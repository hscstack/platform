<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BulkAnnouncementMail;
use App\Models\SentEmailLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    /**
     * Show the email composer form.
     */
    public function create(): Response
    {
        $allSubscribersCount = User::where('receive_emails', true)
            ->whereNotNull('email')
            ->count();

        $studentsCount = User::where('receive_emails', true)
            ->whereNotNull('email')
            ->doesntHave('roles')
            ->count();

        $staffCount = User::where('receive_emails', true)
            ->whereNotNull('email')
            ->has('roles')
            ->count();

        $recentLogs = SentEmailLog::latest('id')
            ->limit(20)
            ->get();

        return Inertia::render('admin/EmailSend', [
            'recipientCount' => $allSubscribersCount,
            'studentsCount' => $studentsCount,
            'staffCount' => $staffCount,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Dispatch emails to either a single user or all/student subscribed users in bulk.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_type' => ['required', 'in:all,students,staff,single'],
            'recipient_email' => ['required_if:recipient_type,single', 'nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ]);

        $subject = $validated['subject'];
        $body = $validated['body'];

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('emails/images');
            $relativeUrl = Storage::url($path);
            $imageUrl = str_starts_with($relativeUrl, 'http') ? $relativeUrl : url($relativeUrl);
        }

        // Single user mode
        if ($validated['recipient_type'] === 'single') {
            $recipientEmail = $validated['recipient_email'];
            $targetUser = User::where('email', $recipientEmail)->first();

            Mail::to($recipientEmail)->queue(
                new BulkAnnouncementMail(
                    mailSubject: $subject,
                    mailContent: $body,
                    recipientName: $targetUser?->name,
                    imageUrl: $imageUrl,
                )
            );

            return redirect()
                ->route('admin.emails.create')
                ->with('success', "Email successfully queued for {$recipientEmail}.");
        }

        // Bulk broadcast query
        $usersQuery = User::where('receive_emails', true)
            ->whereNotNull('email');

        if ($validated['recipient_type'] === 'students') {
            $usersQuery->doesntHave('roles');
        } elseif ($validated['recipient_type'] === 'staff') {
            $usersQuery->has('roles');
        }

        $totalRecipients = $usersQuery->count();

        if ($totalRecipients === 0) {
            return redirect()
                ->route('admin.emails.create')
                ->with('error', 'No subscribed recipients found for the selected target.');
        }

        $usersQuery->chunkById(100, function ($users) use ($subject, $body, $imageUrl) {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(
                    new BulkAnnouncementMail(
                        mailSubject: $subject,
                        mailContent: $body,
                        recipientName: $user->name,
                        imageUrl: $imageUrl,
                    )
                );
            }
        });

        $targetLabel = match ($validated['recipient_type']) {
            'students' => 'students (non-staff)',
            'staff' => 'staff members',
            default => 'all subscribed',
        };

        return redirect()
            ->route('admin.emails.create')
            ->with('success', "Email broadcast successfully queued for {$totalRecipients} {$targetLabel} recipients.");
    }
}
