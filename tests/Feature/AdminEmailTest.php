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

test('admin can fetch subscriber emails for import', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $student = User::factory()->create(['email' => 'student@example.com', 'receive_emails' => true]);
    $staff = User::factory()->create(['email' => 'staff@example.com', 'receive_emails' => true]);
    $editorRole = Role::findOrCreate('editor', 'web');
    $staff->assignRole($editorRole);

    $unsubscribed = User::factory()->create(['email' => 'unsub@example.com', 'receive_emails' => false]);

    // All subscribed
    $responseAll = $this->actingAs($admin)->get(route('admin.emails.recipients', ['type' => 'all']));
    $responseAll->assertOk();
    $dataAll = $responseAll->json();
    expect($dataAll['emails'])->toContain('student@example.com', 'staff@example.com')
        ->and($dataAll['emails'])->not->toContain('unsub@example.com');

    // Students only
    $responseStudents = $this->actingAs($admin)->get(route('admin.emails.recipients', ['type' => 'students']));
    $responseStudents->assertOk();
    $dataStudents = $responseStudents->json();
    expect($dataStudents['emails'])->toContain('student@example.com')
        ->and($dataStudents['emails'])->not->toContain('staff@example.com');

    // Staff only
    $responseStaff = $this->actingAs($admin)->get(route('admin.emails.recipients', ['type' => 'staff']));
    $responseStaff->assertOk();
    $dataStaff = $responseStaff->json();
    expect($dataStaff['emails'])->toContain('staff@example.com')
        ->and($dataStaff['emails'])->not->toContain('student@example.com');
});

test('admin can send email to custom and platform emails with automatic deduplication', function () {
    Mail::fake();

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $john = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $recipientsRaw = "john@example.com\nJOHN@EXAMPLE.COM\nextra-lead@3rdparty.com\ninvalid-email-format\n   extra-lead@3rdparty.com   ";

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipients' => $recipientsRaw,
        'subject' => 'Platform Update Notice',
        'body' => '<p>Hello, check out the new features!</p>',
    ]);

    $response->assertRedirect(route('admin.emails.create'));
    $response->assertSessionHas('success');

    // John receives email with personalized name (only once)
    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) {
        return $mail->hasTo('john@example.com') &&
            $mail->mailSubject === 'Platform Update Notice' &&
            $mail->recipientName === 'John Doe';
    });

    // 3rd party lead receives email with null name (only once)
    Mail::assertQueued(BulkAnnouncementMail::class, function ($mail) {
        return $mail->hasTo('extra-lead@3rdparty.com') &&
            $mail->mailSubject === 'Platform Update Notice' &&
            $mail->recipientName === null;
    });

    // Total queued should be exactly 2
    Mail::assertQueuedCount(2);
});

test('submitting without valid recipients fails validation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipients' => '',
        'subject' => 'Test Subject',
        'body' => '<p>Test Body</p>',
    ]);

    $response->assertSessionHasErrors(['recipients']);
});

test('admin can upload cover image and attach url to queued mail', function () {
    Storage::fake('public');
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $image = UploadedFile::fake()->image('banner.jpg', 600, 300);

    $response = $this->actingAs($admin)->post(route('admin.emails.store'), [
        'recipients' => 'student@example.com',
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
