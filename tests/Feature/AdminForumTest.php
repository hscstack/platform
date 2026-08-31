<?php

use App\Models\AppSetting;
use App\Models\ChatMessage;
use App\Models\ForumPost;
use App\Models\Report;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage forums', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('user without manage forums permission cannot access admin forums index', function () {
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)
        ->get(route('admin.forums.index'));

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('admin with manage forums permission can access forum admin index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = ForumPost::factory()->create(['title' => 'Test Discussion']);

    $response = $this->actingAs($admin)
        ->get(route('admin.forums.index'));

    $response->assertStatus(200);
});

test('admin can toggle lock status on forum post', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = ForumPost::factory()->create(['is_locked' => false]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.forums.lock', $post));

    $response->assertRedirect();
    expect($post->fresh()->is_locked)->toBeTrue();

    // Toggle back
    $this->actingAs($admin)
        ->patch(route('admin.forums.lock', $post));
    expect($post->fresh()->is_locked)->toBeFalse();
});

test('locked post rejects new answers from regular users', function () {
    $user = User::factory()->create();
    $post = ForumPost::factory()->create(['is_locked' => true]);

    $response = $this->actingAs($user)
        ->post(route('forum.answers.store', $post), [
            'body' => 'Trying to answer locked discussion',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($post->answers()->count())->toBe(0);
});

test('admin can update moderation status directly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = ForumPost::factory()->create(['moderation_status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.forums.update-status', $post), [
            'moderation_status' => 'approved',
        ])
        ->assertRedirect();

    expect($post->fresh()->moderation_status)->toBe('approved');

    $this->actingAs($admin)
        ->patch(route('admin.forums.update-status', $post), [
            'moderation_status' => 'flagged',
        ])
        ->assertRedirect();

    expect($post->fresh()->moderation_status)->toBe('flagged');
});

test('unpublished post is not visible on public forum index and returns 404 for regular users', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $publishedPost = ForumPost::factory()->create(['title' => 'Visible Post', 'moderation_status' => 'approved']);
    $hiddenPost = ForumPost::factory()->create(['user_id' => $author->id, 'title' => 'Hidden Post', 'moderation_status' => 'rejected']);

    // Index only returns published
    $response = $this->actingAs($user)->get(route('forum.index'));
    $response->assertStatus(200);

    // Show endpoint returns 404 for regular non-author user on rejected post
    $hiddenResponse = $this->actingAs($user)->get(route('forum.show', $hiddenPost));
    $hiddenResponse->assertSee('errors\/404');
    $this->actingAs($user)->get(route('forum.show', $publishedPost))->assertStatus(200);
});

test('admin can update forum settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->post(route('admin.forums.settings.update'), [
            'approval_mode' => 'manual',
            'posting_enabled' => false,
            'comments_enabled' => false,
            'disabled_reason' => 'Paused for exam period',
            'auto_unpublish_threshold' => 5,
            'profanity_filter_enabled' => true,
            'banned_words' => 'toxic, spam',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(AppSetting::get('forum_approval_mode'))->toBe('manual');
    expect(AppSetting::get('forum_posting_enabled'))->toBeFalse();
    expect(AppSetting::get('forum_comments_enabled'))->toBeFalse();
    expect(AppSetting::get('forum_disabled_reason'))->toBe('Paused for exam period');
    expect(AppSetting::get('forum_auto_unpublish_threshold'))->toBe(5);
});

