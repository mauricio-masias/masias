<?php

namespace Tests\Unit;

use App\Services\Analytics\Data\Period;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AnalyticsPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('analytics.timezone', 'Europe/London');
        config()->set('analytics.week_starts_on', 1);

        // A Wednesday, so week and month boundaries are both partial.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-16 14:30:00', 'Europe/London'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_today_covers_a_single_day(): void
    {
        $period = Period::today();

        $this->assertSame('2026-09-16', $period->startDate());
        $this->assertSame('2026-09-16', $period->endDate());
        $this->assertSame(1, $period->lengthInDays());
    }

    public function test_this_week_starts_on_monday(): void
    {
        $period = Period::thisWeek();

        $this->assertSame('2026-09-14', $period->startDate());
        $this->assertSame('2026-09-16', $period->endDate());
        $this->assertSame(3, $period->lengthInDays());
    }

    public function test_this_month_starts_on_the_first(): void
    {
        $period = Period::thisMonth();

        $this->assertSame('2026-09-01', $period->startDate());
        $this->assertSame(16, $period->lengthInDays());
    }

    public function test_last_days_includes_today(): void
    {
        $period = Period::lastDays(7);

        $this->assertSame('2026-09-10', $period->startDate());
        $this->assertSame('2026-09-16', $period->endDate());
        $this->assertSame(7, $period->lengthInDays());
    }

    public function test_previous_period_matches_length_and_ends_the_day_before(): void
    {
        $previous = Period::thisWeek()->previous();

        $this->assertSame('2026-09-11', $previous->startDate());
        $this->assertSame('2026-09-13', $previous->endDate());
        $this->assertSame(3, $previous->lengthInDays());
    }

    public function test_previous_of_today_is_yesterday(): void
    {
        $previous = Period::today()->previous();

        $this->assertEquals(Period::yesterday()->startDate(), $previous->startDate());
        $this->assertEquals(Period::yesterday()->endDate(), $previous->endDate());
    }

    public function test_periods_ending_today_are_marked_live(): void
    {
        $this->assertTrue(Period::today()->includesToday());
        $this->assertTrue(Period::thisMonth()->includesToday());
        $this->assertFalse(Period::yesterday()->includesToday());
        $this->assertFalse(Period::thisWeek()->previous()->includesToday());
    }

    public function test_all_time_starts_at_the_configured_earliest_date(): void
    {
        config()->set('analytics.earliest_date', '2021-03-04');

        $this->assertSame('2021-03-04', Period::allTime()->startDate());
        $this->assertSame('2026-09-16', Period::allTime()->endDate());
    }

    public function test_cache_key_is_stable_regardless_of_time_of_day(): void
    {
        $morning = Period::make(
            CarbonImmutable::parse('2026-09-01 08:00:00'),
            CarbonImmutable::parse('2026-09-07 09:15:00'),
        );

        $evening = Period::make(
            CarbonImmutable::parse('2026-09-01 22:45:00'),
            CarbonImmutable::parse('2026-09-07 23:59:00'),
        );

        $this->assertSame($morning->cacheKey(), $evening->cacheKey());
        $this->assertSame('2026-09-01_2026-09-07', $morning->cacheKey());
    }
}
