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

    /**
     * Months already written during this run.
     *
     * A long backfill is split into chunks, and each chunk covers the whole of
     * any month it touches. Without this, a small chunk size would re-request
     * the same month's geography several times and burn quota for nothing.
     *
     * @var array<string, true>
     */
    private array $syncedMonths = [];

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
        $points = $this->analytics->trend($period->alignedTo($granularity), $granularity);

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
            if (isset($this->syncedMonths[$month->startDate()])) {
                continue;
            }

            $this->syncedMonths[$month->startDate()] = true;

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
                // GA4 reports unresolved geography as "(not set)", and as
                // "(other)" once a report hits its cardinality limit. These
                // are different groups, so the raw value is kept when it is
                // not an ISO code: collapsing both to an empty string would
                // give them the same natural key, and the second would
                // overwrite the first in the archive permanently.
                'country_code' => $row->isoCode() ?? $row->countryCode,
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
