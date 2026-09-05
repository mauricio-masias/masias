<?php

namespace Tests\Feature;

use App\Models\AnalyticsBucket;
use App\Models\AnalyticsGeoBucket;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\FakeAnalyticsProvider;
use App\Services\Analytics\Snapshot\SnapshotRepository;
use App\Services\Analytics\Snapshot\SnapshotWriter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AnalyticsSnapshotTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('analytics.timezone', 'Europe/London');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-16 09:00:00', 'Europe/London'));

        $this->app->instance(AnalyticsProvider::class, new FakeAnalyticsProvider);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function writer(): SnapshotWriter
    {
        return new SnapshotWriter($this->app->make(AnalyticsProvider::class));
    }

    public function test_sync_stores_buckets_at_every_granularity(): void
    {
        $this->writer()->sync(Period::lastDays(14));

        $this->assertSame(14, AnalyticsBucket::where('granularity', 'daily')->count());
        $this->assertGreaterThan(0, AnalyticsBucket::where('granularity', 'weekly')->count());
        $this->assertGreaterThan(0, AnalyticsBucket::where('granularity', 'monthly')->count());
    }

    public function test_buckets_are_aligned_to_their_granularity(): void
    {
        $this->writer()->sync(Period::lastDays(70));

        // A bucket dated mid-month or mid-week means the series was parsed or
        // padded at the wrong size, which silently mislabels every figure.
        foreach (AnalyticsBucket::where('granularity', 'monthly')->get() as $month) {
            $this->assertSame(1, $month->bucket_start->day, "monthly bucket {$month->bucket_start->toDateString()} is not the first of a month");
        }

        foreach (AnalyticsBucket::where('granularity', 'weekly')->get() as $week) {
            $this->assertSame('Monday', $week->bucket_start->format('l'), "weekly bucket {$week->bucket_start->toDateString()} is not a Monday");
        }
    }

    public function test_monthly_buckets_cover_each_month_once(): void
    {
        $this->writer()->sync(Period::make(
            CarbonImmutable::parse('2026-06-15', 'Europe/London'),
            CarbonImmutable::parse('2026-09-10', 'Europe/London'),
        ));

        $months = AnalyticsBucket::where('granularity', 'monthly')
            ->orderBy('bucket_start')
            ->pluck('bucket_start')
            ->map(fn ($date): string => $date->toDateString())
            ->all();

        $this->assertSame(['2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01'], $months);
    }

    public function test_weekly_buckets_are_stored_not_derived_from_days(): void
    {
        $this->writer()->sync(Period::lastDays(14));

        $week = AnalyticsBucket::where('granularity', 'weekly')->orderBy('bucket_start')->first();
        $daysInWeek = AnalyticsBucket::where('granularity', 'daily')
            ->whereBetween('bucket_start', [
                $week->bucket_start->toDateString(),
                $week->bucket_start->addDays(6)->toDateString(),
            ])
            ->sum('visitors');

        // If weeks were built by summing days these would match, and repeat
        // visitors would be counted once per day they appeared.
        $this->assertNotSame((int) $daysInWeek, $week->visitors);
    }

    public function test_chunked_backfill_matches_a_single_run(): void
    {
        // A chunk boundary falling inside a week or month must not leave a
        // fragment of that bucket stored in place of the whole thing.
        $this->artisan('analytics:sync', [
            '--from' => '2026-04-01',
            '--to' => '2026-08-31',
            '--chunk' => 90,
        ])->assertSuccessful();

        $chunked = AnalyticsBucket::orderBy('granularity')
            ->orderBy('bucket_start')
            ->get()
            ->map(fn (AnalyticsBucket $b): string => "{$b->granularity->value}:{$b->bucket_start->toDateString()}:{$b->visitors}")
            ->all();

        AnalyticsBucket::query()->delete();

        $this->artisan('analytics:sync', [
            '--from' => '2026-04-01',
            '--to' => '2026-08-31',
            '--chunk' => 5000,
        ])->assertSuccessful();

        $whole = AnalyticsBucket::orderBy('granularity')
            ->orderBy('bucket_start')
            ->get()
            ->map(fn (AnalyticsBucket $b): string => "{$b->granularity->value}:{$b->bucket_start->toDateString()}:{$b->visitors}")
            ->all();

        $this->assertSame($whole, $chunked);
    }

    public function test_partial_periods_still_store_whole_buckets(): void
    {
        // Asking about three days in the middle of a month must still record
        // the month as a month, not as those three days.
        $this->writer()->sync(Period::make(
            CarbonImmutable::parse('2026-07-10', 'Europe/London'),
            CarbonImmutable::parse('2026-07-12', 'Europe/London'),
        ));

        $month = AnalyticsBucket::where('granularity', 'monthly')->first();
        $july = $this->app->make(AnalyticsProvider::class)->trend(
            Period::make(
                CarbonImmutable::parse('2026-07-01', 'Europe/London'),
                CarbonImmutable::parse('2026-07-31', 'Europe/London'),
            ),
            Granularity::Monthly,
        )[0];

        $this->assertSame('2026-07-01', $month->bucket_start->toDateString());
        $this->assertSame($july->visitors, $month->visitors);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->writer()->sync(Period::lastDays(7));
        $first = AnalyticsBucket::count();

        $this->writer()->sync(Period::lastDays(7));

        $this->assertSame($first, AnalyticsBucket::count());
    }

    public function test_resyncing_updates_existing_figures(): void
    {
        $this->writer()->sync(Period::lastDays(7));

        $bucket = AnalyticsBucket::where('granularity', 'daily')->first();
        $bucket->update(['visitors' => 99999]);

        $this->writer()->sync(Period::lastDays(7));

        // GA4 revises recent days, so a re-run must overwrite rather than skip.
        $this->assertNotSame(99999, $bucket->fresh()->visitors);
    }

    public function test_geo_is_stored_monthly_with_both_levels(): void
    {
        $this->writer()->sync(Period::lastDays(7));

        $this->assertGreaterThan(0, AnalyticsGeoBucket::where('level', 'country')->count());
        $this->assertGreaterThan(0, AnalyticsGeoBucket::where('level', 'city')->count());
        $this->assertSame(
            AnalyticsGeoBucket::count(),
            AnalyticsGeoBucket::where('granularity', 'monthly')->count(),
        );
    }

    public function test_country_and_withheld_city_rows_do_not_collide(): void
    {
        $this->writer()->sync(Period::lastDays(7));

        // The fake returns an unknown country row with an empty city, which
        // shares its natural key with the country-level row apart from level.
        $unknownCountry = AnalyticsGeoBucket::where('level', 'country')->where('country_code', '')->count();
        $unknownCity = AnalyticsGeoBucket::where('level', 'city')->where('country_code', '')->count();

        $this->assertSame(1, $unknownCountry);
        $this->assertSame(1, $unknownCity);
    }

    public function test_repository_reads_back_a_trend(): void
    {
        $this->writer()->sync(Period::lastDays(10));

        $points = (new SnapshotRepository)->trend(Period::lastDays(10));

        $this->assertCount(10, $points);
        $this->assertSame(Period::lastDays(10)->startDate(), $points[0]->date->toDateString());
        $this->assertGreaterThan(0, $points[0]->visitors);
    }

    public function test_repository_reads_a_single_bucket(): void
    {
        $this->writer()->sync(Period::lastDays(5));

        $summary = (new SnapshotRepository)->bucket(
            Granularity::Daily,
            CarbonImmutable::parse(Period::today()->startDate(), 'Europe/London'),
        );

        $this->assertNotNull($summary);
        $this->assertGreaterThan(0, $summary->sessions);
    }

    public function test_repository_returns_null_for_an_unsynced_bucket(): void
    {
        $summary = (new SnapshotRepository)->bucket(
            Granularity::Daily,
            CarbonImmutable::parse('2001-01-01', 'Europe/London'),
        );

        $this->assertNull($summary);
    }

    public function test_repository_reports_archive_coverage(): void
    {
        $this->assertNull((new SnapshotRepository)->earliestBucket());

        $this->writer()->sync(Period::lastDays(10));

        $repository = new SnapshotRepository;

        $this->assertSame(Period::lastDays(10)->startDate(), $repository->earliestBucket()->toDateString());
        $this->assertSame(10, $repository->bucketCount());
    }

    public function test_repository_reads_geo_rows(): void
    {
        $this->writer()->sync(Period::lastDays(7));

        $countries = (new SnapshotRepository)->geo(Period::lastDays(7), 'country', 3);
        $cities = (new SnapshotRepository)->geo(Period::lastDays(7), 'city', 3);

        $this->assertNotEmpty($countries);
        $this->assertNull($countries[0]->city);
        $this->assertNotNull($cities[0]->city);
        $this->assertSame('United Kingdom', $countries[0]->country);
    }

    public function test_command_syncs_a_given_range(): void
    {
        $this->artisan('analytics:sync', ['--from' => '2026-09-01', '--to' => '2026-09-10'])
            ->assertSuccessful();

        $this->assertSame(10, AnalyticsBucket::where('granularity', 'daily')->count());
    }

    public function test_command_rejects_a_reversed_range(): void
    {
        $this->artisan('analytics:sync', ['--from' => '2026-09-10', '--to' => '2026-09-01'])
            ->expectsOutputToContain('--from must not be after --to.')
            ->assertFailed();
    }

    public function test_command_fails_loudly_when_analytics_is_down(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        // A silent failure here would look like a successful sync and leave a
        // permanent hole in the archive once retention passes.
        $this->artisan('analytics:sync', ['--from' => '2026-09-01', '--to' => '2026-09-02'])
            ->assertFailed();

        $this->assertSame(0, AnalyticsBucket::count());
    }

    public function test_command_chunks_long_backfills(): void
    {
        $this->artisan('analytics:sync', [
            '--from' => '2026-01-01',
            '--to' => '2026-03-31',
            '--chunk' => 30,
        ])->assertSuccessful();

        $this->assertSame(90, AnalyticsBucket::where('granularity', 'daily')->count());
    }
}
