<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::query()->inRandomOrder()->value('id'),

            'title' => $title,
            'slug' => fake()->unique()->slug(),

            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(5, true),

            'featured_image' => 'https://picsum.photos/800/600?random=' . fake()->numberBetween(1, 1000),

            'is_published' => true,
            'is_featured' => fake()->boolean(20),

            'meta_title' => $title,
            'meta_description' => fake()->sentence(),

            'seo_tags' => implode(',', fake()->words(5)),

            'views' => fake()->numberBetween(0, 1000),
        ];
    }
}
