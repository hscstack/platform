<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->count() < 3) {
            $users = User::factory()->count(5)->create();
        }

        // 1. Create a few demo chat messages to report
        $chatUser = $users->first();
        $chatMsg1 = ChatMessage::create([
            'user_id' => $chatUser->id,
            'content' => 'Hey check out this unauthorized Telegram exam leak channel!',
        ]);

        $chatMsg2 = ChatMessage::create([
            'user_id' => $chatUser->id,
            'content' => 'Stop posting repeated questions here, read the book!',
        ]);

        // 2. Chat message reports
        $reporter1 = $users->skip(1)->first();
        $reporter2 = $users->skip(2)->first();

        Report::create([
            'reporter_id' => $reporter1->id,
            'reported_user_id' => $chatUser->id,
            'reported_user_name' => $chatUser->name,
            'reported_user_username' => $chatUser->username,
            'reportable_type' => ChatMessage::class,
            'reportable_id' => $chatMsg1->id,
            'content_snapshot' => $chatMsg1->content,
            'reason' => 'Promotion of exam leak / cheating links',
            'status' => 'pending',
        ]);

        Report::create([
            'reporter_id' => $reporter2->id,
            'reported_user_id' => $chatUser->id,
            'reported_user_name' => $chatUser->name,
            'reported_user_username' => $chatUser->username,
            'reportable_type' => ChatMessage::class,
            'reportable_id' => $chatMsg2->id,
            'content_snapshot' => $chatMsg2->content,
            'reason' => 'Harassment and rude tone',
            'status' => 'reviewed',
        ]);

        // 3. Forum post reports
        $forumPost = ForumPost::where('is_published', false)->first() ?? ForumPost::first();
        if ($forumPost) {
            $author = $forumPost->user;
            Report::create([
                'reporter_id' => $reporter1->id,
                'reported_user_id' => $author?->id,
                'reported_user_name' => $author?->name,
                'reported_user_username' => $author?->username,
                'reportable_type' => ForumPost::class,
                'reportable_id' => $forumPost->id,
                'content_snapshot' => $forumPost->title."\n\n".$forumPost->body,
                'reason' => 'Spam content and external advertising',
                'status' => 'pending',
            ]);
        }

        // 4. Forum answer reports
        $forumAnswer = ForumAnswer::first();
        if ($forumAnswer) {
            $author = $forumAnswer->user;
            Report::create([
                'reporter_id' => $reporter2->id,
                'reported_user_id' => $author?->id,
                'reported_user_name' => $author?->name,
                'reported_user_username' => $author?->username,
                'reportable_type' => ForumAnswer::class,
                'reportable_id' => $forumAnswer->id,
                'content_snapshot' => $forumAnswer->body,
                'reason' => 'Misleading or incorrect scientific information',
                'status' => 'dismissed',
            ]);
        }
    }
}
