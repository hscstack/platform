<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BulkAnnouncementMail;
use App\Models\User;
use App\Notifications\SystemAnnouncementNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    /**
     * Show the email composer form.
     */
    public function create(): Response
    {
        return Inertia::render('admin/EmailSend', [
            'allUsersCount' => User::count(),
            'recipientCount' => User::where('receive_emails', true)->count(),
            'studentsCount' => User::where('is_verified', false)->count(),
            'staffCount' => User::where('is_verified', true)->count(),
        ]);
    }

    /**
     * Fetch raw email list for importing into the editor.
     */
    public function recipients(Request $request): JsonResponse
    {
        $type = $request->query('type', 'subscribers');

        $query = User::query();

        if ($type === 'subscribers') {
            $query->where('receive_emails', true);
        } elseif ($type === 'students') {
            $query->where('is_verified', false);
        } elseif ($type === 'staff') {
            $query->where('is_verified', true);
        }

        $emails = $query->pluck('email');

        return response()->json([
            'emails' => $emails,
            'count' => $emails->count(),
        ]);
    }

    /**
     * Dispatch emails and/or in-app announcements.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'send_email' => ['sometimes', 'boolean'],
            'send_in_app' => ['sometimes', 'boolean'],
            'action_url' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $sendEmail = $request->boolean('send_email', true);
        $sendInApp = $request->boolean('send_in_app', false);

        if (! $sendEmail && ! $sendInApp) {
            return redirect()
                ->route('admin.emails.create')
                ->with('error', 'Please select at least one delivery channel (Email or In-App Notification).');
        }

        $subject = $validated['subject'];
        $body = $validated['body'];

        // Parse, clean, validate, and deduplicate emails
        $rawLines = preg_split('/[\r\n,;]+/', $validated['recipients']);
        $cleanedEmails = [];

        foreach ($rawLines as $line) {
            $email = strtolower(trim($line));
            if (! empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cleanedEmails[$email] = true;
            }
        }

        $uniqueEmails = array_keys($cleanedEmails);

        if (empty($uniqueEmails)) {
            return redirect()
                ->route('admin.emails.create')
                ->withErrors(['recipients' => 'Please provide at least one valid recipient email in the list.']);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('emails/images');
            $relativeUrl = Storage::url($path);
            $imageUrl = str_starts_with($relativeUrl, 'http') ? $relativeUrl : url($relativeUrl);
        }

        $targetUsers = User::whereIn('email', $uniqueEmails)->get();

        if ($sendEmail) {
            $usersMap = $targetUsers->pluck('name', 'email');

            foreach ($uniqueEmails as $email) {
                Mail::to($email)->queue(
                    new BulkAnnouncementMail(
                        mailSubject: $subject,
                        mailContent: $body,
                        recipientName: $usersMap[$email] ?? null,
                        imageUrl: $imageUrl,
                    )
                );
            }
        }

        if ($sendInApp && $targetUsers->isNotEmpty()) {
            $announcement = new SystemAnnouncementNotification(
                title: $subject,
                snippet: Str::limit(strip_tags($body), 120),
                fullContent: $body,
                imageUrl: $imageUrl,
                url: $validated['action_url'] ?? null,
            );

            Notification::send($targetUsers, $announcement);
        }

        return redirect()
            ->route('admin.emails.create')
            ->with('success', 'Announcement successfully dispatched.');
    }
}
