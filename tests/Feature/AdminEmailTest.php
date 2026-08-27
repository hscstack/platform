<?php

use App\Mail\BulkAnnouncementMail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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

test('admin can send email directly to a single user', function () {
    Mail::fake();

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $targetUser = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipient_type' => 'single',
        'recipient_email' => 'john@example.com',
        'subject' => 'Account Verification Notice',
        'body' => '<p>Hello John, please review your account.</p>',
    ]);

    $response->assertRedirect(route('admin.emails.create'));
    $response->assertSessionHas('success');

    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) {
        return $mail->hasTo('john@example.com') &&
            $mail->mailSubject === 'Account Verification Notice' &&
            $mail->mailContent === '<p>Hello John, please review your account.</p>' &&
            $mail->recipientName === 'John Doe';
    });
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
        'recipient_type' => 'all',
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

test('single email requires valid recipient email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipient_type' => 'single',
        'recipient_email' => '',
        'subject' => 'Test Subject',
        'body' => '<p>Test Body</p>',
    ]);

    $response->assertSessionHasErrors(['recipient_email']);
});

test('admin can upload cover image and attach url to queued mail', function () {
    Storage::fake('public');
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create([
        'email' => 'student@example.com',
    ]);

    $image = UploadedFile::fake()->image('banner.jpg', 600, 300);

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipient_type' => 'single',
        'recipient_email' => 'student@example.com',
        'subject' => 'Image Test Subject',
        'body' => '<p>Image Test Body</p>',
        'image' => $image,
    ]);

    $response->assertRedirect(route('admin.emails.create'));
    $response->assertSessionHas('success');

    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) {
        return $mail->hasTo('student@example.com') &&
            ! empty($mail->imageUrl);
    });
});

test('admin can queue broadcast emails specifically to students non-staff users', function () {
    Mail::fake();

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $editor = User::factory()->create(['email' => 'editor@example.com', 'receive_emails' => true]);
    $editorRole = Role::findOrCreate('editor', 'web');
    $editor->assignRole($editorRole);

    $student1 = User::factory()->create(['email' => 'student1@example.com', 'receive_emails' => true]);
    $student2 = User::factory()->create(['email' => 'student2@example.com', 'receive_emails' => true]);
    $unsubStudent = User::factory()->create(['email' => 'unsub_student@example.com', 'receive_emails' => false]);

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipient_type' => 'students',
        'subject' => 'Student Community Update',
        'body' => '<p>Special notice for all students.</p>',
    ]);

    $response->assertRedirect(route('admin.emails.create'));
    $response->assertSessionHas('success');

    // Students with receive_emails=true should receive the email
    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) use ($student1) {
        return $mail->hasTo($student1->email) &&
            $mail->mailSubject === 'Student Community Update';
    });

    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) use ($student2) {
        return $mail->hasTo($student2->email);
    });

    // Staff/role-assigned users should NOT receive it
    Mail::assertNotQueued(BulkAnnouncementMail::class, function ($mail) use ($editor) {
        return $mail->hasTo($editor->email);
    });

    // Unsubscribed students should NOT receive it
    Mail::assertNotQueued(BulkAnnouncementMail::class, function ($mail) use ($unsubStudent) {
        return $mail->hasTo($unsubStudent->email);
    });
});
