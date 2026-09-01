<?php

use App\Models\ChatMessage;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ChatReportNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('reporting a chat message sends ChatReportNotification to users with manage chat permission', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin'); // admin has 'manage chat'

    $regularUser = User::factory()->create();
    $reporter = User::factory()->create(['username' => 'reporter_user']);
    $sender = User::factory()->create(['username' => 'spammer']);

    $message = ChatMessage::create([
        'user_id' => $sender->id,
        'content' => 'Spam message content here',
    ]);

    $this->actingAs($reporter)->postJson('/api/chat/reports', [
        'message_id' => $message->id,
        'message_content' => $message->content,
        'reason' => 'Spamming',
    ])->assertStatus(201);

    // Admin/Moderator with 'manage chat' should receive notification
    Notification::assertSentTo($admin, ChatReportNotification::class, function ($notification) use ($reporter) {
        return $notification->reporter->id === $reporter->id
            && $notification->report->reportable_id !== null;
    });

    // Regular user without 'manage chat' should not receive notification
    Notification::assertNotSentTo($regularUser, ChatReportNotification::class);
});

test('ChatReportNotification array data contains report link and snippet', function () {
    $admin = User::factory()->create();
    $reporter = User::factory()->create(['username' => 'student_rahim']);
    $sender = User::factory()->create();

    $message = ChatMessage::create([
        'user_id' => $sender->id,
        'content' => 'Offensive comment in chat',
    ]);

    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => ChatMessage::class,
        'reportable_id' => $message->id,
        'content_snapshot' => $message->content,
        'reason' => 'Offensive',
    ]);

    $notification = new ChatReportNotification($report, $reporter);

    expect($notification->via($admin))->toBe(['database']);

    $data = $notification->toArray($admin);
    expect($data['type'])->toBe('chat_report')
        ->and($data['title'])->toBe('Chat message reported')
        ->and($data['url'])->toBe(route('admin.chat.reports.index'))
        ->and($data['report_id'])->toBe($report->id)
        ->and($data['message'])->toContain('@student_rahim');
});
