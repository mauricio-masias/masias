<?php

namespace Database\Factories;

use App\Models\HomeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeSetting>
 */
class HomeSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hero_headline' => 'Mauricio Masias',
            'hero_tagline' => 'Full-Stack Developer',
            'hero_description' => fake()->sentence(),
            'about_text' => '<p>' . fake()->paragraph() . '</p>',
            'skills' => ['PHP', 'Vue', 'TypeScript'],
            'cv_url' => null,
        ];
    }
}
