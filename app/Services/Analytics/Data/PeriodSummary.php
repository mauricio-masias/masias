<?php

namespace App\Services\Analytics\Data;

/**
 * Headline totals for a single period.
 *
 * Note that visitor counts are de-duplicated by GA4 across the whole range,
 * so summing summaries from adjacent periods will overcount anyone who
 * visited in both. Always query the range you actually want to report.
 */
final readonly class PeriodSummary
{
    public function __construct(
        public int $visitors,
        public int $newVisitors,
        public int $sessions,
        public int $pageViews,
        public float $averageSessionDuration,
        public float $engagementRate,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0.0, 0.0);
    }

    /**
     * Percentage change between two values, or null when there is no
     * meaningful baseline.
     *
     * Growth from zero is undefined rather than infinite, so callers get null
     * and can render "new" instead of a nonsense percentage.
     */
    public static function percentageChange(int|float $current, int|float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return [
            'visitors' => $this->visitors,
            'new_visitors' => $this->newVisitors,
            'sessions' => $this->sessions,
            'page_views' => $this->pageViews,
            'average_session_duration' => $this->averageSessionDuration,
            'engagement_rate' => $this->engagementRate,
        ];
    }

    /**
     * @param  array<string, int|float>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            visitors: (int) ($data['visitors'] ?? 0),
            newVisitors: (int) ($data['new_visitors'] ?? 0),
            sessions: (int) ($data['sessions'] ?? 0),
            pageViews: (int) ($data['page_views'] ?? 0),
            averageSessionDuration: (float) ($data['average_session_duration'] ?? 0),
            engagementRate: (float) ($data['engagement_rate'] ?? 0),
        );
    }

    public function returningVisitors(): int
    {
        return max($this->visitors - $this->newVisitors, 0);
    }
}
