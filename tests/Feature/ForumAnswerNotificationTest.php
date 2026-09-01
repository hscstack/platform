<?php

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ForumAnswerNotification;
use App\Notifications\ForumReportNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('answering a forum post sends ForumAnswerNotification to post author with mail channel if opted in', function () {
    Notification::fake();

    $author = User::factory()->create(['receive_emails' => true]);
    $responder = User::factory()->create(['name' => 'Helper Student']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition problems?',
        'body' => 'I am struggling with relative velocity in vector physics.',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($responder)->post(route('forum.answers.store', $post->id), [
        'body' => 'Here is the step-by-step solution.',
    ]);

    Notification::assertSentTo($author, ForumAnswerNotification::class, function ($notification) use ($post, $author) {
        $channels = $notification->via($author);

        return $notification->post->id === $post->id
            && $notification->isReply === false
            && in_array('database', $channels, true)
            && in_array('mail', $channels, true);
    });
});

test('sub-reply notification is strictly database only and does not send mail', function () {
    $author = User::factory()->create(['receive_emails' => true]);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-channels',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Sample post title',
        'body' => 'Post body text here',
    ]);

    $answer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $author->id,
        'body' => 'Reply content',
    ]);

    $replyNotification = new ForumAnswerNotification($post, $answer, isReply: true);
    expect($replyNotification->via($author))->toBe(['database']);

    $answerNotification = new ForumAnswerNotification($post, $answer, isReply: false);
    expect($answerNotification->via($author))->toBe(['database', 'mail']);
});

test('author answering own post does not trigger notification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition problems?',
        'body' => 'I am struggling with relative velocity in vector physics.',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($author)->post(route('forum.answers.store', $post->id), [
        'body' => 'Adding more details to my question.',
    ]);

    Notification::assertNothingSent();
});

test('replying to an answer sends ForumAnswerNotification to the parent answer author', function () {
    Notification::fake();

    $postAuthor = User::factory()->create();
    $answerAuthor = User::factory()->create(['name' => 'Answer Author']);
    $replyAuthor = User::factory()->create(['name' => 'Reply User']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $postAuthor->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition problems?',
        'body' => 'I am struggling with relative velocity in vector physics.',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $parentAnswer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $answerAuthor->id,
        'body' => 'First step is vector resolution.',
    ]);

    $this->actingAs($replyAuthor)->post(route('forum.answers.store', $post->id), [
        'body' => 'Thanks, this helped!',
        'parent_id' => $parentAnswer->id,
    ]);

    Notification::assertSentTo($answerAuthor, ForumAnswerNotification::class, function ($notification) use ($post) {
        return $notification->post->id === $post->id
            && $notification->isReply === true;
    });
});

test('replying with @mention sends ForumAnswerNotification to the mentioned user', function () {
    Notification::fake();

    $postAuthor = User::factory()->create();
    $rootAuthor = User::factory()->create(['username' => 'root_author']);
    $mentionedUser = User::factory()->create(['username' => 'sub_replier']);
    $replyAuthor = User::factory()->create(['username' => 'replier_user']);

    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-mentions',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $postAuthor->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition problems?',
        'body' => 'I am struggling with relative velocity in vector physics.',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $parentAnswer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $rootAuthor->id,
        'body' => 'First step is vector resolution.',
    ]);

    // Replying with @sub_replier mention
    $this->actingAs($replyAuthor)->post(route('forum.answers.store', $post->id), [
        'body' => '@sub_replier I agree with your point here.',
        'parent_id' => $parentAnswer->id,
    ]);

    // Notification is sent to @sub_replier
    Notification::assertSentTo($mentionedUser, ForumAnswerNotification::class, function ($notification) use ($post) {
        return $notification->post->id === $post->id
            && $notification->isReply === true;
    });

    // Root author is NOT sent the notification since @mention took priority
    Notification::assertNotSentTo($rootAuthor, ForumAnswerNotification::class);
});

test('reporting a forum answer sends ForumReportNotification to users with manage forums permission', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create(['username' => 'reporter_student']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $reporter->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'How to solve vector addition problems?',
        'body' => 'I am struggling with relative velocity in vector physics.',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $answer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $reporter->id,
        'body' => 'Inappropriate answer content',
    ]);

    $this->actingAs($reporter)->postJson(route('forum.answers.report', $answer->id), [
        'reason' => 'Off-topic or spam',
    ])->assertStatus(201);

    Notification::assertSentTo($admin, ForumReportNotification::class, function ($notification) use ($reporter) {
        return $notification->reporter->id === $reporter->id
            && $notification->report->reportable_id !== null;
    });
});
