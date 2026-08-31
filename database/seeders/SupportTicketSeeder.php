<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereDoesntHave('roles')->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        $admin = User::role('admin')->first() ?? $users->first();

        $tickets = [
            [
                'category' => SupportTicket::CATEGORY_BUG_REPORT,
                'subject' => 'Math formula LaTeX rendering issue in Chapter 3',
                'message' => 'On mobile view, large integral equations in the Higher Math chapter are overflowing beyond the screen margin.',
                'status' => SupportTicket::STATUS_OPEN,
                'admin_reply' => null,
                'replied_by' => null,
                'replied_at' => null,
            ],
            [
                'category' => SupportTicket::CATEGORY_APPLY_ROLE,
                'subject' => 'Application for HSC Biology Contributor',
                'message' => 'Hello team! I am a 2nd year medical student and would love to contribute high-yield botany and zoology short notes and MCQs.',
                'status' => SupportTicket::STATUS_IN_PROGRESS,
                'admin_reply' => 'Thanks for applying! We are reviewing your profile and sample notes. We will get back to you shortly.',
                'replied_by' => $admin->id,
                'replied_at' => now()->subHours(5),
            ],
            [
                'category' => SupportTicket::CATEGORY_MISSING_RESOURCE,
                'subject' => 'Missing PDF solution sheet for 2023 Dhaka Board Physics',
                'message' => 'The download link for the 2023 board question solution seems to return a 404 error.',
                'status' => SupportTicket::STATUS_RESOLVED,
                'admin_reply' => 'Fixed! The PDF has been re-uploaded and the link is now working properly.',
                'replied_by' => $admin->id,
                'replied_at' => now()->subDays(1),
            ],
            [
                'category' => SupportTicket::CATEGORY_GENERAL,
                'subject' => 'Inquiry regarding SSC 2027 syllabus updates',
                'message' => 'Will new chapters added in the revised curriculum be covered on the platform before the next term exam?',
                'status' => SupportTicket::STATUS_CLOSED,
                'admin_reply' => 'Yes, our content team is currently uploading lectures and notes aligned with the 2027 syllabus.',
                'replied_by' => $admin->id,
                'replied_at' => now()->subDays(3),
            ],
        ];

        foreach ($tickets as $tData) {
            $user = $users->random();
            SupportTicket::create([
                'ticket_number' => 'TKT-'.date('Y').'-'.strtoupper(Str::random(5)),
                'user_id' => $user->id,
                'category' => $tData['category'],
                'subject' => $tData['subject'],
                'message' => $tData['message'],
                'status' => $tData['status'],
                'admin_reply' => $tData['admin_reply'],
                'replied_by' => $tData['replied_by'],
                'replied_at' => $tData['replied_at'],
            ]);
        }
    }
}
