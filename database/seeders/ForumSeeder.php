<?php

namespace Database\Seeders;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure some demo students exist
        $users = User::factory()->count(10)->create();
        $subjects = Subject::with('nodes')->get();

        $sampleQuestions = [
            [
                'curriculum' => 'hsc',
                'title' => 'How to solve 2nd order differential equations in Physics Paper 1?',
                'body' => "I am having trouble understanding the standard method to solve second-order differential equations representing simple harmonic motion (SHM).\n\nCould someone please explain the step-by-step approach and how boundary conditions apply?",
                'is_answered' => true,
                'is_locked' => false,
                'is_published' => true,
            ],
            [
                'curriculum' => 'hsc',
                'title' => 'Difference between SN1 and SN2 reaction mechanisms in Organic Chemistry',
                'body' => "Can anyone summarize the main differences between SN1 and SN2 nucleophilic substitution reactions?\n\nSpecifically regarding solvent polarity, carbocation stability, and stereochemistry inversion.",
                'is_answered' => true,
                'is_locked' => false,
                'is_published' => true,
            ],
            [
                'curriculum' => 'hsc',
                'title' => 'Important formulas cheat sheet for Higher Math Integration',
                'body' => 'Which substitution techniques and trigonometric identities appear most frequently in board exam questions for indefinite integrals?',
                'is_answered' => false,
                'is_locked' => false,
                'is_published' => true,
            ],
            [
                'curriculum' => 'ssc',
                'title' => 'How to balance redox chemical equations easily in SSC Chemistry Chapter 7?',
                'body' => 'Is there an intuitive trick for balancing redox equations with oxidation numbers without memorizing every single equation in the textbook?',
                'is_answered' => true,
                'is_locked' => false,
                'is_published' => true,
            ],
            [
                'curriculum' => 'ssc',
                'title' => 'SSC General Science - Electricity and Ohm\'s law problem clarification',
                'body' => 'When resistors are in parallel, why does the equivalent resistance decrease below the smallest resistor? Looking for a physical intuition explanation.',
                'is_answered' => false,
                'is_locked' => false,
                'is_published' => true,
            ],
            [
                'curriculum' => 'hsc',
                'title' => '[RESOLVED/LOCKED] Official guidelines for HSC Board practical submissions',
                'body' => 'This discussion contains the verified laboratory notebook guidelines and submission deadlines for physics and chemistry practical exams.',
                'is_answered' => true,
                'is_locked' => true,
                'is_published' => true,
            ],
            [
                'curriculum' => 'hsc',
                'title' => 'Spam discussion flagged by community for inappropriate content',
                'body' => 'Check out this random link http://suspicious-site.example.com to buy leaked question papers!',
                'is_answered' => false,
                'is_locked' => true,
                'is_published' => false,
            ],
        ];

        foreach ($sampleQuestions as $index => $qData) {
            $author = $users->random();
            $curriculumSubjects = $subjects->where('course', $qData['curriculum']);
            $subject = $curriculumSubjects->isNotEmpty() ? $curriculumSubjects->random() : null;
            $node = $subject && $subject->nodes->isNotEmpty() ? $subject->nodes->random() : null;

            $baseSlug = Str::slug($qData['title']) ?: 'post';
            $slug = "{$baseSlug}-".Str::lower(Str::random(5));

            $post = ForumPost::create([
                'user_id' => $author->id,
                'subject_id' => $subject?->id,
                'node_id' => $node?->id,
                'curriculum' => $qData['curriculum'],
                'title' => $qData['title'],
                'slug' => $slug,
                'body' => $qData['body'],
                'is_answered' => $qData['is_answered'],
                'is_locked' => $qData['is_locked'],
                'is_published' => $qData['is_published'],
                'vote_score' => 0,
                'answers_count' => 0,
            ]);

            // Add sample answers if question is answered
            if ($qData['is_answered']) {
                $answeringUser = $users->where('id', '!=', $author->id)->random();

                $answer = ForumAnswer::create([
                    'forum_post_id' => $post->id,
                    'user_id' => $answeringUser->id,
                    'body' => "Great question! Here is a concise explanation:\n\n1. Identify the primary equation.\n2. Apply initial/boundary conditions.\n3. Verify the units to confirm consistency.",
                    'vote_score' => 3,
                    'upvotes_count' => 3,
                    'downvotes_count' => 0,
                ]);

                // Upvotes on answer
                $voters = $users->random(min(3, $users->count()));
                foreach ($voters as $voter) {
                    ForumVote::create([
                        'user_id' => $voter->id,
                        'voteable_type' => ForumAnswer::class,
                        'voteable_id' => $answer->id,
                        'value' => 1,
                    ]);
                }

                // Add a reply
                $replyUser = $users->random();
                ForumAnswer::create([
                    'forum_post_id' => $post->id,
                    'parent_id' => $answer->id,
                    'user_id' => $replyUser->id,
                    'body' => 'Thank you so much, this cleared up my confusion immediately!',
                    'vote_score' => 1,
                    'upvotes_count' => 1,
                ]);

                $post->update([
                    'answers_count' => 2,
                    'vote_score' => 4,
                    'upvotes_count' => 4,
                ]);

                // Upvotes on post
                foreach ($users->take(4) as $voter) {
                    ForumVote::create([
                        'user_id' => $voter->id,
                        'voteable_type' => ForumPost::class,
                        'voteable_id' => $post->id,
                        'value' => 1,
                    ]);
                }
            }
        }
    }
}
