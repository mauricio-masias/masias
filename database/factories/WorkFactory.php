<?php

namespace Database\Factories;

use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 999),
            'excerpt' => fake()->sentence(20),
            'description' => implode("\n\n", fake()->paragraphs(3)),
            'image' => null,
            'url' => fake()->url(),
            'tags' => fake()->randomElements(['PHP', 'TypeScript', 'Vue', 'React', 'Next.js', 'Laravel', 'Node.js'], 3),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_featured' => false,
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(['published_at' => null]);
    }
}
