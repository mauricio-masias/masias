<?php

namespace App\Services\Analytics\Snapshot;

use App\Models\AnalyticsBucket;
use App\Models\AnalyticsGeoBucket;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use Carbon\CarbonImmutable;

/**
 * Reads the local archive.
 *
 * This deliberately exposes no way to total an arbitrary date range. Visitor
 * counts are de-duplicated inside whatever bucket Google reported, so adding
 * stored buckets together would overcount anyone who came back. Only whole
 * stored buckets are returned, and callers wanting a custom range must ask the
 * Data API for it while the data is still within retention.
 */
class SnapshotRepository
{
    /**
     * @return list<TrendPoint>
     */
    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array
    {
        return AnalyticsBucket::query()
            ->granularity($granularity)
            ->between($period->startDate(), $period->endDate())
            ->orderBy('bucket_start')
            ->get()
            ->map(fn (AnalyticsBucket $bucket): TrendPoint => new TrendPoint(
                date: CarbonImmutable::parse($bucket->bucket_start, config('analytics.timezone'))->startOfDay(),
                visitors: $bucket->visitors,
                sessions: $bucket->sessions,
                pageViews: $bucket->page_views,
                newVisitors: $bucket->new_visitors,
                averageSessionDuration: $bucket->average_session_duration,
                engagementRate: $bucket->engagement_rate,
            ))
            ->values()
            ->all();
    }

    /**
     * Totals for one stored bucket, or null when that bucket was never synced.
     */
    public function bucket(Granularity $granularity, CarbonImmutable $start): ?PeriodSummary
    {
        $bucket = AnalyticsBucket::query()
            ->granularity($granularity)
            ->where('bucket_start', $start->toDateString())
            ->first();

        if ($bucket === null) {
            return null;
        }

        return new PeriodSummary(
            visitors: $bucket->visitors,
            newVisitors: $bucket->new_visitors,
            sessions: $bucket->sessions,
            pageViews: $bucket->page_views,
            averageSessionDuration: $bucket->average_session_duration,
            engagementRate: $bucket->engagement_rate,
        );
    }

    /**
     * Archived geography for the months a period touches.
     *
     * Sessions add up across months, but visitors do not, so rows from more
     * than one month are returned per month rather than merged.
     *
     * @return list<GeoRow>
     */
    public function geo(Period $period, string $level = 'country', int $limit = 10): array
    {
        // Months are matched by overlap, not containment: a bucket dated the
        // first of the month covers a period starting mid-month.
        return AnalyticsGeoBucket::query()
            ->granularity(Granularity::Monthly)
            ->where('level', $level)
            ->between($period->start->startOfMonth()->toDateString(), $period->endDate())
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get()
            ->map(fn (AnalyticsGeoBucket $row): GeoRow => new GeoRow(
                countryCode: $row->country_code,
                country: $row->country,
                city: $level === 'city' ? $row->city : null,
                visitors: $row->visitors,
                sessions: $row->sessions,
            ))
            ->values()
            ->all();
    }

    /**
     * The earliest bucket held at this granularity, for showing how far the
     * archive reaches back.
     */
    public function earliestBucket(Granularity $granularity = Granularity::Daily): ?CarbonImmutable
    {
        $earliest = AnalyticsBucket::query()
            ->granularity($granularity)
            ->min('bucket_start');

        return $earliest === null
            ? null
            : CarbonImmutable::parse($earliest, config('analytics.timezone'))->startOfDay();
    }

    public function bucketCount(Granularity $granularity = Granularity::Daily): int
    {
        return AnalyticsBucket::query()->granularity($granularity)->count();
    }
}
