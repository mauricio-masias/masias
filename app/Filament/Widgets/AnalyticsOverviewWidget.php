<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\PeriodSummary;
use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * The base widget polls every five seconds. Reports are cached for minutes
     * at a time and the Data API is quota limited, so polling would only cost
     * Livewire round trips without ever showing anything new.
     */
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Visitors';

    protected function getDescription(): ?string
    {
        return 'Google Analytics, '.config('analytics.timezone').' days.';
    }

    protected function getStats(): array
    {
        try {
            return $this->stats(app(AnalyticsProvider::class));
        } catch (AnalyticsUnavailable $e) {
            // A Google outage, a revoked key, or an exhausted quota must not
            // take the whole dashboard down with it.
            report($e);

            return $this->unavailableStats();
        }
    }

    /**
     * @return array<Stat>
     */
    private function stats(AnalyticsProvider $analytics): array
    {
        $today = Period::today();
        $week = Period::thisWeek();
        $month = Period::thisMonth();

        $monthTrend = $analytics->trend(Period::lastDays(30));
        $allTime = $analytics->summary(Period::allTime());

        return [
            $this->comparedStat('Today', $analytics, $today, 'yesterday'),
            $this->comparedStat('This week', $analytics, $week, 'the previous '.$week->lengthInDays().' days'),
            $this->comparedStat('This month', $analytics, $month, 'the previous '.$month->lengthInDays().' days')
                ->chart(array_map(fn (TrendPoint $point): int => $point->visitors, $monthTrend))
                ->chartColor('primary'),
            Stat::make('All time', Number::format($allTime->visitors))
                ->description(Number::format($allTime->sessions).' visits, '.Number::format($allTime->pageViews).' page views')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('gray'),
        ];
    }

    /**
     * A stat showing this period's visitors against the equal-length period
     * immediately before it.
     */
    private function comparedStat(
        string $label,
        AnalyticsProvider $analytics,
        Period $period,
        string $comparison,
    ): Stat {
        $current = $analytics->summary($period);
        $previous = $analytics->summary($period->previous());

        $change = PeriodSummary::percentageChange($current->visitors, $previous->visitors);

        return Stat::make($label, Number::format($current->visitors))
            ->description($this->changeDescription($change, $comparison, $current))
            ->descriptionIcon($this->changeIcon($change))
            ->color($this->changeColor($change));
    }

    private function changeDescription(?float $change, string $comparison, PeriodSummary $current): string
    {
        $visits = Number::format($current->sessions).' visits';

        // Growth from a zero baseline has no meaningful percentage, and the
        // comparison labels do not all read grammatically after "no traffic
        // in", so this phrasing stays neutral for every period.
        if ($change === null) {
            return $visits.', nothing to compare with';
        }

        $sign = $change >= 0 ? '+' : '';

        return $visits.', '.$sign.Number::format($change, maxPrecision: 1).'% vs '.$comparison;
    }

    private function changeIcon(?float $change): string
    {
        return match (true) {
            $change === null => 'heroicon-m-minus-small',
            $change > 0 => 'heroicon-m-arrow-trending-up',
            $change < 0 => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus-small',
        };
    }

    private function changeColor(?float $change): string
    {
        return match (true) {
            $change === null => 'gray',
            $change > 0 => 'success',
            $change < 0 => 'danger',
            default => 'gray',
        };
    }

    /**
     * @return array<Stat>
     */
    private function unavailableStats(): array
    {
        return array_map(
            fn (string $label): Stat => Stat::make($label, '—')
                ->description('Analytics unavailable')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray'),
            ['Today', 'This week', 'This month', 'All time'],
        );
    }
}
