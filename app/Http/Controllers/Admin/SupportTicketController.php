<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SupportTicketMail;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $category = $request->query('category');
        $search = $request->query('search');

        $query = SupportTicket::with([
            'user:id,name,username,email,image_path',
            'repliedBy:id,name,username,image_path',
        ]);

        if ($status && in_array($status, [
            SupportTicket::STATUS_OPEN,
            SupportTicket::STATUS_IN_PROGRESS,
            SupportTicket::STATUS_RESOLVED,
            SupportTicket::STATUS_CLOSED,
        ])) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count(),
            'in_progress' => SupportTicket::where('status', SupportTicket::STATUS_IN_PROGRESS)->count(),
            'resolved' => SupportTicket::where('status', SupportTicket::STATUS_RESOLVED)->count(),
            'closed' => SupportTicket::where('status', SupportTicket::STATUS_CLOSED)->count(),
        ];

        return Inertia::render('admin/Tickets', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'category' => $category,
                'search' => $search,
            ],
            'categories' => SupportTicket::getCategories(),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved,closed',
            'admin_reply' => 'nullable|string|max:5000',
        ]);

        $updates = [
            'status' => $validated['status'],
        ];

        if ($request->has('admin_reply') && $validated['admin_reply'] !== null) {
            $updates['admin_reply'] = $validated['admin_reply'];
            $updates['replied_by'] = $request->user()->id;
            $updates['replied_at'] = now();
        }

        $ticket->update($updates);

        $ticket->load('user');

        // Send in-app notification to ticket owner
        if ($ticket->user && $ticket->user_id !== $request->user()->id) {
            $hasReply = ! empty($updates['admin_reply']);
            $ticket->user->notify(new SupportTicketNotification($ticket, $hasReply));
        }

        if ($ticket->user?->email) {
            Mail::to($ticket->user->email)->queue(SupportTicketMail::forStatusUpdated($ticket));
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} updated successfully.");
    }

    public function destroy(SupportTicket $ticket)
    {
        if ($ticket->attachment_path && ! str($ticket->attachment_path)->startsWith(['http://', 'https://'])) {
            Storage::delete($ticket->attachment_path);
        }

        $ticket->delete();

        return back()->with('success', 'Ticket deleted successfully.');
    }
}
