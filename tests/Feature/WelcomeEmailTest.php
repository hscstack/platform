<?php

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('welcome user mail renders recipient name, AI mention, and join us link', function () {
    $user = User::factory()->create([
        'name' => 'Arefin Tajim',
        'email' => 'tajim@example.com',
    ]);

    $mailable = new WelcomeUserMail($user);

    $mailable->assertHasSubject('Welcome to HSCStack — The Open Learning Platform');
    $mailable->assertSeeInHtml('Arefin Tajim');
    $mailable->assertSeeInHtml('HSC');
    $mailable->assertSeeInHtml('Stack');
    $mailable->assertSeeInHtml('HSCStack AI');
    $mailable->assertSeeInHtml('Join Our Team');
});

test('creating user in admin queues welcome mail', function () {
    Mail::fake();

    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage users', 'web');
    $perm = Permission::findOrCreate('create blogs', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());

    Role::findOrCreate('editor', 'web');

    $admin = User::factory()->create(['name' => 'Admin']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Contributor',
            'email' => 'contributor@example.com',
            'role' => 'editor',
            'permissions' => [$perm->name],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.users.index'));

    Mail::assertQueued(WelcomeUserMail::class, function ($mail) {
        return $mail->hasTo('contributor@example.com');
    });
});
