<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 5) {
            $users = User::factory()->count(10)->create();
        }

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first() ?? $users->first();
        $studentA = $users->where('id', '!=', $admin->id)->first() ?? $users->first();
        $studentB = $users->whereNotIn('id', [$admin->id, $studentA->id])->first() ?? $studentA;
        $studentC = $users->whereNotIn('id', [$admin->id, $studentA->id, $studentB->id])->first() ?? $studentA;
        $studentD = $users->whereNotIn('id', [$admin->id, $studentA->id, $studentB->id, $studentC->id])->first() ?? $studentB;

        $now = now();

        $conversation = [
            [
                'user' => $admin,
                'content' => 'Welcome to the platform global community chat! Feel free to discuss topics, ask doubts, and collaborate.',
                'minutes_ago' => 180,
                'reply_index' => null,
                'reactions' => ['🎉', '❤️', '👏'],
            ],
            [
                'user' => $studentA,
                'content' => 'Hey everyone! Anyone practicing HSC Physics 1st paper vector problems today?',
                'minutes_ago' => 150,
                'reply_index' => null,
                'reactions' => ['👍', '🔥'],
            ],
            [
                'user' => $studentB,
                'content' => "Yes @{$studentA->username}! I was just reviewing dot and cross products for river-boat problems.",
                'minutes_ago' => 135,
                'reply_index' => 1,
                'reactions' => ['🔥'],
            ],
            [
                'user' => $studentC,
                'content' => 'Does anyone have a quick summary of Organic Chemistry reaction pathways?',
                'minutes_ago' => 90,
                'reply_index' => null,
                'reactions' => ['👍'],
            ],
            [
                'user' => $studentD,
                'content' => "Check the resources tab! @{$studentC->username} there are high-yield notes uploaded for organic mechanisms.",
                'minutes_ago' => 75,
                'reply_index' => 3,
                'reactions' => ['❤️', '👏'],
            ],
            [
                'user' => $studentA,
                'content' => 'Higher Math integration cheat sheet in the library is super helpful for board exam prep!',
                'minutes_ago' => 45,
                'reply_index' => null,
                'reactions' => ['🔥', '👍'],
            ],
            [
                'user' => $studentB,
                'content' => "Agreed! Special thanks to @{$admin->username} and contributors for keeping the materials updated.",
                'minutes_ago' => 30,
                'reply_index' => 5,
                'reactions' => ['❤️', '🎉'],
            ],
            [
                'user' => $studentC,
                'content' => 'Best of luck with everyone’s test exam preparations! Keep pushing forward 💪',
                'minutes_ago' => 10,
                'reply_index' => null,
                'reactions' => ['🔥', '👏'],
            ],
        ];

        $createdMessages = [];

        foreach ($conversation as $index => $item) {
            $replyToId = null;
            $replyToContent = null;

            if ($item['reply_index'] !== null && isset($createdMessages[$item['reply_index']])) {
                $parent = $createdMessages[$item['reply_index']];
                $replyToId = $parent->id;
                $replyToContent = Str::limit($parent->content, 97, '...');
            }

            $messageTime = (clone $now)->subMinutes($item['minutes_ago']);

            $message = ChatMessage::create([
                'user_id' => $item['user']->id,
                'content' => $item['content'],
                'reply_to_id' => $replyToId,
                'reply_to_content' => $replyToContent,
                'created_at' => $messageTime,
                'updated_at' => $messageTime,
            ]);

            $createdMessages[$index] = $message;

            // Add reactions from random users
            foreach ($item['reactions'] as $emoji) {
                $reactorCount = min(rand(1, 3), $users->count());
                $reactors = $users->random($reactorCount);

                foreach ($reactors as $reactor) {
                    ChatMessageReaction::firstOrCreate([
                        'chat_message_id' => $message->id,
                        'user_id' => $reactor->id,
                        'emoji' => $emoji,
                    ]);
                }
            }
        }
    }
}
