<?php

namespace App\Services\Analytics\Snapshot;

use App\Models\AnalyticsBucket;
use App\Models\AnalyticsGeoBucket;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\TrendPoint;

/**
 * Copies Google Analytics totals into the local archive.
 *
 * Google discards event-level data at the end of its retention window, so any
 * day not copied before then is lost for good. Runs are idempotent: buckets
 * are upserted on their natural key, so re-running over the same dates
 * refreshes them rather than duplicating.
 */
class SnapshotWriter
{
    /**
     * How many geography rows to keep per month. Far more than the dashboard
     * shows, because the archive should outlive the current widgets.
     */
    private const GEO_LIMIT = 50;

    public function __construct(private readonly AnalyticsProvider $analytics) {}

    public function sync(Period $period): SnapshotResult
    {
        $result = new SnapshotResult;

        foreach (Granularity::cases() as $granularity) {
            $result = $result->add(
                buckets: $this->syncBuckets($period, $granularity),
                reports: 1,
            );
        }

        return $this->syncGeo($period, $result);
    }

    private function syncBuckets(Period $period, Granularity $granularity): int
    {
        $points = $this->analytics->trend($this->wholeBuckets($period, $granularity), $granularity);

        $rows = array_map(
            fn (TrendPoint $point): array => [
                'granularity' => $granularity->value,
                'bucket_start' => $point->date->toDateString(),
                'visitors' => $point->visitors,
                'new_visitors' => $point->newVisitors,
                'sessions' => $point->sessions,
                'page_views' => $point->pageViews,
                'average_session_duration' => $point->averageSessionDuration,
                'engagement_rate' => $point->engagementRate,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $points,
        );

        if ($rows === []) {
            return 0;
        }

        AnalyticsBucket::upsert(
            $rows,
            ['granularity', 'bucket_start'],
            ['visitors', 'new_visitors', 'sessions', 'page_views', 'average_session_duration', 'engagement_rate', 'updated_at'],
        );

        return count($rows);
    }

    /**
     * Geography is archived monthly rather than daily.
     *
     * GA4 withholds locations for very small groups, so at a daily grain most
     * rows on a low-traffic site would arrive as "(not set)". Monthly buckets
     * carry enough visitors to resolve real cities, and cost two reports per
     * month instead of two per day.
     */
    private function syncGeo(Period $period, SnapshotResult $result): SnapshotResult
    {
        foreach ($this->monthsIn($period) as $month) {
            $rows = [
                ...$this->geoRows($this->analytics->topCountries($month, self::GEO_LIMIT), $month, 'country'),
                ...$this->geoRows($this->analytics->topCities($month, self::GEO_LIMIT), $month, 'city'),
            ];

            $result = $result->add(reports: 2);

            if ($rows === []) {
                continue;
            }

            AnalyticsGeoBucket::upsert(
                $rows,
                ['granularity', 'bucket_start', 'level', 'country_code', 'city'],
                ['country', 'visitors', 'sessions', 'updated_at'],
            );

            $result = $result->add(geoRows: count($rows));
        }

        return $result;
    }

    /**
     * @param  list<GeoRow>  $geoRows
     * @return list<array<string, mixed>>
     */
    private function geoRows(array $geoRows, Period $month, string $level): array
    {
        $rows = [];

        foreach ($geoRows as $row) {
            $rows[] = [
                'granularity' => Granularity::Monthly->value,
                'bucket_start' => $month->startDate(),
                'level' => $level,
                // GA4 reports unresolved geography as "(not set)" rather than
                // an empty value. Normalising it here keeps one representation
                // of "unknown" in the archive, and keeps the code column to
                // actual ISO codes.
                'country_code' => $row->isoCode() ?? '',
                'country' => $row->country,
                // Never null: the unique key must be able to match on it.
                'city' => $row->hasKnownCity() ? $row->city : '',
                'visitors' => $row->visitors,
                'sessions' => $row->sessions,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    /**
     * Grows a period outwards until it covers whole buckets.
     *
     * A bucket asked about over part of its span comes back holding only that
     * part, but is still labelled with the whole bucket. Writing that would
     * overwrite a complete figure with a fragment, which is exactly what
     * happens at the seam between two chunks of a long backfill. Overlapping
     * neighbouring chunks is harmless by comparison, because every bucket
     * written is complete and the upsert is idempotent.
     */
    private function wholeBuckets(Period $period, Granularity $granularity): Period
    {
        $start = $granularity->startOf($period->start);
        $end = $granularity->next($granularity->startOf($period->end))->subDay();

        return Period::make($start, $end);
    }

    /**
     * Every calendar month the period touches, in full for the same reason.
     *
     * @return list<Period>
     */
    private function monthsIn(Period $period): array
    {
        $months = [];
        $cursor = $period->start->startOfMonth();

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $months[] = Period::make($cursor, $cursor->endOfMonth());

            $cursor = $cursor->addMonth()->startOfMonth();
        }

        return $months;
    }
}
