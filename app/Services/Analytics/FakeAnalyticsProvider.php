<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Carbon\CarbonImmutable;

/**
 * Deterministic stand-in for the Data API.
 *
 * Used by the test suite so no test ever touches the network, and available
 * behind ANALYTICS_DRIVER=fake for building widgets without credentials.
 * Numbers are derived from the date so they are stable between runs.
 */
class FakeAnalyticsProvider implements AnalyticsProvider
{
    private bool $shouldFail = false;

    /**
     * Make every subsequent call fail, to exercise the unavailable state.
     */
    public function fail(): self
    {
        $this->shouldFail = true;

        return $this;
    }

    public function summary(Period $period): PeriodSummary
    {
        $this->guard();

        $visitors = array_sum(array_map(
            fn (TrendPoint $point): int => $point->visitors,
            $this->trend($period),
        ));

        return new PeriodSummary(
            visitors: $visitors,
            newVisitors: (int) round($visitors * 0.62),
            sessions: (int) round($visitors * 1.3),
            pageViews: (int) round($visitors * 2.8),
            averageSessionDuration: 74.5,
            engagementRate: 0.58,
        );
    }

    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array
    {
        $this->guard();

        // Larger buckets hold more visitors, but not proportionally more,
        // because a repeat visitor is counted once per bucket.
        $scale = match ($granularity) {
            Granularity::Daily => 1,
            Granularity::Weekly => 4,
            Granularity::Monthly => 12,
        };

        $points = [];
        $cursor = $granularity->startOf($period->start);

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $visitors = (int) round(
                (8 + ((int) $cursor->format('z') % 17)) * $scale * $this->coverage($cursor, $granularity, $period),
            );

            $points[] = new TrendPoint(
                date: $cursor,
                visitors: $visitors,
                sessions: (int) round($visitors * 1.3),
                pageViews: (int) round($visitors * 2.8),
                newVisitors: (int) round($visitors * 0.62),
                averageSessionDuration: 74.5,
                engagementRate: 0.58,
            );

            $cursor = $granularity->next($cursor);
        }

        return $points;
    }

    /**
     * How much of a bucket the requested period actually covers.
     *
     * GA4 reports a bucket asked about over part of its span as holding only
     * that part, still labelled with the whole bucket. Reproducing that is the
     * point: without it, a test cannot tell a whole bucket from a fragment,
     * and every assertion about whole-bucket handling passes regardless.
     */
    private function coverage(CarbonImmutable $bucketStart, Granularity $granularity, Period $period): float
    {
        $bucketEnd = $granularity->next($bucketStart)->subDay();

        $from = $bucketStart->greaterThan($period->start) ? $bucketStart : $period->start;
        $to = $bucketEnd->lessThan($period->end) ? $bucketEnd : $period->end;

        $covered = $from->startOfDay()->diffInDays($to->startOfDay()) + 1;
        $total = $bucketStart->startOfDay()->diffInDays($bucketEnd->startOfDay()) + 1;

        return max(min($covered / $total, 1.0), 0.0);
    }

    public function topCountries(Period $period, int $limit = 10): array
    {
        $this->guard();

        return array_slice([
            new GeoRow('GB', 'United Kingdom', null, 412, 530),
            new GeoRow('US', 'United States', null, 188, 233),
            new GeoRow('PE', 'Peru', null, 96, 121),
            new GeoRow('ES', 'Spain', null, 54, 68),
            new GeoRow('', '(not set)', null, 12, 14),
        ], 0, $limit);
    }

    public function topCities(Period $period, int $limit = 10): array
    {
        $this->guard();

        return array_slice([
            new GeoRow('GB', 'United Kingdom', 'London', 254, 331),
            new GeoRow('GB', 'United Kingdom', 'Manchester', 61, 78),
            new GeoRow('US', 'United States', 'New York', 47, 59),
            new GeoRow('PE', 'Peru', 'Lima', 44, 55),
            new GeoRow('', '(not set)', '(not set)', 19, 22),
        ], 0, $limit);
    }

    private function guard(): void
    {
        if ($this->shouldFail) {
            throw AnalyticsUnavailable::notConfigured('the fake provider was told to fail');
        }
    }
}
