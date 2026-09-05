<?php

namespace Tests\Feature;

use App\Services\Analytics\CachedAnalyticsProvider;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use App\Services\Analytics\FakeAnalyticsProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsCacheTest extends TestCase
{
    private function cached(AnalyticsProvider $inner): CachedAnalyticsProvider
    {
        return new CachedAnalyticsProvider(
            inner: $inner,
            cache: Cache::store('array'),
            prefix: 'analytics',
            liveTtl: 900,
            historicalTtl: 43200,
        );
    }

    /**
     * Mirrors production cache semantics: values are serialized, and
     * cache.serializable_classes is disabled so no object may be unserialized.
     * The default array store in tests keeps objects by reference and would
     * hide the problem entirely.
     */
    private function hardenedStore(): Repository
    {
        return new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
    }

    public function test_summary_survives_a_store_that_forbids_object_serialization(): void
    {
        $provider = new CachedAnalyticsProvider(
            inner: new FakeAnalyticsProvider,
            cache: $this->hardenedStore(),
            prefix: 'analytics',
            liveTtl: 900,
            historicalTtl: 43200,
        );
        $period = Period::lastDays(7);

        $fresh = $provider->summary($period);
        $cached = $provider->summary($period);

        $this->assertInstanceOf(PeriodSummary::class, $cached);
        $this->assertEquals($fresh, $cached);
    }

    public function test_trend_and_geo_survive_a_store_that_forbids_object_serialization(): void
    {
        $provider = new CachedAnalyticsProvider(
            inner: new FakeAnalyticsProvider,
            cache: $this->hardenedStore(),
            prefix: 'analytics',
            liveTtl: 900,
            historicalTtl: 43200,
        );
        $period = Period::lastDays(7);

        $provider->trend($period);
        $provider->topCountries($period, 5);
        $provider->topCities($period, 5);

        $trend = $provider->trend($period);
        $countries = $provider->topCountries($period, 5);
        $cities = $provider->topCities($period, 5);

        $this->assertContainsOnlyInstancesOf(TrendPoint::class, $trend);
        $this->assertContainsOnlyInstancesOf(GeoRow::class, $countries);
        $this->assertContainsOnlyInstancesOf(GeoRow::class, $cities);

        // Dates must survive the round trip, not arrive as raw strings.
        $this->assertSame($period->startDate(), $trend[0]->date->toDateString());
        $this->assertSame('London, United Kingdom', $cities[0]->label());
    }

    public function test_only_plain_arrays_are_written_to_the_cache(): void
    {
        $store = $this->hardenedStore();
        $provider = new CachedAnalyticsProvider(
            inner: new FakeAnalyticsProvider,
            cache: $store,
            prefix: 'analytics',
            liveTtl: 900,
            historicalTtl: 43200,
        );
        $period = Period::lastDays(7);

        $provider->summary($period);

        $this->assertIsArray($store->get('analytics:summary:'.$period->cacheKey()));
    }

    public function test_repeated_reads_hit_the_provider_once(): void
    {
        $counter = new CountingAnalyticsProvider(new FakeAnalyticsProvider);
        $provider = $this->cached($counter);
        $period = Period::lastDays(7);

        $provider->summary($period);
        $provider->summary($period);
        $provider->summary($period);

        $this->assertSame(1, $counter->summaryCalls);
    }

    public function test_different_periods_are_cached_separately(): void
    {
        $counter = new CountingAnalyticsProvider(new FakeAnalyticsProvider);
        $provider = $this->cached($counter);

        $provider->summary(Period::lastDays(7));
        $provider->summary(Period::lastDays(30));

        $this->assertSame(2, $counter->summaryCalls);
    }

    public function test_different_limits_are_cached_separately(): void
    {
        $counter = new CountingAnalyticsProvider(new FakeAnalyticsProvider);
        $provider = $this->cached($counter);
        $period = Period::lastDays(7);

        $provider->topCountries($period, 5);
        $provider->topCountries($period, 10);
        $provider->topCountries($period, 5);

        $this->assertSame(2, $counter->countryCalls);
    }

    public function test_reports_are_cached_independently_of_each_other(): void
    {
        $counter = new CountingAnalyticsProvider(new FakeAnalyticsProvider);
        $provider = $this->cached($counter);
        $period = Period::lastDays(7);

        $provider->summary($period);
        $provider->trend($period);

        $this->assertSame(1, $counter->summaryCalls);
        $this->assertSame(1, $counter->trendCalls);
    }

    public function test_failures_are_not_cached(): void
    {
        $failing = (new FakeAnalyticsProvider)->fail();
        $provider = $this->cached($failing);
        $period = Period::lastDays(7);

        $this->assertThrows(fn () => $provider->summary($period), AnalyticsUnavailable::class);

        // A transient outage must not be pinned in the cache for hours.
        $this->assertThrows(fn () => $provider->summary($period), AnalyticsUnavailable::class);
        $this->assertNull(Cache::store('array')->get('analytics:summary:'.$period->cacheKey()));
    }

    public function test_cached_values_survive_a_round_trip_through_the_store(): void
    {
        $provider = $this->cached(new FakeAnalyticsProvider);
        $period = Period::lastDays(7);

        $first = $provider->summary($period);
        $second = $provider->summary($period);

        $this->assertEquals($first, $second);
        $this->assertInstanceOf(PeriodSummary::class, $second);
    }

    public function test_daily_trend_returns_one_point_per_day(): void
    {
        $points = (new FakeAnalyticsProvider)->trend(Period::lastDays(7));

        $this->assertCount(7, $points);
        $this->assertContainsOnlyInstancesOf(TrendPoint::class, $points);
    }

    public function test_daily_trend_has_no_gaps(): void
    {
        $period = Period::lastDays(14);

        $dates = array_map(
            fn (TrendPoint $point): string => $point->date->toDateString(),
            (new FakeAnalyticsProvider)->trend($period),
        );

        // GA4 omits zero-traffic days; a chart needs every day present.
        $this->assertCount(14, $dates);
        $this->assertSame($period->startDate(), $dates[0]);
        $this->assertSame($period->endDate(), end($dates));
        $this->assertSame($dates, array_unique($dates));
    }

    public function test_geo_rows_expose_a_readable_label(): void
    {
        $rows = (new FakeAnalyticsProvider)->topCities(Period::lastDays(7), 2);

        $this->assertContainsOnlyInstancesOf(GeoRow::class, $rows);
        $this->assertSame('London, United Kingdom', $rows[0]->label());
    }

    public function test_country_rows_label_without_a_city(): void
    {
        $rows = (new FakeAnalyticsProvider)->topCountries(Period::lastDays(7), 1);

        $this->assertSame('United Kingdom', $rows[0]->label());
    }
}

/**
 * Counts calls so cache behaviour can be asserted without mocking.
 */
class CountingAnalyticsProvider implements AnalyticsProvider
{
    public int $summaryCalls = 0;

    public int $trendCalls = 0;

    public int $countryCalls = 0;

    public int $cityCalls = 0;

    public function __construct(private readonly AnalyticsProvider $inner) {}

    public function summary(Period $period): PeriodSummary
    {
        $this->summaryCalls++;

        return $this->inner->summary($period);
    }

    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array
    {
        $this->trendCalls++;

        return $this->inner->trend($period, $granularity);
    }

    public function topCountries(Period $period, int $limit = 10): array
    {
        $this->countryCalls++;

        return $this->inner->topCountries($period, $limit);
    }

    public function topCities(Period $period, int $limit = 10): array
    {
        $this->cityCalls++;

        return $this->inner->topCities($period, $limit);
    }
}
