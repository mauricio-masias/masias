<?php

namespace Tests\Feature;

use App\Filament\Widgets\TopCitiesWidget;
use App\Filament\Widgets\TopCountriesChart;
use App\Filament\Widgets\VisitorsTrendChart;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Data\Period;
use App\Services\Analytics\FakeAnalyticsProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsChartsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(AnalyticsProvider::class, new FakeAnalyticsProvider);
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    }

    private function chartData(object $component): array
    {
        return (fn () => $this->getData())->call($component);
    }

    public function test_trend_chart_defaults_to_thirty_daily_points(): void
    {
        $data = $this->chartData(Livewire::test(VisitorsTrendChart::class)->instance());

        $this->assertCount(30, $data['labels']);
        $this->assertCount(2, $data['datasets']);
        $this->assertSame('Visitors', $data['datasets'][0]['label']);
        $this->assertSame('Visits', $data['datasets'][1]['label']);
        $this->assertCount(30, $data['datasets'][0]['data']);
    }

    public function test_trend_chart_switches_to_weekly_buckets_for_long_ranges(): void
    {
        $component = Livewire::test(VisitorsTrendChart::class)
            ->set('filter', '365');

        $data = $this->chartData($component->instance());

        // A year of daily points would be unreadable, and summing days into
        // weeks would overcount repeat visitors, so the series is requested
        // as weeks.
        $this->assertGreaterThan(50, count($data['labels']));
        $this->assertLessThan(60, count($data['labels']));
        $this->assertStringStartsWith('w/c ', $data['labels'][0]);
    }

    public function test_weekly_chart_does_not_plot_a_partial_leading_week(): void
    {
        $component = Livewire::test(VisitorsTrendChart::class)->set('filter', '365');
        $data = $this->chartData($component->instance());

        $visitors = $data['datasets'][0]['data'];
        $rest = array_slice($visitors, 1, 8);

        // The requested year starts mid-week. Asking GA4 for that raw range
        // returns only the days inside it, plotted under the whole week's
        // label, which draws a dip that never happened.
        $this->assertGreaterThan(
            max($rest) / 2,
            $visitors[0],
            'leading week looks truncated rather than whole',
        );
    }

    public function test_trend_chart_stays_daily_at_ninety_days(): void
    {
        $component = Livewire::test(VisitorsTrendChart::class)->set('filter', '90');

        $data = $this->chartData($component->instance());

        $this->assertCount(90, $data['labels']);
        $this->assertStringNotContainsString('w/c', $data['labels'][0]);
    }

    public function test_trend_chart_renders_empty_when_analytics_is_down(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        $data = $this->chartData(Livewire::test(VisitorsTrendChart::class)->instance());

        $this->assertSame([], $data['datasets']);
        $this->assertSame([], $data['labels']);
    }

    public function test_countries_chart_lists_countries_by_visitors(): void
    {
        $data = $this->chartData(Livewire::test(TopCountriesChart::class)->instance());

        $this->assertSame('United Kingdom', $data['labels'][0]);
        $this->assertContains('Unknown', $data['labels']);
        $this->assertSame(412, $data['datasets'][0]['data'][0]);
    }

    public function test_countries_chart_supports_a_today_filter(): void
    {
        $component = Livewire::test(TopCountriesChart::class)->set('filter', '1');
        $data = $this->chartData($component->instance());

        $this->assertNotEmpty($data['labels']);
        $this->assertSame('United Kingdom', $data['labels'][0]);
    }

    public function test_today_filter_asks_for_a_single_day(): void
    {
        // The filter value is a day count, so "Today" must resolve to a
        // one-day period rather than silently widening the range.
        $period = Period::lastDays(1);

        $this->assertSame($period->startDate(), $period->endDate());
        $this->assertSame(1, $period->lengthInDays());
    }

    public function test_countries_chart_renders_horizontally(): void
    {
        $options = (fn () => $this->getOptions())->call(
            Livewire::test(TopCountriesChart::class)->instance(),
        );

        $this->assertSame('y', $options['indexAxis']);
    }

    public function test_countries_chart_survives_an_outage(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        Livewire::test(TopCountriesChart::class)->assertOk();
    }

    public function test_cities_table_renders_rows(): void
    {
        Livewire::test(TopCitiesWidget::class)
            ->assertOk()
            ->assertSee('London')
            ->assertSee('Manchester')
            ->assertSee('Top cities');
    }

    public function test_cities_table_labels_withheld_locations_as_unknown(): void
    {
        Livewire::test(TopCitiesWidget::class)
            ->assertOk()
            ->assertSee('Unknown')
            ->assertDontSee('(not set)');
    }

    public function test_cities_table_survives_an_outage(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        Livewire::test(TopCitiesWidget::class)->assertOk();
    }

    public function test_all_widgets_are_registered_on_the_dashboard(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertContains(VisitorsTrendChart::class, $widgets);
        $this->assertContains(TopCountriesChart::class, $widgets);
        $this->assertContains(TopCitiesWidget::class, $widgets);
    }

    public function test_dashboard_loads_with_every_widget(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_charts_do_not_poll(): void
    {
        foreach ([VisitorsTrendChart::class, TopCountriesChart::class] as $widget) {
            $instance = Livewire::test($widget)->instance();

            $this->assertNull(
                (fn () => $this->getPollingInterval())->call($instance),
                $widget.' must not poll a quota-limited API',
            );
        }
    }
}
