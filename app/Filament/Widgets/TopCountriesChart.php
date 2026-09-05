<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\GeoRow;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Filament\Widgets\ChartWidget;

class TopCountriesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '320px';

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return 'Where visitors come from';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        try {
            $rows = app(AnalyticsProvider::class)
                ->topCountries(Period::lastDays((int) ($this->filter ?? 30)), 10);
        } catch (AnalyticsUnavailable $e) {
            report($e);

            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visitors',
                    'data' => array_map(fn (GeoRow $row): int => $row->visitors, $rows),
                    'backgroundColor' => 'rgba(45, 212, 191, 0.55)',
                    'borderColor' => 'rgb(45, 212, 191)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_map(fn (GeoRow $row): string => $row->countryLabel(), $rows),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Horizontal bars, so country names stay readable rather than
            // being rotated under a vertical axis.
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
