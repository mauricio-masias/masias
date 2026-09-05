<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalyticsOverviewWidget;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\FakeAnalyticsProvider;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsWidgetTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(AnalyticsProvider::class, new FakeAnalyticsProvider);
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    }

    public function test_widget_renders_all_four_periods(): void
    {
        Livewire::test(AnalyticsOverviewWidget::class)
            ->assertOk()
            ->assertSee('Today')
            ->assertSee('This week')
            ->assertSee('This month')
            ->assertSee('All time');
    }

    public function test_widget_shows_visitor_counts(): void
    {
        Livewire::test(AnalyticsOverviewWidget::class)
            ->assertOk()
            ->assertSee('visits');
    }

    public function test_widget_degrades_instead_of_failing_when_analytics_is_down(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        Livewire::test(AnalyticsOverviewWidget::class)
            ->assertOk()
            ->assertSee('Analytics unavailable')
            ->assertSee('Today');
    }

    public function test_dashboard_loads_with_the_widget(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_dashboard_still_loads_when_analytics_is_down(): void
    {
        $this->app->instance(AnalyticsProvider::class, (new FakeAnalyticsProvider)->fail());

        // The dashboard is the admin landing page; an analytics outage must
        // never lock the user out of the rest of the panel.
        $this->get('/admin')->assertOk();
    }

    public function test_default_filament_widgets_are_not_registered(): void
    {
        // Asserted against panel registration rather than rendered markup,
        // because "Filament" legitimately appears in asset paths and classes.
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
    }

    public function test_analytics_widget_is_registered(): void
    {
        $this->assertContains(
            AnalyticsOverviewWidget::class,
            Filament::getPanel('admin')->getWidgets(),
        );
    }

    public function test_widget_does_not_poll(): void
    {
        // Polling a quota-limited API every few seconds would burn the daily
        // budget without ever showing fresher numbers.
        $widget = Livewire::test(AnalyticsOverviewWidget::class)->instance();

        $this->assertNull(
            (fn () => $this->getPollingInterval())->call($widget),
        );
    }
}
