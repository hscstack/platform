<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Services\ChatProfanityFilter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage chat', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('admin can update auto-ban report settings with minute presets', function () {
    $botUser = User::factory()->create(['username' => 'hscstack']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.chat.settings.update'), [
        'audience' => 'all',
        'cooldown_seconds' => 15,
        'max_messages' => 150,
        'max_length' => 300,
        'profanity_filter_enabled' => true,
        'banned_words' => 'badword1, badword2',
        'allowed_emojis' => ['👍', '❤️', '🔥'],
        'bot_username' => 'hscstack',
        'auto_ban_enabled' => true,
        'auto_ban_threshold' => 3,
        'auto_ban_duration_minutes' => 15, // 15 mins preset
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(AppSetting::get('global_chat_auto_ban_enabled'))->toBeTrue();
    expect(AppSetting::get('global_chat_auto_ban_reports_threshold'))->toBe(3);
    expect(AppSetting::get('global_chat_auto_ban_duration_minutes'))->toBe(15);
});

test('reporting message triggers auto-ban dynamically with 5 mins duration', function () {
    AppSetting::set('global_chat_auto_ban_enabled', true, 'boolean');
    AppSetting::set('global_chat_auto_ban_reports_threshold', 3, 'integer');
    AppSetting::set('global_chat_auto_ban_duration_minutes', 5, 'integer'); // 5 mins preset

    $badUser = User::factory()->create(['username' => 'badstudent']);
    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();
    $reporter3 = User::factory()->create();

    $messageContent = 'spam or abusive message';

    // Report 1
    $this->actingAs($reporter1)->postJson(route('chat.reports.store'), [
        'reported_user_id' => $badUser->id,
        'reported_user_name' => $badUser->name,
        'reported_user_username' => $badUser->username,
        'message_content' => $messageContent,
        'reason' => 'Abusive text',
    ])->assertStatus(201);

    expect($badUser->fresh()->isChatBanned())->toBeFalse();

    // Report 2
    $this->actingAs($reporter2)->postJson(route('chat.reports.store'), [
        'reported_user_id' => $badUser->id,
        'reported_user_name' => $badUser->name,
        'reported_user_username' => $badUser->username,
        'message_content' => $messageContent,
        'reason' => 'Abusive text',
    ])->assertStatus(201);

    expect($badUser->fresh()->isChatBanned())->toBeFalse();

    // Report 3 (Hits threshold of 3)
    $this->actingAs($reporter3)->postJson(route('chat.reports.store'), [
        'reported_user_id' => $badUser->id,
        'reported_user_name' => $badUser->name,
        'reported_user_username' => $badUser->username,
        'message_content' => $messageContent,
        'reason' => 'Abusive text',
    ])->assertStatus(201);

    $badUserFresh = $badUser->fresh();
    expect($badUserFresh->isChatBanned())->toBeTrue();
    expect($badUserFresh->chat_banned_until)->not->toBeNull();
    expect(now()->diffInMinutes($badUserFresh->chat_banned_until))->toBeGreaterThanOrEqual(4);
});

test('auto ban is not triggered when disabled in admin settings', function () {
    AppSetting::set('global_chat_auto_ban_enabled', false, 'boolean');
    AppSetting::set('global_chat_auto_ban_reports_threshold', 2, 'integer');

    $targetUser = User::factory()->create();
    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();

    $messageContent = 'some reportable text';

    $this->actingAs($reporter1)->postJson(route('chat.reports.store'), [
        'reported_user_id' => $targetUser->id,
        'message_content' => $messageContent,
    ])->assertStatus(201);

    $this->actingAs($reporter2)->postJson(route('chat.reports.store'), [
        'reported_user_id' => $targetUser->id,
        'message_content' => $messageContent,
    ])->assertStatus(201);

    expect($targetUser->fresh()->isChatBanned())->toBeFalse();
});

test('profanity filter allows legitimate words like assalamualaikum and class while blocking actual profanity and obfuscations', function () {
    AppSetting::set('global_chat_profanity_filter_enabled', true, 'boolean');
    AppSetting::set('global_chat_banned_words', 'ass, fuck, sex, bitch, চোদা');

    // Legitimate words containing substrings must NOT be blocked
    expect(ChatProfanityFilter::hasProfanity('assalamualaikum'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('assalamualaikum brothers'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('walaikumsalam'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('class assignment'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('compass assistant'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('classic physics'))->toBeFalse();
    expect(ChatProfanityFilter::hasProfanity('password passage'))->toBeFalse();

    // Actual profanity and obfuscations MUST be blocked
    expect(ChatProfanityFilter::hasProfanity('you are an ass'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('you are an a.s.s'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('you are an a s s'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('you are an @$$'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('f.u.c.k you'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('f u c k you'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('s_e_x'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('s3x'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('5ex'))->toBeTrue();
    expect(ChatProfanityFilter::hasProfanity('তুই একটা চোদা'))->toBeTrue();
});
