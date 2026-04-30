<?php

namespace Tests\Feature;

use App\Models\HomeSetting;
use App\Models\Work;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_returns_ok(): void
    {
        HomeSetting::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_homepage_renders_inertia_home_component(): void
    {
        HomeSetting::factory()->create();

        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page->component('Home'));
    }

    public function test_homepage_shares_settings_prop(): void
    {
        HomeSetting::factory()->create([
            'hero_headline' => 'Test Headline',
            'hero_tagline' => 'Test Tagline',
        ]);

        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('settings')
            ->where('settings.hero_headline', 'Test Headline')
            ->where('settings.hero_tagline', 'Test Tagline')
        );
    }

    public function test_homepage_shows_only_featured_published_works(): void
    {
        HomeSetting::factory()->create();
        $featured = Work::factory()->featured()->create(['published_at' => now()]);
        Work::factory()->create(['published_at' => now(), 'is_featured' => false]);
        Work::factory()->unpublished()->featured()->create();

        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('featuredWorks', 1)
            ->where('featuredWorks.0.id', $featured->id)
        );
    }

    public function test_homepage_creates_default_settings_if_none_exist(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertDatabaseCount('home_settings', 1);
    }
}
