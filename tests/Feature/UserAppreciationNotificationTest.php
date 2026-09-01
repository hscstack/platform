<?php

use App\Models\User;
use App\Models\UserAppreciation;
use App\Notifications\UserAppreciationNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('appreciating a user profile sends UserAppreciationNotification', function () {
    Notification::fake();

    $targetUser = User::factory()->create(['username' => 'inspiring_contributor']);
    $appreciator = User::factory()->create(['name' => 'Grateful Student', 'username' => 'grateful_student']);

    $this->actingAs($appreciator)->post(route('user.appreciate', $targetUser->id));

    Notification::assertSentTo($targetUser, UserAppreciationNotification::class, function ($notification) use ($appreciator) {
        return $notification->appreciator->id === $appreciator->id
            && $notification->totalAppreciations === 1;
    });
});

test('un-appreciating does not trigger UserAppreciationNotification', function () {
    Notification::fake();

    $targetUser = User::factory()->create(['username' => 'inspiring_contributor']);
    $appreciator = User::factory()->create(['username' => 'grateful_student']);

    UserAppreciation::create([
        'user_id' => $targetUser->id,
        'appreciator_id' => $appreciator->id,
    ]);

    // Second call toggles off / deletes appreciation
    $this->actingAs($appreciator)->post(route('user.appreciate', $targetUser->id));

    Notification::assertNothingSent();
});

test('appreciating own profile does not trigger notification', function () {
    Notification::fake();

    $targetUser = User::factory()->create(['username' => 'inspiring_contributor']);

    $this->actingAs($targetUser)->post(route('user.appreciate', $targetUser->id));

    Notification::assertNothingSent();
});

test('UserAppreciationNotification delivers to mail only on milestone count and when opted in', function () {
    $targetUserSubscribed = User::factory()->create(['username' => 'target1', 'receive_emails' => true]);
    $targetUserUnsubscribed = User::factory()->create(['username' => 'target2', 'receive_emails' => false]);
    $appreciator = User::factory()->create(['username' => 'fan']);

    // 1st appreciation (milestone: 1) -> database + mail
    $notif1 = new UserAppreciationNotification($appreciator, 1);
    expect($notif1->isMilestone())->toBeTrue()
        ->and($notif1->via($targetUserSubscribed))->toContain('database', 'mail')
        ->and($notif1->via($targetUserUnsubscribed))->toBe(['database']);

    // 2nd appreciation (not milestone) -> database only
    $notif2 = new UserAppreciationNotification($appreciator, 2);
    expect($notif2->isMilestone())->toBeFalse()
        ->and($notif2->via($targetUserSubscribed))->toBe(['database']);

    // 10th appreciation (milestone: 10) -> database + mail
    $notif10 = new UserAppreciationNotification($appreciator, 10);
    expect($notif10->isMilestone())->toBeTrue()
        ->and($notif10->via($targetUserSubscribed))->toContain('database', 'mail');
});
