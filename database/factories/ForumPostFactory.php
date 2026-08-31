<?php

namespace Database\Factories;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumPost>
 */
class ForumPostFactory extends Factory
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
            'user_id' => User::factory(),
            'curriculum' => fake()->randomElement(['hsc', 'ssc']),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'body' => fake()->paragraphs(2, true),
            'is_answered' => false,
            'is_locked' => false,
            'is_published' => true,
            'vote_score' => 0,
            'answers_count' => 0,
        ];
    }
}
