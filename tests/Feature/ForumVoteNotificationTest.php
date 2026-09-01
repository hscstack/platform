<?php

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ForumVoteNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('upvoting a question sends ForumVoteNotification to post author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create(['name' => 'Helpful Voter']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-vote1',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition?',
        'body' => 'Question body here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($voter)->post(route('forum.posts.vote', $post->id), [
        'value' => 1,
    ]);

    Notification::assertSentTo($author, ForumVoteNotification::class, function ($notification) use ($post, $voter) {
        return $notification->item->id === $post->id
            && $notification->voter->id === $voter->id;
    });
});

test('downvoting a question does not trigger ForumVoteNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-downvote',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition?',
        'body' => 'Question body here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($voter)->post(route('forum.posts.vote', $post->id), [
        'value' => -1,
    ]);

    Notification::assertNothingSent();
});

test('author upvoting own post does not trigger ForumVoteNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-self-vote',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition?',
        'body' => 'Question body here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($author)->post(route('forum.posts.vote', $post->id), [
        'value' => 1,
    ]);

    Notification::assertNothingSent();
});

test('upvoting an answer sends ForumVoteNotification to the answer author', function () {
    Notification::fake();

    $postAuthor = User::factory()->create();
    $answerAuthor = User::factory()->create(['name' => 'Answer Genius']);
    $voter = User::factory()->create(['name' => 'Helpful Voter']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-vote-answer',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $postAuthor->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition?',
        'body' => 'Question body here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $answer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $answerAuthor->id,
        'body' => 'Step 1: Resolve vectors into components.',
    ]);

    $this->actingAs($voter)->post(route('forum.answers.vote', $answer->id), [
        'value' => 1,
    ]);

    Notification::assertSentTo($answerAuthor, ForumVoteNotification::class, function ($notification) use ($answer) {
        return $notification->item->id === $answer->id;
    });
});

test('repeatedly toggling or switching vote does not trigger spam notifications', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create(['name' => 'Toggling Voter']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-anti-spam',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Anti-spam test question',
        'body' => 'Question body here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    // 1st initial upvote -> sends notification
    $this->actingAs($voter)->post(route('forum.posts.vote', $post->id), ['value' => 1]);
    Notification::assertSentToTimes($author, ForumVoteNotification::class, 1);

    // 2nd toggle: switch to downvote -> NO notification
    $this->actingAs($voter)->post(route('forum.posts.vote', $post->id), ['value' => -1]);
    Notification::assertSentToTimes($author, ForumVoteNotification::class, 1);

    // 3rd toggle: switch back to upvote -> NO notification (not a brand new vote)
    $this->actingAs($voter)->post(route('forum.posts.vote', $post->id), ['value' => 1]);
    Notification::assertSentToTimes($author, ForumVoteNotification::class, 1);
});
