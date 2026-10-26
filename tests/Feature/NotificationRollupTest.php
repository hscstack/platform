<?php

use App\Models\Blog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\BlogCommentNotification;
use App\Notifications\ForumAnswerNotification;
use App\Notifications\ResourceCommentNotification;

test('resource comment notifications roll up when multiple comments are posted', function () {
    $author = User::factory()->create();
    $commenter1 = User::factory()->create(['name' => 'Alice']);
    $commenter2 = User::factory()->create(['name' => 'Bob']);

    $resource = Resource::create([
        'user_id' => $author->id,
        'title' => 'Physics Note',
        'resource_type' => 'note',
        'status' => 'approved',
    ]);

    // First comment
    $comment1 = $resource->comments()->create([
        'user_id' => $commenter1->id,
        'content' => 'First comment',
    ]);
    ResourceCommentNotification::sendTo($author, $resource, $comment1);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif1 = $author->unreadNotifications()->first();
    expect($notif1->data['count'])->toBe(1);
    expect($notif1->data['title'])->toContain('New comment on "Physics Note"');

    // Second comment from another user
    $comment2 = $resource->comments()->create([
        'user_id' => $commenter2->id,
        'content' => 'Second comment',
    ]);
    ResourceCommentNotification::sendTo($author, $resource, $comment2);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif2 = $author->fresh()->unreadNotifications()->first();
    expect($notif2->data['count'])->toBe(2);
    expect($notif2->data['title'])->toBe('Bob and 1 other person commented on your resource');
});

test('blog comment notifications roll up when multiple comments are posted', function () {
    $author = User::factory()->create();
    $commenter1 = User::factory()->create(['name' => 'Alice']);
    $commenter2 = User::factory()->create(['name' => 'Bob']);

    $blog = Blog::factory()->create([
        'user_id' => $author->id,
        'title' => 'Study Strategy',
    ]);

    $comment1 = $blog->comments()->create([
        'user_id' => $commenter1->id,
        'content' => 'First comment',
    ]);
    BlogCommentNotification::sendTo($author, $blog, $comment1);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif1 = $author->unreadNotifications()->first();
    expect($notif1->data['count'])->toBe(1);

    $comment2 = $blog->comments()->create([
        'user_id' => $commenter2->id,
        'content' => 'Second comment',
    ]);
    BlogCommentNotification::sendTo($author, $blog, $comment2);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif2 = $author->fresh()->unreadNotifications()->first();
    expect($notif2->data['count'])->toBe(2);
    expect($notif2->data['title'])->toBe('Bob and 1 other person commented on your blog');
});

test('forum answer notifications roll up when multiple answers are posted', function () {
    $author = User::factory()->create();
    $answerer1 = User::factory()->create(['name' => 'Alice']);
    $answerer2 = User::factory()->create(['name' => 'Bob']);

    $post = ForumPost::factory()->create([
        'user_id' => $author->id,
        'body' => 'Need help with math question',
    ]);

    $answer1 = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $answerer1->id,
        'body' => 'First answer',
    ]);
    ForumAnswerNotification::sendTo($author, $post, $answer1, false);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif1 = $author->unreadNotifications()->first();
    expect($notif1->data['count'])->toBe(1);

    $answer2 = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $answerer2->id,
        'body' => 'Second answer',
    ]);
    ForumAnswerNotification::sendTo($author, $post, $answer2, false);

    expect($author->unreadNotifications()->count())->toBe(1);
    $notif2 = $author->fresh()->unreadNotifications()->first();
    expect($notif2->data['count'])->toBe(2);
    expect($notif2->data['title'])->toContain('Bob and 1 other person answered your question');
});
