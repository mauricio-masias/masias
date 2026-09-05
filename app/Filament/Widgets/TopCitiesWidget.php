<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Cities are shown as a table rather than a chart.
 *
 * City names are long and their counts on a small site are close together, so
 * a bar chart would be mostly labels. A table also lets the withheld rows GA4
 * reports as unknown sit honestly alongside the rest.
 */
class TopCitiesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top cities')
            ->description('Last 30 days. GA4 withholds locations for very small groups; those appear as unknown.')
            ->records(fn (): array => $this->rows())
            ->columns([
                TextColumn::make('city')
                    ->label('City')
                    ->weight('medium'),
                TextColumn::make('country')
                    ->label('Country'),
                TextColumn::make('visitors')
                    ->label('Visitors')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('sessions')
                    ->label('Visits')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No location data yet');
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function rows(): array
    {
        try {
            $rows = app(AnalyticsProvider::class)->topCities(Period::lastDays(30), 10);
        } catch (AnalyticsUnavailable $e) {
            report($e);

            return [];
        }

        $records = [];

        foreach (array_values($rows) as $index => $row) {
            $records[$index + 1] = [
                'city' => $row->cityLabel() ?? 'Unknown',
                'country' => $row->countryLabel(),
                'visitors' => $row->visitors,
                'sessions' => $row->sessions,
            ];
        }

        return $records;
    }

}
