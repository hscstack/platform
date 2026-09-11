<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Notifications\StudyPokeNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guests are redirected when attempting to poke a user', function () {
    $targetUser = User::factory()->create(['username' => 'bob']);

    $this->post("/u/{$targetUser->id}/poke", ['preset_id' => 'syllabus'])
        ->assertRedirect('/login');
});

test('users cannot poke their own profile', function () {
    $user = User::factory()->create(['username' => 'alice']);

    $this->actingAs($user)
        ->post("/u/{$user->id}/poke", ['preset_id' => 'syllabus'])
        ->assertSessionHas('error');
});

test('authenticated user can poke another user and dispatch notification', function () {
    Notification::fake();
    Cache::flush();

    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create(['username' => 'bob']);

    $response = $this->actingAs($userA)
        ->post("/u/{$userB->id}/poke", ['preset_id' => 'syllabus']);

    $response->assertSessionHas('success');

    Notification::assertSentTo(
        $userB,
        StudyPokeNotification::class,
        function (StudyPokeNotification $notification) use ($userA) {
            return $notification->sender->id === $userA->id
                && $notification->presetId === 'syllabus';
        }
    );

    // Verify cooldown is in cache
    $cacheKey = "study_poke:{$userA->id}:{$userB->id}";
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('subsequent pokes during cooldown are blocked', function () {
    Notification::fake();
    Cache::flush();

    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create(['username' => 'bob']);

    // First poke succeeds
    $this->actingAs($userA)
        ->post("/u/{$userB->id}/poke", ['preset_id' => 'syllabus'])
        ->assertSessionHas('success');

    // Second poke fails due to cooldown
    $this->actingAs($userA)
        ->post("/u/{$userB->id}/poke", ['preset_id' => 'coffee'])
        ->assertSessionHas('error');

    Notification::assertSentTimes(StudyPokeNotification::class, 1);
});

test('cannot poke user who has turned off allow_pokes', function () {
    Notification::fake();
    Cache::flush();

    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create([
        'username' => 'bob',
        'allow_pokes' => false,
    ]);

    $this->actingAs($userA)
        ->post("/u/{$userB->id}/poke", ['preset_id' => 'syllabus'])
        ->assertSessionHas('error');

    Notification::assertNothingSent();
});

test('user profile passes pokeData with active presets and cooldown status', function () {
    Cache::flush();

    $userA = User::factory()->create(['username' => 'alice']);
    $userB = User::factory()->create(['username' => 'bob']);

    $response = $this->actingAs($userA)->get("/u/{$userB->username}");

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Show')
            ->has('pokeData')
            ->where('pokeData.enabled', true)
            ->where('pokeData.canPoke', true)
            ->where('pokeData.isCooldown', false)
            ->has('pokeData.presets')
        );
});

test('admin can update peer and poke settings with manage peers permission', function () {
    $permission = Permission::findOrCreate('manage peers');
    $adminRole = Role::findOrCreate('admin');
    $adminRole->givePermissionTo($permission);

    $adminUser = User::factory()->create([
        'email_verified_at' => now(),
        'is_verified' => true,
    ]);
    $adminUser->assignRole('admin');
    $adminUser->givePermissionTo('view admin');

    $response = $this->actingAs($adminUser)->get('/admin/peers/settings');
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/PeerSettings')
            ->has('settings.presets')
        );

    $newPresets = [
        [
            'id' => 'custom_1',
            'icon' => '🚀',
            'message' => 'Blast off into your study session now!',
        ],
    ];

    $updateResponse = $this->actingAs($adminUser)
        ->post('/admin/peers/settings', [
            'enabled' => true,
            'cooldown_hours' => 12,
            'presets' => $newPresets,
        ]);

    $updateResponse->assertRedirect();

    expect(AppSetting::get('peer_poke_cooldown_hours'))->toBe(12);
    expect(AppSetting::get('peer_poke_presets'))->toBe($newPresets);
});
