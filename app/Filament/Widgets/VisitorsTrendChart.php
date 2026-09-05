<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\Granularity;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Data\TrendPoint;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Filament\Widgets\ChartWidget;

class VisitorsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '320px';

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return 'Visitors over time';
    }

    public function getDescription(): ?string
    {
        $days = $this->days();

        return Granularity::forLength($days) === Granularity::Weekly
            ? 'Grouped by week. Visitors are counted once per week, so weekly figures are not the sum of their days.'
            : 'Grouped by day.';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last 12 months',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = $this->days();
        $granularity = Granularity::forLength($days);

        try {
            $points = app(AnalyticsProvider::class)->trend(Period::lastDays($days), $granularity);
        } catch (AnalyticsUnavailable $e) {
            report($e);

            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visitors',
                    'data' => array_map(fn (TrendPoint $point): int => $point->visitors, $points),
                    'borderColor' => 'rgb(45, 212, 191)',
                    'backgroundColor' => 'rgba(45, 212, 191, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Visits',
                    'data' => array_map(fn (TrendPoint $point): int => $point->sessions, $points),
                    'borderColor' => 'rgb(148, 163, 184)',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [4, 4],
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(
                fn (TrendPoint $point): string => $granularity === Granularity::Weekly
                    ? 'w/c '.$point->date->format('j M')
                    : $point->date->format('j M'),
                $points,
            ),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Visitor counts are whole people; fractional gridlines
                    // would be meaningless on a low-traffic site.
                    'ticks' => ['precision' => 0],
                ],
            ],
            'maintainAspectRatio' => false,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
        ];
    }

    private function days(): int
    {
        return (int) ($this->filter ?? 30);
    }
}
