<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $ticketsCount = $user ? SupportTicket::where('user_id', $user->id)->count() : 0;
        $openTicketsCount = $user ? SupportTicket::where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])
            ->count() : 0;

        return Inertia::render('Support', [
            'ticketsCount' => $ticketsCount,
            'openTicketsCount' => $openTicketsCount,
            'categories' => SupportTicket::getCategories(),
        ]);
    }

    public function myTickets(Request $request)
    {
        $user = $request->user();

        $tickets = SupportTicket::where('user_id', $user->id)
            ->with('repliedBy:id,name,username,image_path')
            ->latest()
            ->get();

        return Inertia::render('SupportMyTickets', [
            'tickets' => $tickets,
            'categories' => SupportTicket::getCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $openTicketsCount = SupportTicket::where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])
            ->count();

        if ($openTicketsCount >= 3) {
            return back()->withErrors([
                'general' => 'আপনার ইতিমধ্যে ৩টি খোলা টিকেট পেন্ডিং রয়েছে। নতুন টিকেট খোলার আগে অনুগ্রহ করে পূর্ববর্তী টিকেটের সমাধান হওয়া পর্যন্ত অপেক্ষা করুন।',
            ]);
        }

        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(SupportTicket::getCategories())),
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets');
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return redirect()->route('support.my-tickets')->with('success', "Ticket {$ticket->ticket_number} created successfully! Our team will review and reply.");
    }
}
