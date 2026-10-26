<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Notifications\UserMentionNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    AppSetting::set('global_chat_enabled', true, 'boolean');
    AppSetting::set('global_chat_audience', 'all', 'string');
    AppSetting::set('global_chat_cooldown_seconds', 0, 'integer');
});

test('sending a chat message with @mention notifies the mentioned user', function () {
    Notification::fake();

    $sender = User::factory()->create(['username' => 'sender_user']);
    $mentioned = User::factory()->create(['username' => 'alice']);

    $this->actingAs($sender)->postJson(route('chat.messages.store'), [
        'content' => 'Hello @alice how are you doing today?',
    ])->assertCreated();

    Notification::assertSentTo($mentioned, UserMentionNotification::class, function ($notification) use ($sender) {
        return $notification->sender->id === $sender->id
            && str_contains($notification->content, '@alice');
    });
});

test('sending a chat message with multiple @mentions notifies all valid mentioned users', function () {
    Notification::fake();

    $sender = User::factory()->create(['username' => 'sender_user']);
    $alice = User::factory()->create(['username' => 'alice']);
    $bob = User::factory()->create(['username' => 'bob']);

    $this->actingAs($sender)->postJson(route('chat.messages.store'), [
        'content' => 'Hey @alice and @bob check this out!',
    ])->assertCreated();

    Notification::assertSentTo($alice, UserMentionNotification::class);
    Notification::assertSentTo($bob, UserMentionNotification::class);
});

test('mentioning oneself in a chat message does not send self notification', function () {
    Notification::fake();

    $sender = User::factory()->create(['username' => 'sender_user']);

    $this->actingAs($sender)->postJson(route('chat.messages.store'), [
        'content' => 'Note to self: @sender_user remember to study!',
    ])->assertCreated();

    Notification::assertNothingSent();
});
