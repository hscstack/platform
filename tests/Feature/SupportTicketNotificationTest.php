<?php

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketUpdatedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('user creating support ticket sends SupportTicketCreatedNotification to staff with manage tickets permission', function () {
    Notification::fake();

    $staff = User::factory()->create();
    $staff->assignRole('admin');

    $user = User::factory()->create(['name' => 'Need Help Student']);

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'category' => 'bug_report',
        'subject' => 'Cannot view PDF resources',
        'message' => 'Whenever I click on the PDF resource link, a 404 occurs.',
    ]);

    Notification::assertSentTo($staff, SupportTicketCreatedNotification::class, function ($notification) use ($user) {
        return $notification->user->id === $user->id
            && $notification->ticket->subject === 'Cannot view PDF resources';
    });
});

test('staff replying or updating status sends SupportTicketUpdatedNotification to ticket owner with mail', function () {
    Notification::fake();

    $staff = User::factory()->create();
    $staff->assignRole('admin');

    $user = User::factory()->create(['receive_emails' => true]);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'category' => 'bug_report',
        'subject' => 'Cannot view PDF resources',
        'message' => 'Whenever I click on the PDF resource link, a 404 occurs.',
        'status' => SupportTicket::STATUS_OPEN,
    ]);

    $this->actingAs($staff)->patch(route('admin.tickets.update', $ticket->id), [
        'status' => SupportTicket::STATUS_RESOLVED,
        'admin_reply' => 'The PDF link issue has been resolved. Please check now!',
    ]);

    Notification::assertSentTo($user, SupportTicketUpdatedNotification::class, function ($notification) use ($ticket, $user) {
        $channels = $notification->via($user);

        return $notification->ticket->id === $ticket->id
            && in_array('database', $channels, true)
            && in_array('mail', $channels, true);
    });
});

test('SupportTicketUpdatedNotification does not send mail if user opted out', function () {
    $user = User::factory()->create(['receive_emails' => false]);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'category' => 'general_inquiry',
        'subject' => 'Question about courses',
        'message' => 'When will the new session start?',
        'status' => SupportTicket::STATUS_OPEN,
    ]);

    $notification = new SupportTicketUpdatedNotification($ticket);
    expect($notification->via($user))->toBe(['database']);
});
