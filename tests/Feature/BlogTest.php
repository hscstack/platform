<?php

use App\Mail\BlogNotificationMail;
use App\Models\Blog;
use App\Models\BlogComment;
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

test('guests are redirected when trying to react or comment', function () {
    $blog = Blog::factory()->create(['is_published' => true]);

    $this->post("/blogs/{$blog->slug}/react")->assertRedirect('/login');
    $this->post("/blogs/{$blog->slug}/comments", ['content' => 'Nice!'])->assertRedirect('/login');
});

test('authenticated user can toggle love reaction', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['is_published' => true]);

    // React (Love)
    $this->actingAs($user)
        ->post("/blogs/{$blog->slug}/react")
        ->assertRedirect();

    $this->assertDatabaseHas('blog_reactions', [
        'blog_id' => $blog->id,
        'user_id' => $user->id,
    ]);

    // Un-react (Unlike)
    $this->actingAs($user)
        ->post("/blogs/{$blog->slug}/react")
        ->assertRedirect();

    $this->assertDatabaseMissing('blog_reactions', [
        'blog_id' => $blog->id,
        'user_id' => $user->id,
    ]);
});

test('authenticated user can post and delete own comments', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['is_published' => true]);

    // Post comment
    $this->actingAs($user)
        ->post("/blogs/{$blog->slug}/comments", [
            'content' => 'This is a test comment!',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('blog_comments', [
        'blog_id' => $blog->id,
        'user_id' => $user->id,
        'content' => 'This is a test comment!',
    ]);

    $comment = BlogComment::first();

    // Another user cannot delete
    $otherUser = User::factory()->create();
    $this->actingAs($otherUser)
        ->delete("/blogs/comments/{$comment->id}")
        ->assertForbidden();

    // Owner can delete
    $this->actingAs($user)
        ->delete("/blogs/comments/{$comment->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('blog_comments', [
        'id' => $comment->id,
    ]);
});

test('a user is restricted to 1 comment per blog', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['is_published' => true]);

    // First comment succeeds
    $this->actingAs($user)
        ->post("/blogs/{$blog->slug}/comments", [
            'content' => 'First comment',
        ])
        ->assertSessionHas('success');

    expect($blog->comments()->count())->toBe(1);

    // Second comment by same user fails
    $this->actingAs($user)
        ->post("/blogs/{$blog->slug}/comments", [
            'content' => 'Second duplicate comment attempt',
        ])
        ->assertSessionHas('error');

    expect($blog->comments()->count())->toBe(1);
});

test('blog show page returns live reactive reaction counts and comments', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['is_published' => true]);

    // Initial page load has 0 reactions and 0 comments
    $response = $this->get("/blogs/{$blog->slug}");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Blog/Show')
        ->where('reactionsCount', 0)
        ->has('comments', 0)
    );

    // React
    $this->actingAs($user)->post("/blogs/{$blog->slug}/react");

    // Comment
    $this->actingAs($user)->post("/blogs/{$blog->slug}/comments", [
        'content' => 'Fresh live comment',
    ]);

    // Next get request returns live updated count and comment
    $response = $this->actingAs($user)->get("/blogs/{$blog->slug}");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Blog/Show')
        ->where('reactionsCount', 1)
        ->where('isReacted', true)
        ->has('comments', 1)
    );
});

test('email is queued to blog author when a new comment is posted', function () {
    Mail::fake();

    $author = User::factory()->create(['email' => 'author@example.com', 'receive_emails' => true]);
    $commenter = User::factory()->create(['name' => 'John Reader']);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Sample Article',
        'is_published' => true,
    ]);

    $this->actingAs($commenter)
        ->post("/blogs/{$blog->slug}/comments", [
            'content' => 'Great article!',
        ])
        ->assertSessionHas('success');

    Mail::assertQueued(BlogNotificationMail::class, function ($mail) use ($author) {
        return $mail->hasTo($author->email);
    });
});

test('email is not sent if author comments on their own blog', function () {
    Mail::fake();

    $author = User::factory()->create(['email' => 'author@example.com', 'receive_emails' => true]);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
    ]);

    $this->actingAs($author)
        ->post("/blogs/{$blog->slug}/comments", [
            'content' => 'My own note',
        ])
        ->assertSessionHas('success');

    Mail::assertNothingQueued();
});

test('email is queued to author on milestone reactions', function () {
    Mail::fake();

    $author = User::factory()->create(['email' => 'author@example.com', 'receive_emails' => true]);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Milestone Post',
        'is_published' => true,
    ]);

    $firstUser = User::factory()->create(['name' => 'First Lover']);
    $secondUser = User::factory()->create(['name' => 'Second Lover']);

    // 1st reaction (milestone: 1) -> queues email
    $this->actingAs($firstUser)->post("/blogs/{$blog->slug}/react");
    Mail::assertQueued(BlogNotificationMail::class, 1);

    // 2nd reaction (not milestone) -> does not queue new email
    $this->actingAs($secondUser)->post("/blogs/{$blog->slug}/react");
    Mail::assertQueued(BlogNotificationMail::class, 1);
});

test('email is not sent if author reacts to own blog', function () {
    Mail::fake();

    $author = User::factory()->create(['email' => 'author@example.com', 'receive_emails' => true]);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
    ]);

    $this->actingAs($author)->post("/blogs/{$blog->slug}/react");
    Mail::assertNothingQueued();
});
