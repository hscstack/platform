<?php

use App\Mail\AccountDeletedMail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('account deleted mailable renders expected subject, content and legal links', function () {
    $mailable = new AccountDeletedMail('Rahim Ahmed');

    $mailable->assertHasSubject('আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে');

    $html = $mailable->render();

    expect($html)
        ->toContain('আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে')
        ->toContain('আমরা আপনার অ্যাকাউন্ট ডিলিট করার অনুরোধটি পেয়েছি')
        ->toContain('Privacy Policy')
        ->toContain('Terms &amp; Conditions')
        ->toContain('/privacy-policy')
        ->toContain('/terms-service')
        ->toContain('Resource Archive')
        ->toContain('Forum')
        ->toContain('Global Chat')
        ->toContain('Study Tracker')
        ->toContain('Community Interaction')
        ->toContain('ভবিষ্যতে আবার দেখা হবে! 💙');
});

test('admin deleting a user queues account deleted mail and deletes user', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userToDelete = User::factory()->create([
        'name' => 'Karim Hasan',
        'email' => 'karim@example.com',
    ]);

    $this->actingAs($admin)
        ->delete("/admin/users/{$userToDelete->id}")
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    expect(User::find($userToDelete->id))->toBeNull();

    Mail::assertQueued(AccountDeletedMail::class, function (AccountDeletedMail $mail) use ($userToDelete) {
        return $mail->hasTo($userToDelete->email)
            && $mail->recipientName === 'Karim Hasan';
    });
});
