<?php

namespace Tests\Feature;

use App\Mail\AccountDeletedMail;
use App\Mail\AccountDeletionLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountDeletionSignedUrlTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view admin');
        Permission::findOrCreate('view users');
        Permission::findOrCreate('manage users');

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        $this->admin->givePermissionTo(['view admin', 'view users', 'manage users']);

        $this->targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
        ]);
    }

    public function test_admin_can_send_deletion_link_to_user(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.send-delete-link', $this->targetUser));

        $response->assertSessionHas('success');

        Mail::assertQueued(AccountDeletionLinkMail::class, function ($mail) {
            return $mail->hasTo('target@example.com')
                && str_contains($mail->signedUrl, 'account/delete/verify/'.$this->targetUser->id)
                && str_contains($mail->signedUrl, 'signature=');
        });
    }

    public function test_cannot_send_deletion_link_without_permission(): void
    {
        Mail::fake();

        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->post(route('admin.users.send-delete-link', $this->targetUser));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        Mail::assertNothingQueued();
    }

    public function test_cannot_send_deletion_link_if_user_has_no_email(): void
    {
        Mail::fake();

        $userId = DB::table('users')->insertGetId([
            'name' => 'User Without Email',
            'email' => '',
            'username' => 'user_no_email_'.uniqid(),
            'google_id' => 'no_email_'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::findOrFail($userId);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.send-delete-link', $user));

        $response->assertSessionHas('error');
        Mail::assertNothingQueued();
    }

    public function test_user_can_access_confirmation_page_with_valid_signed_url(): void
    {
        $this->withoutVite();

        $signedUrl = URL::temporarySignedRoute(
            'account.delete.verify',
            now()->addHours(24),
            [
                'user' => $this->targetUser->id,
                'hash' => sha1($this->targetUser->email),
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Account/ConfirmDeletion')
            ->has('user')
            ->where('user.id', $this->targetUser->id)
            ->where('user.email', $this->targetUser->email)
        );
    }

    public function test_access_denied_with_tampered_or_invalid_signature(): void
    {
        $this->withoutVite();

        $signedUrl = URL::temporarySignedRoute(
            'account.delete.verify',
            now()->addHours(24),
            [
                'user' => $this->targetUser->id,
                'hash' => sha1($this->targetUser->email),
            ]
        );

        // Tamper with the URL
        $tamperedUrl = $signedUrl.'&extra=bad';

        $response = $this->get($tamperedUrl);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Account/ConfirmDeletion')
            ->where('isValid', false)
        );
    }

    public function test_access_denied_with_expired_signature(): void
    {
        $this->withoutVite();

        $expiredUrl = URL::temporarySignedRoute(
            'account.delete.verify',
            now()->subHour(),
            [
                'user' => $this->targetUser->id,
                'hash' => sha1($this->targetUser->email),
            ]
        );

        $response = $this->get($expiredUrl);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Account/ConfirmDeletion')
            ->where('isValid', false)
        );
    }

    public function test_access_denied_with_mismatched_hash(): void
    {
        $this->withoutVite();

        $signedUrl = URL::temporarySignedRoute(
            'account.delete.verify',
            now()->addHours(24),
            [
                'user' => $this->targetUser->id,
                'hash' => sha1('different-email@example.com'),
            ]
        );

        $response = $this->get($signedUrl);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Account/ConfirmDeletion')
            ->where('isValid', false)
        );
    }

    public function test_user_can_permanently_delete_account_via_signed_post(): void
    {
        Mail::fake();

        $signedUrl = URL::temporarySignedRoute(
            'account.delete.confirm',
            now()->addHours(24),
            [
                'user' => $this->targetUser->id,
                'hash' => sha1($this->targetUser->email),
            ]
        );

        $response = $this->actingAs($this->targetUser)->post($signedUrl);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $this->targetUser->id,
        ]);

        $this->assertGuest();

        Mail::assertNothingQueued();
    }

    public function test_admin_instant_hard_delete_still_works(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $this->targetUser->id,
        ]);

        Mail::assertQueued(AccountDeletedMail::class, function ($mail) {
            return $mail->hasTo('target@example.com');
        });
    }
}
