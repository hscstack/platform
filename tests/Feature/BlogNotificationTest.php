<?php

use App\Models\Blog;
use App\Models\User;
use App\Notifications\BlogCommentNotification;
use App\Notifications\BlogReactionNotification;
use Illuminate\Support\Facades\Notification;

test('reacting to a blog triggers BlogReactionNotification to the blog author', function () {
    Notification::fake();

    $author = User::factory()->create(['name' => 'Article Author']);
    $reactor = User::factory()->create(['name' => 'Fan User']);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Understanding Quantum Mechanics',
        'is_published' => true,
    ]);

    $this->actingAs($reactor)->post("/blogs/{$blog->slug}/react");

    Notification::assertSentTo($author, BlogReactionNotification::class, function ($notification) use ($blog, $reactor) {
        return $notification->blog->id === $blog->id
            && $notification->reactor->id === $reactor->id
            && $notification->reactionsCount === 1;
    });
});

test('author reacting to own blog does not trigger BlogReactionNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
    ]);

    $this->actingAs($author)->post("/blogs/{$blog->slug}/react");

    Notification::assertNothingSent();
});

test('BlogReactionNotification delivers to database channel only', function () {
    $author = User::factory()->create();
    $reactor = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id]);

    $notif = new BlogReactionNotification($blog, $reactor, 1);
    expect($notif->via($author))->toBe(['database']);
});

test('multiple reactions on a blog aggregate into a single unread notification with rolled up titles', function () {
    $author = User::factory()->create();
    $reactor1 = User::factory()->create(['name' => 'Alice']);
    $reactor2 = User::factory()->create(['name' => 'Bob']);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Calculus Basics',
        'is_published' => true,
    ]);

    // 1st reaction
    $this->actingAs($reactor1)->post("/blogs/{$blog->slug}/react");
    expect($author->unreadNotifications()->count())->toBe(1);

    $notif = $author->unreadNotifications()->first();
    expect($notif->data['title'])->toBe('Alice loved your blog post')
        ->and($notif->data['count'])->toBe(1);

    // 2nd reaction
    $this->actingAs($reactor2)->post("/blogs/{$blog->slug}/react");
    expect($author->unreadNotifications()->count())->toBe(1);

    $notif->refresh();
    expect($notif->data['title'])->toBe('Bob and 1 other person loved your blog post')
        ->and($notif->data['count'])->toBe(2);
});

test('commenting on a blog triggers BlogCommentNotification to the blog author', function () {
    Notification::fake();

    $author = User::factory()->create(['name' => 'Post Author']);
    $commenter = User::factory()->create(['name' => 'Commenter Rahim']);
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Chemistry Notes Chapter 1',
        'is_published' => true,
    ]);

    $this->actingAs($commenter)->post("/blogs/{$blog->slug}/comments", [
        'content' => 'Great notes, thank you!',
    ]);

    Notification::assertSentTo($author, BlogCommentNotification::class, function ($notification) use ($blog) {
        return $notification->blog->id === $blog->id
            && $notification->comment->content === 'Great notes, thank you!';
    });
});

test('comment notification is strictly database only and never routes to mail', function () {
    $author = User::factory()->create(['receive_emails' => true]);
    $blog = Blog::factory()->create(['user_id' => $author->id]);
    $comment = $blog->comments()->create([
        'user_id' => $author->id,
        'content' => 'Test comment content',
    ]);

    $notification = new BlogCommentNotification($blog, $comment);
    $channels = $notification->via($author);

    expect($channels)->toContain('database')
        ->and($channels)->not->toContain('mail');
});

test('author commenting on own blog does not trigger BlogCommentNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
    ]);

    $this->actingAs($author)->post("/blogs/{$blog->slug}/comments", [
        'content' => 'Self note comment',
    ]);

    Notification::assertNothingSent();
});
