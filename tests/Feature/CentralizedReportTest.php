<?php

use App\Models\Report;
use App\Models\Resource;
use App\Models\ResourceComment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('authenticated user can report a resource comment', function () {
    $reporter = User::factory()->create();
    $author = User::factory()->create();
    $subject = Subject::create(['name' => 'Physics', 'course' => 'hsc']);
    $resource = Resource::create([
        'subject_id' => $subject->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'items' => [['format' => 'image', 'source' => 'test.png']],
        'status' => 'approved',
    ]);

    $comment = ResourceComment::create([
        'resource_id' => $resource->id,
        'user_id' => $author->id,
        'content' => 'Inappropriate comment content',
    ]);

    $response = $this->actingAs($reporter)->postJson('/reports', [
        'reportable_type' => 'resource_comment',
        'reportable_id' => $comment->id,
        'reason' => 'Harassment, bullying or threats',
        'details' => 'Offensive remark to another student',
    ]);

    $response->assertStatus(201);
    expect(Report::count())->toBe(1);

    $report = Report::first();
    expect($report->reportable_type)->toBe(ResourceComment::class)
        ->and($report->reportable_id)->toBe($comment->id)
        ->and($report->reported_user_id)->toBe($author->id)
        ->and($report->content_snapshot)->toBe('Inappropriate comment content')
        ->and($report->details)->toBe('Offensive remark to another student')
        ->and($report->status)->toBe('pending');
});

test('authenticated user cannot report their own comment', function () {
    $user = User::factory()->create();
    $subject = Subject::create(['name' => 'Physics', 'course' => 'hsc']);
    $resource = Resource::create([
        'subject_id' => $subject->id,
        'user_id' => $user->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'items' => [['format' => 'image', 'source' => 'test.png']],
        'status' => 'approved',
    ]);

    $comment = ResourceComment::create([
        'resource_id' => $resource->id,
        'user_id' => $user->id,
        'content' => 'My own comment',
    ]);

    $response = $this->actingAs($user)->postJson('/reports', [
        'reportable_type' => 'resource_comment',
        'reportable_id' => $comment->id,
        'reason' => 'Spam',
    ]);

    $response->assertStatus(422);
    expect(Report::count())->toBe(0);
});

test('admin with manage reports can resolve a report and suspend the offender', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin'); // Admin has manage reports

    $offender = User::factory()->create(['username' => 'troublemaker']);
    $reporter = User::factory()->create();

    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reported_user_id' => $offender->id,
        'reported_user_username' => 'troublemaker',
        'reportable_type' => ResourceComment::class,
        'reportable_id' => 1,
        'content_snapshot' => 'Bad words',
        'reason' => 'Offensive language',
        'status' => 'pending',
    ]);

    // Update status to resolved
    $this->actingAs($admin)->patch("/admin/reports/{$report->id}/status", [
        'status' => 'resolved',
    ])->assertRedirect();

    $report->refresh();
    expect($report->status)->toBe('resolved')
        ->and($report->reviewed_by)->toBe($admin->id)
        ->and($report->reviewed_at)->not->toBeNull();

    // Suspend user
    $this->actingAs($admin)->post("/admin/reports/users/{$offender->id}/ban", [
        'banned_until' => now()->addDays(7)->toDateTimeString(),
    ])->assertRedirect();

    expect($offender->fresh()->isBanned())->toBeTrue();
});
