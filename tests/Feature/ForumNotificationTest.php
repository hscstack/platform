<?php

use App\Models\AppSetting;
use App\Models\ForumPost;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ForumQuestionPendingNotification;
use App\Notifications\ForumReportNotification;
use App\Notifications\ForumStatusNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

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
        'slug' => 'test-slug-'.Str::random(6),
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
        'slug' => 'test-slug-'.Str::random(6),
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
        'slug' => 'test-slug-'.Str::random(6),
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

test('admin updating moderation status to same status does not trigger duplicate notification', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-same-status',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Question body content',
        'moderation_status' => 'approved',
        'is_locked' => false,
    ]);

    $this->actingAs($admin)->patch(route('admin.forums.update-status', $post->id), [
        'moderation_status' => 'approved',
    ]);

    Notification::assertNothingSent();
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
        'slug' => 'test-slug-'.Str::random(6),
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
            && $channels === ['database'];
    });
});

test('ForumStatusNotification delivers to database channel only', function () {
    $author = User::factory()->create(['receive_emails' => true]);
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
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Post body',
    ]);

    $approvedNotif = new ForumStatusNotification($post, 'approved');
    expect($approvedNotif->via($author))->toBe(['database']);

    $flaggedNotif = new ForumStatusNotification($post, 'flagged');
    expect($flaggedNotif->via($author))->toBe(['database']);

    $rejectedNotif = new ForumStatusNotification($post, 'rejected');
    expect($rejectedNotif->via($author))->toBe(['database']);

    $lockedNotif = new ForumStatusNotification($post, 'locked');
    expect($lockedNotif->via($author))->toBe(['database']);
});

test('submitting a question when approval mode is manual sends ForumQuestionPendingNotification to moderators', function () {
    Notification::fake();

    AppSetting::set('forum_approval_mode', 'manual', 'string');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $author = User::factory()->create(['name' => 'Question Author']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-manual',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $this->actingAs($author)->post(route('forum.store'), [
        'body' => 'Here is my detailed physics inquiry that requires approval.',
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
    ]);

    Notification::assertSentTo($admin, ForumQuestionPendingNotification::class, function ($notification) use ($author) {
        return $notification->author->id === $author->id
            && str_contains($notification->post->body, 'Here is my detailed physics inquiry');
    });
});
