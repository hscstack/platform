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

test('BlogReactionNotification delivers to mail only on milestone count and when opted in', function () {
    $authorSubscribed = User::factory()->create(['receive_emails' => true]);
    $authorUnsubscribed = User::factory()->create(['receive_emails' => false]);
    $reactor = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $authorSubscribed->id]);

    // 1st reaction (milestone: 1) -> database + mail
    $notif1 = new BlogReactionNotification($blog, $reactor, 1);
    expect($notif1->isMilestone())->toBeTrue()
        ->and($notif1->via($authorSubscribed))->toContain('database', 'mail')
        ->and($notif1->via($authorUnsubscribed))->toBe(['database']);

    // 2nd reaction (not milestone) -> database only
    $notif2 = new BlogReactionNotification($blog, $reactor, 2);
    expect($notif2->isMilestone())->toBeFalse()
        ->and($notif2->via($authorSubscribed))->toBe(['database']);

    // 10th reaction (milestone: 10) -> database + mail
    $notif10 = new BlogReactionNotification($blog, $reactor, 10);
    expect($notif10->isMilestone())->toBeTrue()
        ->and($notif10->via($authorSubscribed))->toContain('database', 'mail');
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

    expect($channels)->toBe(['database'])
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
