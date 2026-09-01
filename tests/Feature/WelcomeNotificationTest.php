<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('welcome notification delivers to both database and mail when user accepts emails', function () {
    $user = User::factory()->create([
        'email' => 'student@example.com',
        'receive_emails' => true,
    ]);

    $notification = new WelcomeNotification;
    $channels = $notification->via($user);

    expect($channels)->toContain('database')
        ->and($channels)->toContain('mail');

    $dbData = $notification->toArray($user);
    expect($dbData['type'])->toBe('welcome')
        ->and($dbData['title'])->toBe('Welcome to HSCStack! 🎓')
        ->and($dbData['url'])->toBe(route('me'));

    $mailMessage = $notification->toMail($user);
    expect($mailMessage->subject)->toBe('Welcome to HSCStack! 🎓');
});

test('welcome notification delivers only to database when user opted out of emails', function () {
    $user = User::factory()->create([
        'email' => 'quiet@example.com',
        'receive_emails' => false,
    ]);

    $notification = new WelcomeNotification;
    $channels = $notification->via($user);

    expect($channels)->toContain('database')
        ->and($channels)->not->toContain('mail');
});

test('creating user in admin panel sends welcome notification', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Staff User',
        'email' => 'newstaff@example.com',
        'username' => 'new_staff',
        'role' => 'editor',
    ])->assertRedirect(route('admin.users.index'));

    $newUser = User::where('email', 'newstaff@example.com')->first();
    expect($newUser)->not->toBeNull();

    Notification::assertSentTo($newUser, WelcomeNotification::class);
});

test('user database notification record is persisted and visible in notifications endpoint', function () {
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification);

    $response = $this->actingAs($user)->getJson('/notifications');

    $response->assertStatus(200)
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.data.type', 'welcome')
        ->assertJsonPath('notifications.0.data.title', 'Welcome to HSCStack! 🎓');
});
