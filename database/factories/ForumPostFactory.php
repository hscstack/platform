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
        $body = fake()->paragraphs(2, true);

        return [
            'user_id' => User::factory(),
            'curriculum' => fake()->randomElement(['hsc', 'ssc']),
            'slug' => Str::slug(Str::words($body, 8, '')).'-'.fake()->unique()->randomNumber(5),
            'body' => $body,
            'is_locked' => false,
            'moderation_status' => 'approved',
            'vote_score' => 0,
            'answers_count' => 0,
        ];
    }
}
