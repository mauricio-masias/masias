<?php

namespace App\Services\Analytics\Data;

use Carbon\CarbonImmutable;

/**
 * An inclusive date range, anchored to the configured reporting timezone.
 *
 * GA4 reports on whole days in the property timezone, so every range here is
 * day-granular. Times are normalised to start/end of day to keep cache keys
 * stable across requests made at different moments.
 */
final readonly class Period
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function make(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self($start->startOfDay(), $end->endOfDay());
    }

    public static function today(): self
    {
        $now = self::now();

        return self::make($now, $now);
    }

    public static function yesterday(): self
    {
        $yesterday = self::now()->subDay();

        return self::make($yesterday, $yesterday);
    }

    public static function thisWeek(): self
    {
        $now = self::now();

        return self::make($now->startOfWeek(config('analytics.week_starts_on')), $now);
    }

    public static function thisMonth(): self
    {
        $now = self::now();

        return self::make($now->startOfMonth(), $now);
    }

    /**
     * The last $days days, including today.
     */
    public static function lastDays(int $days): self
    {
        $now = self::now();

        return self::make($now->subDays(max($days, 1) - 1), $now);
    }

    public static function allTime(): self
    {
        return self::make(
            CarbonImmutable::parse(config('analytics.earliest_date'), self::timezone()),
            self::now(),
        );
    }

    /**
     * The equal-length range immediately preceding this one.
     *
     * Used for period-over-period deltas. Comparing a partial month against a
     * whole previous month would overstate the drop, so the comparison window
     * always matches this period's length rather than snapping to a calendar
     * boundary.
     */
    public function previous(): self
    {
        $length = $this->lengthInDays();

        return self::make(
            $this->start->subDays($length),
            $this->start->subDay(),
        );
    }

    public function lengthInDays(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    /**
     * Periods that include today are still accumulating and must not be
     * cached for long.
     */
    public function includesToday(): bool
    {
        return $this->end->startOfDay()->greaterThanOrEqualTo(self::now()->startOfDay());
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }

    public function cacheKey(): string
    {
        return $this->startDate().'_'.$this->endDate();
    }

    private static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    private static function timezone(): string
    {
        return config('analytics.timezone', 'UTC');
    }
}
