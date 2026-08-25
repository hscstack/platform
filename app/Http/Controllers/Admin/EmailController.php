<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BulkAnnouncementMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    /**
     * Show the bulk email composer form.
     */
    public function create(): Response
    {
        $recipientCount = User::where('receive_emails', true)
            ->whereNotNull('email')
            ->count();

        return Inertia::render('admin/EmailSend', [
            'recipientCount' => $recipientCount,
        ]);
    }

    /**
     * Dispatch bulk emails to all subscribed users.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $usersQuery = User::where('receive_emails', true)
            ->whereNotNull('email');

        $totalRecipients = $usersQuery->count();

        if ($totalRecipients === 0) {
            return redirect()
                ->route('admin.emails.create')
                ->with('error', 'No subscribed recipients found.');
        }

        $subject = $validated['subject'];
        $body = $validated['body'];

        $usersQuery->chunkById(100, function ($users) use ($subject, $body) {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(
                    new BulkAnnouncementMail(
                        mailSubject: $subject,
                        mailContent: $body,
                        recipientName: $user->name,
                    )
                );
            }
        });

        return redirect()
            ->route('admin.emails.create')
            ->with('success', "Email broadcast successfully queued for {$totalRecipients} recipients.");
    }
}
