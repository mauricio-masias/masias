<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Caches another provider's reports.
 *
 * The Data API allows a limited number of tokens per property per day, and a
 * dashboard with several widgets issues a handful of reports on every page
 * load. Without this decorator an afternoon of refreshing exhausts the daily
 * quota and the dashboard starts failing for everyone.
 *
 * Closed periods can never change, so they are held far longer than periods
 * that still include today.
 *
 * Only plain arrays are written to the cache, never objects. Laravel ships
 * with cache.serializable_classes disabled, so a cached object returns as
 * __PHP_Incomplete_Class rather than raising an error. Storing arrays keeps
 * that hardening intact and works identically on every cache store.
 */
class CachedAnalyticsProvider implements AnalyticsProvider
{
    public function __construct(
        private readonly AnalyticsProvider $inner,
        private readonly Repository $cache,
        private readonly string $prefix,
        private readonly int $liveTtl,
        private readonly int $historicalTtl,
    ) {}

    public function summary(Period $period): PeriodSummary
    {
        return PeriodSummary::fromArray($this->remember(
            'summary',
            $period,
            fn (): array => $this->inner->summary($period)->toArray(),
        ));
    }

    public function trend(Period $period, Granularity $granularity = Granularity::Daily): array
    {
        $rows = $this->remember("trend:{$granularity->value}", $period, fn (): array => array_map(
            fn (TrendPoint $point): array => $point->toArray(),
            $this->inner->trend($period, $granularity),
        ));

        return array_map(fn (array $row): TrendPoint => TrendPoint::fromArray($row), $rows);
    }

    public function topCountries(Period $period, int $limit = 10): array
    {
        return $this->geo("countries:{$limit}", $period, fn (): array => $this->inner->topCountries($period, $limit));
    }

    public function topCities(Period $period, int $limit = 10): array
    {
        return $this->geo("cities:{$limit}", $period, fn (): array => $this->inner->topCities($period, $limit));
    }

    /**
     * @param  Closure(): list<GeoRow>  $fetch
     * @return list<GeoRow>
     */
    private function geo(string $report, Period $period, Closure $fetch): array
    {
        $rows = $this->remember($report, $period, fn (): array => array_map(
            fn (GeoRow $row): array => $row->toArray(),
            $fetch(),
        ));

        return array_map(fn (array $row): GeoRow => GeoRow::fromArray($row), $rows);
    }

    /**
     * Failures are not cached. A quota error or a network blip would otherwise
     * be pinned in place for hours after the underlying problem cleared.
     *
     * @param  Closure(): array<mixed>  $callback
     * @return array<mixed>
     */
    private function remember(string $report, Period $period, Closure $callback): array
    {
        $key = "{$this->prefix}:{$report}:{$period->cacheKey()}";

        return $this->cache->remember($key, $this->ttlFor($period), $callback);
    }

    private function ttlFor(Period $period): int
    {
        return $period->isStillSettling() ? $this->liveTtl : $this->historicalTtl;
    }
}
