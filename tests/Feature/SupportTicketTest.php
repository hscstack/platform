<?php

use App\Mail\SupportTicketMail;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('support and donate pages load successfully', function () {
    $this->get('/donate')->assertStatus(200);
    $this->get('/support')->assertStatus(200);
});

test('authenticated user can submit a support ticket and receive email', function () {
    Storage::fake();
    Mail::fake();
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('screenshot.png');

    $response = $this->actingAs($user)->post('/support/tickets', [
        'category' => 'bug_report',
        'subject' => 'Issue loading video player',
        'message' => 'The video player does not load when clicking on chapter 3 note.',
        'attachment' => $file,
    ]);

    $response->assertRedirect('/support/my-tickets');

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'category' => 'bug_report',
        'subject' => 'Issue loading video player',
        'status' => 'open',
    ]);

    $ticket = SupportTicket::first();
    expect($ticket->ticket_number)->toStartWith('TKT-')
        ->and($ticket->attachment_path)->not->toBeNull();

    Storage::assertExists($ticket->attachment_path);

    Mail::assertQueued(SupportTicketMail::class, function ($mail) use ($user, $ticket) {
        return $mail->hasTo($user->email) && $mail->mailSubject === "[HSCStack Support] Ticket #{$ticket->ticket_number} Received";
    });
});

test('my tickets page loads for authenticated user and redirects for guest', function () {
    $this->get('/support/my-tickets')->assertRedirect('/login');

    $user = User::factory()->create();
    $this->actingAs($user)->get('/support/my-tickets')->assertStatus(200);
});

test('user cannot have more than 3 active open tickets', function () {
    $user = User::factory()->create();

    // Create 3 open tickets
    for ($i = 1; $i <= 3; $i++) {
        SupportTicket::create([
            'user_id' => $user->id,
            'category' => 'general',
            'subject' => "Open ticket {$i}",
            'message' => "This is open ticket message {$i}",
            'status' => 'open',
        ]);
    }

    // Try to create a 4th ticket
    $response = $this->actingAs($user)->post('/support/tickets', [
        'category' => 'general',
        'subject' => '4th ticket attempt',
        'message' => 'This should be blocked because 3 tickets are open.',
    ]);

    $response->assertSessionHasErrors(['general']);
    expect(SupportTicket::where('user_id', $user->id)->count())->toBe(3);
});

test('unauthenticated user cannot submit a support ticket', function () {
    $response = $this->post('/support/tickets', [
        'category' => 'general',
        'subject' => 'Guest question',
        'message' => 'Testing message for guest submission.',
    ]);

    $response->assertRedirect('/login');
    expect(SupportTicket::count())->toBe(0);
});

test('ticket validation requires valid category and minimum message length', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/support/tickets', [
        'category' => 'invalid_cat',
        'subject' => 'Hi',
        'message' => 'Short',
    ]);

    $response->assertSessionHasErrors(['category', 'subject', 'message']);
    expect(SupportTicket::count())->toBe(0);
});

test('admin with manage tickets permission can access admin tickets panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = SupportTicket::create([
        'user_id' => $admin->id,
        'category' => 'general',
        'subject' => 'Test Subject',
        'message' => 'This is a test ticket message body.',
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->get('/admin/tickets');
    $response->assertStatus(200);
});

test('admin can reply to a ticket and resolve it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $student = User::factory()->create();
    Mail::fake();
    $ticket = SupportTicket::create([
        'user_id' => $student->id,
        'category' => 'missing_resource',
        'subject' => 'Missing PDF for chapter 4',
        'message' => 'Could you please upload the PDF note for chapter 4 physics?',
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->patch("/admin/tickets/{$ticket->id}", [
        'admin_reply' => 'We have uploaded the requested PDF note. Thank you for reporting!',
        'status' => 'resolved',
    ]);

    $response->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->status)->toBe('resolved')
        ->and($ticket->admin_reply)->toBe('We have uploaded the requested PDF note. Thank you for reporting!')
        ->and($ticket->replied_by)->toBe($admin->id)
        ->and($ticket->replied_at)->not->toBeNull();

    Mail::assertQueued(SupportTicketMail::class, function ($mail) use ($student, $ticket) {
        return $mail->hasTo($student->email) && str_contains($mail->mailSubject, "Ticket #{$ticket->ticket_number} Updated");
    });
});

test('admin can update status and delete a ticket', function () {
    Storage::fake();
    Mail::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $file = UploadedFile::fake()->image('error.jpg');
    $path = $file->store('tickets');

    $ticket = SupportTicket::create([
        'user_id' => $admin->id,
        'category' => 'suggestion',
        'subject' => 'Dark mode suggestion',
        'message' => 'Please add AMOLED pure black theme.',
        'attachment_path' => $path,
        'status' => 'open',
    ]);

    // Update status
    $this->actingAs($admin)->patch("/admin/tickets/{$ticket->id}", [
        'status' => 'closed',
        'admin_reply' => 'আপনার সাপোর্ট টিকেটটি পর্যালোচনা শেষে বন্ধ করা হয়েছে।',
    ])->assertSessionHas('success');

    $updatedTicket = $ticket->fresh();
    expect($updatedTicket->status)->toBe('closed')
        ->and($updatedTicket->admin_reply)->toBe('আপনার সাপোর্ট টিকেটটি পর্যালোচনা শেষে বন্ধ করা হয়েছে।')
        ->and($updatedTicket->replied_by)->toBe($admin->id);

    Mail::assertQueued(SupportTicketMail::class, function ($mail) use ($admin, $ticket) {
        return $mail->hasTo($admin->email) && str_contains($mail->mailSubject, "Ticket #{$ticket->ticket_number} Updated");
    });

    // Delete ticket
    $this->actingAs($admin)->delete("/admin/tickets/{$ticket->id}")
        ->assertSessionHas('success');

    expect(SupportTicket::count())->toBe(0);
    Storage::assertMissing($path);
});

test('non-admin user cannot access admin ticket management', function () {
    $student = User::factory()->create();

    $response = $this->actingAs($student)->get('/admin/tickets');
    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});
