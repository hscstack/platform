<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\BlogReactionNotification;
use App\Notifications\ChatMentionNotification;
use App\Notifications\ForumAnswerNotification;
use App\Notifications\ForumVoteNotification;
use App\Notifications\NodeVoteNotification;
use App\Notifications\SupportTicketUpdatedNotification;
use App\Notifications\UserAppreciationNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $targetUsers = $users->take(3);

        foreach ($targetUsers as $user) {
            $notificationData = [
                // 1. Forum Answer (Unread)
                [
                    'id' => (string) Str::uuid(),
                    'type' => ForumAnswerNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'forum_comment',
                        'title' => 'New answer on "পদার্থবিজ্ঞান ১ম পত্র ভেক্টর সমস্যা"',
                        'message' => 'Tahsin Shahriar: "তুমি ডট গুণন এবং ক্রস গুণনের সূত্র ব্যবহার করে সমাধান করতে পারো।"',
                        'url' => '/forum',
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subMinutes(5),
                    'updated_at' => now()->subMinutes(5),
                ],

                // 2. Chat Mention (Unread)
                [
                    'id' => (string) Str::uuid(),
                    'type' => ChatMentionNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'user_mention',
                        'title' => 'Mentioned in Global Chat',
                        'message' => "@{$user->username} ভাইয়া আপনার কি HSC 26 এর বায়োলজি নোটস আছে?",
                        'url' => '/chat',
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subMinutes(18),
                    'updated_at' => now()->subMinutes(18),
                ],

                // 3. User Appreciation (Unread)
                [
                    'id' => (string) Str::uuid(),
                    'type' => UserAppreciationNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'user_appreciation',
                        'title' => 'New Profile Appreciation ❤️',
                        'message' => 'Sakib Ahmed sent an appreciation: "Thanks for helping the community with notes!"',
                        'url' => "/u/{$user->username}",
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subHours(1),
                    'updated_at' => now()->subHours(1),
                ],

                // 4. Forum Vote Milestone (Unread)
                [
                    'id' => (string) Str::uuid(),
                    'type' => ForumVoteNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'forum_vote',
                        'title' => 'Question Upvoted 🚀',
                        'message' => 'Your question on Organic Chemistry reactions received 5 upvotes!',
                        'url' => '/forum',
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subHours(3),
                    'updated_at' => now()->subHours(3),
                ],

                // 5. Blog Reaction (Unread)
                [
                    'id' => (string) Str::uuid(),
                    'type' => BlogReactionNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'blog_reaction',
                        'title' => 'Article Milestone Reached 🎉',
                        'message' => 'Your article "HSC 26 Complete Study Roadmap" reached 25 reactions!',
                        'url' => '/blogs',
                    ]),
                    'read_at' => null,
                    'created_at' => now()->subHours(6),
                    'updated_at' => now()->subHours(6),
                ],

                // 6. Support Ticket Update (Read)
                [
                    'id' => (string) Str::uuid(),
                    'type' => SupportTicketUpdatedNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'support_ticket',
                        'title' => 'Support Ticket #104 Updated',
                        'message' => 'Staff resolved your support ticket regarding course materials.',
                        'url' => '/support/my-tickets',
                    ]),
                    'read_at' => now()->subHours(12),
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],

                // 7. Node Vote (Read)
                [
                    'id' => (string) Str::uuid(),
                    'type' => NodeVoteNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'node_vote',
                        'title' => 'Resource Folder Upvoted 👍',
                        'message' => 'Your shared resource folder in Higher Math received 10 upvotes!',
                        'url' => '/',
                    ]),
                    'read_at' => now()->subDay(),
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],

                // 8. Welcome Notification (Read)
                [
                    'id' => (string) Str::uuid(),
                    'type' => WelcomeNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => 'welcome',
                        'title' => 'Welcome to HSCStack! 🎓',
                        'message' => 'Join discussions in the forum, access free notes, and connect with peers.',
                        'url' => '/profile',
                    ]),
                    'read_at' => now()->subDays(3),
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ],
            ];

            DB::table('notifications')->insert($notificationData);
        }
    }
}
