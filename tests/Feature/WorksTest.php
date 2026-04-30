<?php

namespace Tests\Feature;

use App\Models\Work;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorksTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_works_page_returns_ok(): void
    {
        $response = $this->get('/works');

        $response->assertStatus(200);
    }

    public function test_works_page_renders_inertia_works_component(): void
    {
        $response = $this->get('/works');

        $response->assertInertia(fn ($page) => $page->component('Works'));
    }

    public function test_works_page_only_returns_published_works(): void
    {
        $published = Work::factory()->create(['published_at' => now()]);
        Work::factory()->unpublished()->create();

        $response = $this->get('/works');

        $response->assertInertia(fn ($page) => $page
            ->component('Works')
            ->has('works', 1)
            ->where('works.0.id', $published->id)
        );
    }

    public function test_works_page_returns_required_fields(): void
    {
        Work::factory()->create(['published_at' => now()]);

        $response = $this->get('/works');

        $response->assertInertia(fn ($page) => $page
            ->component('Works')
            ->has('works.0', fn ($work) => $work
                ->has('id')
                ->has('title')
                ->has('slug')
                ->has('excerpt')
                ->has('description')
                ->has('image')
                ->has('url')
                ->has('tags')
                ->has('is_featured')
            )
        );
    }

    public function test_works_ordered_by_sort_order(): void
    {
        $second = Work::factory()->create(['published_at' => now(), 'sort_order' => 2]);
        $first = Work::factory()->create(['published_at' => now(), 'sort_order' => 1]);

        $response = $this->get('/works');

        $response->assertInertia(fn ($page) => $page
            ->component('Works')
            ->where('works.0.id', $first->id)
            ->where('works.1.id', $second->id)
        );
    }

    public function test_works_page_returns_empty_array_when_no_works(): void
    {
        $response = $this->get('/works');

        $response->assertInertia(fn ($page) => $page
            ->component('Works')
            ->has('works', 0)
        );
    }
}
