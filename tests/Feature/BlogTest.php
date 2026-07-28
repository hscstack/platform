<?php

use App\Models\Blog;
use App\Models\User;

test('published blog pages load and increment views', function () {
    $author = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'slug' => 'platform-testing-blog',
        'is_published' => true,
        'views' => 0,
    ]);

    $response = $this->get("/blogs/{$blog->slug}");

    $response->assertStatus(200);
    $this->assertDatabaseHas('blogs', ['id' => $blog->id, 'views' => 1]);
});
