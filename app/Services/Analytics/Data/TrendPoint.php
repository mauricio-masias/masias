<?php

namespace App\Services\Analytics\Data;

use Carbon\CarbonImmutable;

/**
 * One bucket of a trend series.
 *
 * The date is the first day of the bucket, so a weekly point is dated to its
 * Monday.
 */
final readonly class TrendPoint
{
    public function __construct(
        public CarbonImmutable $date,
        public int $visitors,
        public int $sessions,
        public int $pageViews,
        public int $newVisitors = 0,
        public float $averageSessionDuration = 0.0,
        public float $engagementRate = 0.0,
    ) {}

    /**
     * The bucket's totals in the same shape as any other period summary.
     */
    public function summary(): PeriodSummary
    {
        return new PeriodSummary(
            visitors: $this->visitors,
            newVisitors: $this->newVisitors,
            sessions: $this->sessions,
            pageViews: $this->pageViews,
            averageSessionDuration: $this->averageSessionDuration,
            engagementRate: $this->engagementRate,
        );
    }

    /**
     * @return array<string, string|int|float>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'visitors' => $this->visitors,
            'sessions' => $this->sessions,
            'page_views' => $this->pageViews,
            'new_visitors' => $this->newVisitors,
            'average_session_duration' => $this->averageSessionDuration,
            'engagement_rate' => $this->engagementRate,
        ];
    }

    /**
     * @param  array<string, string|int|float>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: CarbonImmutable::parse($data['date'], config('analytics.timezone'))->startOfDay(),
            visitors: (int) $data['visitors'],
            sessions: (int) $data['sessions'],
            pageViews: (int) $data['page_views'],
            newVisitors: (int) ($data['new_visitors'] ?? 0),
            averageSessionDuration: (float) ($data['average_session_duration'] ?? 0),
            engagementRate: (float) ($data['engagement_rate'] ?? 0),
        );
    }
}
