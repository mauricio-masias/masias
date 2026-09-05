<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Throwable;

/**
 * Reads reports from the GA4 Data API.
 *
 * This class is deliberately free of caching so that the cache policy lives in
 * one place; wrap it in CachedAnalyticsProvider before use.
 */
class GoogleAnalyticsProvider implements AnalyticsProvider
{
    public function __construct(
        private readonly BetaAnalyticsDataClient $client,
        private readonly string $propertyId,
    ) {}

    public function summary(Period $period): PeriodSummary
    {
        $response = $this->runReport('summary', $period, [
            'metrics' => [
                'totalUsers',
                'newUsers',
                'sessions',
                'screenPageViews',
                'averageSessionDuration',
                'engagementRate',
            ],
        ]);

        $row = $response->getRows()[0] ?? null;

        if ($row === null) {
            return PeriodSummary::empty();
        }

        return new PeriodSummary(
            visitors: (int) $this->metric($row, 0),
            newVisitors: (int) $this->metric($row, 1),
            sessions: (int) $this->metric($row, 2),
            pageViews: (int) $this->metric($row, 3),
            averageSessionDuration: (float) $this->metric($row, 4),
            engagementRate: (float) $this->metric($row, 5),
        );
    }

    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array
    {
        $response = $this->runReport('trend', $period, [
            'dimensions' => [$granularity->dimension()],
            'metrics' => [
                'totalUsers',
                'sessions',
                'screenPageViews',
                'newUsers',
                'averageSessionDuration',
                'engagementRate',
            ],
            'orderBys' => [
                (new OrderBy)->setDimension(
                    (new DimensionOrderBy)->setDimensionName($granularity->dimension()),
                ),
            ],
        ]);

        $byBucket = [];

        foreach ($response->getRows() as $row) {
            $date = $granularity->parse($this->dimension($row, 0), config('analytics.timezone'));

            $byBucket[$date->toDateString()] = new TrendPoint(
                date: $date,
                visitors: (int) $this->metric($row, 0),
                sessions: (int) $this->metric($row, 1),
                pageViews: (int) $this->metric($row, 2),
                newVisitors: (int) $this->metric($row, 3),
                averageSessionDuration: (float) $this->metric($row, 4),
                engagementRate: (float) $this->metric($row, 5),
            );
        }

        return $this->padMissingBuckets($period, $granularity, $byBucket);
    }

    /**
     * GA4 omits buckets with no traffic entirely. Charting the sparse response
     * would draw a continuous line between distant dates and hide the quiet
     * stretches, so absent buckets are filled in explicitly as zero.
     *
     * @param  array<string, TrendPoint>  $byBucket
     * @return list<TrendPoint>
     */
    private function padMissingBuckets(Period $period, Granularity $granularity, array $byBucket): array
    {
        $points = [];
        $cursor = $granularity->startOf($period->start);

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $points[] = $byBucket[$cursor->toDateString()]
                ?? new TrendPoint($cursor, 0, 0, 0);

            $cursor = $granularity->next($cursor);
        }

        return $points;
    }

    public function topCountries(Period $period, int $limit = 10): array
    {
        $response = $this->runReport('topCountries', $period, [
            'dimensions' => ['country', 'countryId'],
            'metrics' => ['totalUsers', 'sessions'],
            'orderBys' => [$this->byVisitorsDescending()],
            'limit' => $limit,
        ]);

        $rows = [];

        foreach ($response->getRows() as $row) {
            $rows[] = new GeoRow(
                countryCode: $this->dimension($row, 1),
                country: $this->dimension($row, 0),
                city: null,
                visitors: (int) $this->metric($row, 0),
                sessions: (int) $this->metric($row, 1),
            );
        }

        return $rows;
    }

    public function topCities(Period $period, int $limit = 10): array
    {
        $response = $this->runReport('topCities', $period, [
            'dimensions' => ['city', 'country', 'countryId'],
            'metrics' => ['totalUsers', 'sessions'],
            'orderBys' => [$this->byVisitorsDescending()],
            'limit' => $limit,
        ]);

        $rows = [];

        foreach ($response->getRows() as $row) {
            $rows[] = new GeoRow(
                countryCode: $this->dimension($row, 2),
                country: $this->dimension($row, 1),
                city: $this->dimension($row, 0),
                visitors: (int) $this->metric($row, 0),
                sessions: (int) $this->metric($row, 1),
            );
        }

        return $rows;
    }

    /**
     * @param  array{dimensions?: list<string>, metrics: list<string>, orderBys?: list<OrderBy>, limit?: int}  $spec
     */
    private function runReport(string $report, Period $period, array $spec): RunReportResponse
    {
        $request = (new RunReportRequest)
            ->setProperty("properties/{$this->propertyId}")
            ->setDateRanges([
                (new DateRange)
                    ->setStartDate($period->startDate())
                    ->setEndDate($period->endDate()),
            ])
            ->setMetrics(array_map(
                fn (string $name): Metric => (new Metric)->setName($name),
                $spec['metrics'],
            ))
            ->setDimensions(array_map(
                fn (string $name): Dimension => (new Dimension)->setName($name),
                $spec['dimensions'] ?? [],
            ));

        if (isset($spec['orderBys'])) {
            $request->setOrderBys($spec['orderBys']);
        }

        if (isset($spec['limit'])) {
            $request->setLimit($spec['limit']);
        }

        try {
            return $this->client->runReport($request);
        } catch (Throwable $e) {
            throw AnalyticsUnavailable::requestFailed($report, $e);
        }
    }

    private function byVisitorsDescending(): OrderBy
    {
        return (new OrderBy)
            ->setMetric((new MetricOrderBy)->setMetricName('totalUsers'))
            ->setDesc(true);
    }

    private function metric(Row $row, int $index): string
    {
        return $row->getMetricValues()[$index]?->getValue() ?? '0';
    }

    private function dimension(Row $row, int $index): string
    {
        return $row->getDimensionValues()[$index]?->getValue() ?? '';
    }
}
