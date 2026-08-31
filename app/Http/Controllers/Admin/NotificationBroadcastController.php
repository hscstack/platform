<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationBroadcastController extends Controller
{
    /**
     * Show the broadcast notification composer.
     */
    public function create(): Response
    {
        $allUsersCount = User::count();
        $studentsCount = User::doesntHave('roles')->count();
        $staffCount = User::has('roles')->count();

        return Inertia::render('admin/NotificationSend', [
            'totalUsersCount' => $allUsersCount,
            'studentsCount' => $studentsCount,
            'staffCount' => $staffCount,
        ]);
    }

    /**
     * Broadcast an in-app notification to selected user scope.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_type' => ['required', 'in:all,students,staff,single'],
            'recipient_username' => ['required_if:recipient_type,single', 'nullable', 'string', 'max:255', 'exists:users,username'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:255'],
        ]);

        $title = $validated['title'];
        $message = $validated['message'];
        $url = $validated['url'] ?? null;

        // Single user mode
        if ($validated['recipient_type'] === 'single') {
            $user = User::where('username', $validated['recipient_username'])->firstOrFail();
            $user->notify(new GeneralNotification($title, $message, $url));

            return redirect()
                ->route('admin.notifications.create')
                ->with('success', "Notification sent successfully to @{$user->username}.");
        }

        // Bulk broadcast
        $query = User::query();

        if ($validated['recipient_type'] === 'students') {
            $query->doesntHave('roles');
        } elseif ($validated['recipient_type'] === 'staff') {
            $query->has('roles');
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            return redirect()
                ->route('admin.notifications.create')
                ->with('error', 'No recipients found for the selected scope.');
        }

        $query->chunkById(200, function ($users) use ($title, $message, $url) {
            foreach ($users as $user) {
                $user->notify(new GeneralNotification($title, $message, $url));
            }
        });

        $targetLabel = match ($validated['recipient_type']) {
            'students' => 'students',
            'staff' => 'staff members',
            default => 'all users',
        };

        return redirect()
            ->route('admin.notifications.create')
            ->with('success', "In-app notification successfully broadcast to {$totalCount} {$targetLabel}.");
    }
}
