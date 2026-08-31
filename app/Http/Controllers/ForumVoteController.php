<?php

namespace App\Http\Controllers;

use App\Models\ForumAnswer;
use App\Models\ForumPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumVoteController extends Controller
{
    public function votePost(Request $request, ForumPost $post): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $value = (int) $validated['value'];
        $userId = auth()->id();

        DB::transaction(function () use ($post, $userId, $value) {
            $lockedPost = ForumPost::where('id', $post->id)->lockForUpdate()->first();
            if (! $lockedPost) {
                return;
            }

            $existing = $lockedPost->votes()->where('user_id', $userId)->lockForUpdate()->first();

            if ($existing) {
                if ($existing->value === $value) {
                    $existing->delete();
                } else {
                    $existing->update(['value' => $value]);
                }
            } else {
                $lockedPost->votes()->create([
                    'user_id' => $userId,
                    'value' => $value,
                ]);
            }

            $upvotes = (int) $lockedPost->votes()->where('value', 1)->count();
            $downvotes = (int) $lockedPost->votes()->where('value', -1)->count();

            $lockedPost->updateQuietly([
                'upvotes_count' => $upvotes,
                'downvotes_count' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]);
        });

        return back();
    }

    public function voteAnswer(Request $request, ForumAnswer $answer): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isBanned()) {
            $bannedUntilFormatted = $user->banned_until->diffForHumans();

            return back()->with('error', "You are temporarily suspended from community participation until {$user->banned_until->toDateTimeString()} ({$bannedUntilFormatted}).");
        }

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $value = (int) $validated['value'];
        $userId = auth()->id();

        DB::transaction(function () use ($answer, $userId, $value) {
            $lockedAnswer = ForumAnswer::where('id', $answer->id)->lockForUpdate()->first();
            if (! $lockedAnswer) {
                return;
            }

            $existing = $lockedAnswer->votes()->where('user_id', $userId)->lockForUpdate()->first();

            if ($existing) {
                if ($existing->value === $value) {
                    $existing->delete();
                } else {
                    $existing->update(['value' => $value]);
                }
            } else {
                $lockedAnswer->votes()->create([
                    'user_id' => $userId,
                    'value' => $value,
                ]);
            }

            $upvotes = (int) $lockedAnswer->votes()->where('value', 1)->count();
            $downvotes = (int) $lockedAnswer->votes()->where('value', -1)->count();

            $lockedAnswer->updateQuietly([
                'upvotes_count' => $upvotes,
                'downvotes_count' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]);
        });

        return back();
    }
}
