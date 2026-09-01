<?php

use App\Models\User;
use App\Notifications\UserSuspensionNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('moderator suspending user sends UserSuspensionNotification to user with email', function () {
    Notification::fake();

    $moderator = User::factory()->create();
    $moderator->assignRole('admin');

    $targetUser = User::factory()->create(['username' => 'misbehaving_user', 'receive_emails' => true]);

    $bannedUntil = now()->addDays(3);

    $this->actingAs($moderator)->post(route('admin.chat.users.ban', $targetUser->id), [
        'banned_until' => $bannedUntil->toDateTimeString(),
    ]);

    Notification::assertSentTo($targetUser, UserSuspensionNotification::class, function ($notification) use ($targetUser) {
        $channels = $notification->via($targetUser);

        return $notification->isBanned === true
            && in_array('database', $channels, true)
            && in_array('mail', $channels, true);
    });
});

test('moderator removing suspension sends UserSuspensionNotification with isBanned false', function () {
    Notification::fake();

    $moderator = User::factory()->create();
    $moderator->assignRole('admin');

    $targetUser = User::factory()->create([
        'username' => 'misbehaving_user',
        'banned_until' => now()->addDays(3),
        'receive_emails' => true,
    ]);

    $this->actingAs($moderator)->post(route('admin.chat.users.ban', $targetUser->id), [
        'banned_until' => null,
    ]);

    Notification::assertSentTo($targetUser, UserSuspensionNotification::class, function ($notification) {
        return $notification->isBanned === false;
    });
});

test('UserSuspensionNotification does not route to mail if user opted out', function () {
    $user = User::factory()->create(['receive_emails' => false]);

    $notification = new UserSuspensionNotification(isBanned: true, bannedUntilFormatted: 'in 3 days');
    expect($notification->via($user))->toBe(['database']);
});
