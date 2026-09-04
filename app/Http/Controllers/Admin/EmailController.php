<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BulkAnnouncementMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

        return Inertia::render('admin/EmailSend', [
            'recipientCount' => $allSubscribersCount,
            'studentsCount' => $studentsCount,
            'staffCount' => $staffCount,
        ]);
    }

    /**
     * Fetch raw subscribed email list for importing into the editor.
     */
    public function recipients(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all');

        $query = User::where('receive_emails', true)->whereNotNull('email');

        if ($type === 'students') {
            $query->doesntHave('roles');
        } elseif ($type === 'staff') {
            $query->has('roles');
        }

        $emails = $query->pluck('email')->unique()->values();

        return response()->json([
            'emails' => $emails,
            'count' => $emails->count(),
        ]);
    }

    /**
     * Dispatch emails to a list of raw recipient emails.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $subject = $validated['subject'];
        $body = $validated['body'];

        // Parse, clean, validate, and deduplicate emails
        $rawLines = preg_split('/[\r\n,;]+/', $validated['recipients']);
        $cleanedEmails = [];

        foreach ($rawLines as $line) {
            $email = strtolower(trim($line));
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cleanedEmails[$email] = true;
            }
        }

        $uniqueEmails = array_keys($cleanedEmails);

        if (empty($uniqueEmails)) {
            return redirect()
                ->route('admin.emails.create')
                ->with('error', 'No valid recipient email addresses found in the list.');
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('emails/images');
            $relativeUrl = Storage::url($path);
            $imageUrl = str_starts_with($relativeUrl, 'http') ? $relativeUrl : url($relativeUrl);
        }

        // Fetch known user names in bulk for personalization
        $usersMap = User::whereIn('email', $uniqueEmails)
            ->pluck('name', 'email')
            ->toArray();

        foreach ($uniqueEmails as $email) {
            $recipientName = $usersMap[$email] ?? null;

            Mail::to($email)->queue(
                new BulkAnnouncementMail(
                    mailSubject: $subject,
                    mailContent: $body,
                    recipientName: $recipientName,
                    imageUrl: $imageUrl,
                )
            );
        }

        $totalCount = count($uniqueEmails);
        $emailPlural = $totalCount === 1 ? 'recipient' : 'recipients';

        return redirect()
            ->route('admin.emails.create')
            ->with('success', "Email successfully queued for {$totalCount} unique {$emailPlural}.");
    }
}
