<?php

use App\Models\AppSetting;
use App\Models\ForumPost;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ForumReportNotification;
use App\Notifications\ForumStatusNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('reporting a question sends ForumReportNotification to users with manage forums permission', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create(['username' => 'question_reporter']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-reports',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $reporter->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Sample reported question',
        'body' => 'Question body content here',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($reporter)->postJson(route('forum.posts.report', $post->id), [
        'reason' => 'Duplicate question',
    ])->assertStatus(201);

    Notification::assertSentTo($admin, ForumReportNotification::class, function ($notification) use ($reporter) {
        return $notification->reporter->id === $reporter->id
            && $notification->report->reportable_id !== null;
    });
});

test('admin locking discussion sends ForumStatusNotification to author', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-lock',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Question to be locked',
        'body' => 'Question body content',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($admin)->patch(route('admin.forums.lock', $post->id));

    Notification::assertSentTo($author, ForumStatusNotification::class, function ($notification) use ($post) {
        return $notification->post->id === $post->id
            && $notification->status === 'locked';
    });
});

test('admin updating moderation status sends ForumStatusNotification to author', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-status',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Question pending approval',
        'body' => 'Question body content',
        'moderation_status' => 'pending',
        'is_locked' => false,
    ]);

    $this->actingAs($admin)->patch(route('admin.forums.update-status', $post->id), [
        'moderation_status' => 'approved',
    ]);

    Notification::assertSentTo($author, ForumStatusNotification::class, function ($notification) use ($post) {
        return $notification->post->id === $post->id
            && $notification->status === 'approved';
    });
});

test('auto unpublishing a question on report threshold sends ForumStatusNotification to author', function () {
    Notification::fake();

    AppSetting::set('forum_auto_unpublish_threshold', 2, 'integer');

    $author = User::factory()->create(['receive_emails' => true]);
    $reporter1 = User::factory()->create(['username' => 'reporter_1']);
    $reporter2 = User::factory()->create(['username' => 'reporter_2']);

    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-auto-flag',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Spam question title',
        'body' => 'Spam body content',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    // 1st report
    $this->actingAs($reporter1)->postJson(route('forum.posts.report', $post->id), ['reason' => 'Spam']);
    expect($post->fresh()->moderation_status)->toBe('approved');

    // 2nd report hits threshold
    $this->actingAs($reporter2)->postJson(route('forum.posts.report', $post->id), ['reason' => 'Spam']);
    expect($post->fresh()->moderation_status)->toBe('flagged');

    Notification::assertSentTo($author, ForumStatusNotification::class, function ($notification) use ($post, $author) {
        $channels = $notification->via($author);

        return $notification->post->id === $post->id
            && $notification->status === 'flagged'
            && in_array('mail', $channels, true);
    });
});

test('ForumStatusNotification delivers to mail for approval, flagged, rejected and database only for locked', function () {
    $author = User::factory()->create(['receive_emails' => true]);
    $optOutAuthor = User::factory()->create(['receive_emails' => false]);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-channels2',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'title' => 'Channel test post',
        'body' => 'Post body',
    ]);

    $approvedNotif = new ForumStatusNotification($post, 'approved');
    expect($approvedNotif->via($author))->toBe(['database', 'mail'])
        ->and($approvedNotif->via($optOutAuthor))->toBe(['database']);

    $flaggedNotif = new ForumStatusNotification($post, 'flagged');
    expect($flaggedNotif->via($author))->toBe(['database', 'mail']);

    $rejectedNotif = new ForumStatusNotification($post, 'rejected');
    expect($rejectedNotif->via($author))->toBe(['database', 'mail']);

    $lockedNotif = new ForumStatusNotification($post, 'locked');
    expect($lockedNotif->via($author))->toBe(['database']);
});
