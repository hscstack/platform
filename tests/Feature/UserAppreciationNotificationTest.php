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

test('UserAppreciationNotification delivers to database channel', function () {
    $targetUser = User::factory()->create(['username' => 'target1']);
    $appreciator = User::factory()->create(['username' => 'fan']);

    $notif = new UserAppreciationNotification($appreciator, 1);
    expect($notif->via($targetUser))->toBe(['database']);
});

test('multiple appreciations aggregate into a single unread notification with rolled up titles', function () {
    $targetUser = User::factory()->create(['username' => 'popular_creator']);
    $user1 = User::factory()->create(['name' => 'Alice', 'username' => 'alice']);
    $user2 = User::factory()->create(['name' => 'Bob', 'username' => 'bob']);
    $user3 = User::factory()->create(['name' => 'Charlie', 'username' => 'charlie']);
    $user4 = User::factory()->create(['name' => 'David', 'username' => 'david']);

    // 1st appreciation by Alice
    $this->actingAs($user1)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $notif = $targetUser->unreadNotifications()->first();
    expect($notif->data['title'])->toBe('Alice appreciated you!')
        ->and($notif->data['count'])->toBe(1);

    // 2nd appreciation by Bob
    $this->actingAs($user2)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $notif->refresh();
    expect($notif->data['title'])->toBe('Bob and 1 other person appreciated you!')
        ->and($notif->data['count'])->toBe(2);

    // 3rd appreciation by Charlie
    $this->actingAs($user3)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $notif->refresh();
    expect($notif->data['title'])->toBe('Charlie and 2 other people appreciated you!')
        ->and($notif->data['count'])->toBe(3);

    // 4th appreciation by David
    $this->actingAs($user4)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $notif->refresh();
    expect($notif->data['title'])->toBe('David and 3 other people appreciated you!')
        ->and($notif->data['count'])->toBe(4);
});

test('reading the notification starts a fresh aggregation on subsequent appreciation', function () {
    $targetUser = User::factory()->create(['username' => 'popular_creator']);
    $user1 = User::factory()->create(['name' => 'Alice', 'username' => 'alice']);
    $user2 = User::factory()->create(['name' => 'Bob', 'username' => 'bob']);

    // Alice appreciates
    $this->actingAs($user1)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    // Target user marks all notifications as read
    $targetUser->unreadNotifications()->update(['read_at' => now()]);
    expect($targetUser->unreadNotifications()->count())->toBe(0);

    // Bob appreciates -> creates new unread notification
    $this->actingAs($user2)->post(route('user.appreciate', $targetUser->id));
    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $newNotif = $targetUser->unreadNotifications()->first();
    expect($newNotif->data['title'])->toBe('Bob appreciated you!')
        ->and($newNotif->data['count'])->toBe(1);
});

test('100 appreciations roll up into title with 99 other people', function () {
    $targetUser = User::factory()->create(['username' => 'superstar']);
    $users = User::factory()->count(100)->create();

    foreach ($users as $u) {
        $this->actingAs($u)->post(route('user.appreciate', $targetUser->id));
    }

    expect($targetUser->unreadNotifications()->count())->toBe(1);

    $notif = $targetUser->unreadNotifications()->first();
    $lastUser = $users[99];

    expect($notif->data['count'])->toBe(100)
        ->and($notif->data['title'])->toBe("{$lastUser->name} and 99 other people appreciated you!");
});