test('creating post is rejected when posting_enabled is false', function () {
    AppSetting::set('forum_posting_enabled', false, 'boolean');
    AppSetting::set('forum_disabled_reason', 'Posting paused', 'string');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('forum.store'), [
            'title' => 'New question title here',
            'body' => 'Detailed body of the question here',
            'curriculum' => 'hsc',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Posting paused');
});

test('reporting post multiple times triggers auto-unpublish when threshold is reached', function () {
    AppSetting::set('forum_auto_unpublish_threshold', 3, 'integer');

    $author = User::factory()->create();
    $post = ForumPost::factory()->create([
        'user_id' => $author->id,
        'moderation_status' => 'approved',
    ]);

    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();
    $reporter3 = User::factory()->create();

    // Report 1
    $this->actingAs($reporter1)
        ->postJson(route('forum.posts.report', $post), ['reason' => 'Spam'])
        ->assertStatus(201);
    expect($post->fresh()->moderation_status)->toBe('approved');

    // Report 2
    $this->actingAs($reporter2)
        ->postJson(route('forum.posts.report', $post), ['reason' => 'Spam'])
        ->assertStatus(201);
    expect($post->fresh()->moderation_status)->toBe('approved');

    // Report 3 (Hits threshold of 3)
    $this->actingAs($reporter3)
        ->postJson(route('forum.posts.report', $post), ['reason' => 'Spam'])
        ->assertStatus(201);
    expect($post->fresh()->moderation_status)->toBe('flagged');
});

test('admin can manage forum reports status and delete reports', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = ForumPost::factory()->create();
    $reporter = User::factory()->create();

    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => ForumPost::class,
        'reportable_id' => $post->id,
        'content_snapshot' => $post->title,
        'reason' => 'Inappropriate',
        'status' => 'pending',
    ]);

    // View reports
    $this->actingAs($admin)
        ->get(route('admin.forums.reports.index'))
        ->assertStatus(200);

    // Update status
    $this->actingAs($admin)
        ->patch(route('admin.forums.reports.update-status', $report), ['status' => 'reviewed'])
        ->assertRedirect();
    expect($report->fresh()->status)->toBe('reviewed');

    // Delete report
    $this->actingAs($admin)
        ->delete(route('admin.forums.reports.destroy', $report))
        ->assertRedirect();
    expect(Report::find($report->id))->toBeNull();
});

test('admin can toggle lock and delete using post id route binding', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = ForumPost::factory()->create([
        'title' => 'Sample Post Title',
        'slug' => 'sample-post-slug',
        'is_locked' => false,
    ]);

    $reporter = User::factory()->create();
    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => ForumPost::class,
        'reportable_id' => $post->id,
        'content_snapshot' => $post->title,
        'reason' => 'Spam',
        'status' => 'pending',
    ]);

    // Send direct URL with numeric ID
    $this->actingAs($admin)
        ->patch("/admin/forums/{$post->id}/lock")
        ->assertRedirect();
    expect($post->fresh()->is_locked)->toBeTrue();

    // Delete with numeric ID deletes post while preserving report for audit trail
    $this->actingAs($admin)
        ->delete("/admin/forums/{$post->id}")
        ->assertRedirect();
    expect(ForumPost::find($post->id))->toBeNull();
    expect(Report::find($report->id))->not->toBeNull();
});

test('admin cannot mutate non-forum reports through forum admin endpoints', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create();
    $chatReport = Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => ChatMessage::class,
        'reportable_id' => 999,
        'content_snapshot' => 'Chat message',
        'reason' => 'Spam',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.forums.reports.update-status', $chatReport), ['status' => 'reviewed'])
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete(route('admin.forums.reports.destroy', $chatReport))
        ->assertNotFound();
});

test('admin can update auto unpublish threshold to zero to disable it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.forums.settings.update'), [
            'approval_mode' => 'auto',
            'posting_enabled' => true,
            'comments_enabled' => true,
            'disabled_reason' => '',
            'auto_unpublish_threshold' => 0,
            'profanity_filter_enabled' => true,
            'banned_words' => '',
        ])
        ->assertRedirect();

    expect(AppSetting::get('forum_auto_unpublish_threshold'))->toBe(0);
});

test('moderator with only manage forums permission can suspend a user', function () {
    $forumMod = User::factory()->create();
    $forumMod->givePermissionTo('view admin');
    $forumMod->givePermissionTo('manage forums');

    $targetUser = User::factory()->create();
    $banDate = now()->addDays(3)->toIso8601String();

    $this->actingAs($forumMod)
        ->post(route('admin.chat.users.ban', $targetUser), [
            'banned_until' => $banDate,
        ])
        ->assertRedirect();

    expect($targetUser->fresh()->isBanned())->toBeTrue();
});

test('user without manage chat or manage forums permission cannot suspend a user', function () {
    $regularAdminUser = User::factory()->create();
    $regularAdminUser->givePermissionTo('view admin');

    $targetUser = User::factory()->create();

    $this->actingAs($regularAdminUser)
        ->post(route('admin.chat.users.ban', $targetUser), [
            'banned_until' => now()->addDays(3)->toIso8601String(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($targetUser->fresh()->isBanned())->toBeFalse();
});
