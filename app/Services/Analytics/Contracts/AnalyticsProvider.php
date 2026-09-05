<?php

namespace App\Services\Analytics\Contracts;

use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;

/**
 * The application's own analytics contract.
 *
 * Nothing outside this namespace should touch the Google SDK, so the data
 * source can be swapped (local snapshot table, a different vendor) without
 * rewriting the dashboard.
 *
 * @throws AnalyticsUnavailable on any transport, auth, or quota failure
 */
interface AnalyticsProvider
{
    public function summary(Period $period): PeriodSummary;

    /**
     * Bucketed totals, ordered oldest first.
     *
     * Every bucket in the period is present. GA4 omits buckets with no
     * traffic, so implementations fill those in as zero rather than returning
     * a sparse series that would distort a chart.
     *
     * Granularity is requested rather than derived, because visitor counts are
     * de-duplicated per bucket and cannot be summed after the fact.
     *
     * @return list<TrendPoint>
     */
    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array;

    /**
     * @return list<GeoRow>
     */
    public function topCountries(Period $period, int $limit = 10): array;

    /**
     * @return list<GeoRow>
     */
    public function topCities(Period $period, int $limit = 10): array;
}
