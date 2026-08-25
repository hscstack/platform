<?php

use App\Mail\BulkAnnouncementMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('send email', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('admin with send email permission can view email send page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.emails.create'));

    $response->assertStatus(200);
});

test('user without send email permission cannot access email send page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.emails.create'));

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});


test('admin can queue bulk emails only to users with receive_emails enabled', function () {
    Mail::fake();

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    // Create subscribed users
    $subscribed1 = User::factory()->create(['email' => 'sub1@example.com', 'receive_emails' => true]);
    $subscribed2 = User::factory()->create(['email' => 'sub2@example.com', 'receive_emails' => true]);

    // Create unsubscribed user
    $unsubscribed = User::factory()->create(['email' => 'unsub@example.com', 'receive_emails' => false]);

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'subject' => 'Platform Update Announcement',
        'body' => '<p>Check out our brand new video resources!</p>',
    ]);

    $response->assertRedirect(route('admin.emails.create'));
    $response->assertSessionHas('success');

    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) use ($subscribed1) {
        return $mail->hasTo($subscribed1->email) &&
            $mail->mailSubject === 'Platform Update Announcement' &&
            $mail->mailContent === '<p>Check out our brand new video resources!</p>';
    });

    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) use ($subscribed2) {
        return $mail->hasTo($subscribed2->email);
    });

    Mail::assertNotQueued(BulkAnnouncementMail::class, function ($mail) use ($unsubscribed) {
        return $mail->hasTo($unsubscribed->email);
    });
});

test('bulk email validates subject and body', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'subject' => '',
        'body' => '',
    ]);

    $response->assertSessionHasErrors(['subject', 'body']);
});
