<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PeerSettingsController;
use App\Models\AppSetting;
use App\Models\DailyStudyLog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\NodeCompletion;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserAppreciation;
use App\Notifications\StudyPokeNotification;
use App\Notifications\UserAppreciationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class UserProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $profileUser = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'about' => $user->about,
            'institution' => $user->institution,
            'curriculum' => $user->curriculum ?? 'hsc',
            'group' => $user->group ?? 'science',
            'image_url' => $user->image_url,
            'facebook' => $user->facebook,
            'instagram' => $user->instagram,
            'github' => $user->github,
            'created_at' => $user->created_at?->format('M Y') ?? '2026',
            'is_verified' => $user->is_verified,
            'is_online' => $user->isOnline(),
            'last_active_at' => $user->last_active_at?->toIso8601String(),
        ];

        $appreciationsCount = $user->appreciationsReceived()->count();
        $appreciatingCount = $user->appreciationsGiven()->count();
        $isAppreciated = auth()->check()
            ? $user->appreciationsReceived()->where('appreciator_id', auth()->id())->exists()
            : false;

        // Suggested members: 1 contributors + 3 general users
        $contributorUsers = User::where('id', '!=', $user->id)
            ->whereNotNull('username')
            ->where('is_verified', true)
            ->select(['id', 'name', 'username', 'institution', 'image_path', 'about', 'is_verified'])
            ->inRandomOrder()
            ->take(1)
            ->get();

        $excludedIds = $contributorUsers->pluck('id')->push($user->id)->all();
        $remainingNeeded = 4 - $contributorUsers->count();

        $randomUsers = User::whereNotIn('id', $excludedIds)
            ->whereNotNull('username')
            ->select(['id', 'name', 'username', 'institution', 'image_path', 'about', 'is_verified'])
            ->inRandomOrder()
            ->take($remainingNeeded)
            ->get();

        $suggestedUsers = $contributorUsers->concat($randomUsers)->shuffle()->values();

        $isOwner = auth()->id() === $user->id;
        $activityPrivacy = $user->activity_privacy ?? 'public';
        $isLocked = false;
        $lockReason = null;

        if (! $isOwner) {
            if ($activityPrivacy === 'private') {
                $isLocked = true;
                $lockReason = 'private';
            } elseif ($activityPrivacy === 'appreciators_only' && ! $isAppreciated) {
                $isLocked = true;
                $lockReason = 'appreciators_only';
            }
        }

        $appreciators = $user->appreciators()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->take(15)
            ->get();

        $appreciating = $user->appreciatingUsers()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->take(15)
            ->get();

        $pokeEnabled = (bool) AppSetting::get('peer_poke_enabled', true);
        $canPoke = $pokeEnabled && ! $isOwner && ($user->allow_pokes ?? true);
        $isPokeOnCooldown = $canPoke && auth()->check() && Cache::has('study_poke:'.auth()->id().":{$user->id}");
        $pokePresets = $pokeEnabled ? PeerSettingsController::getPresets() : [];
        $pokeData = [
            'enabled' => $pokeEnabled,
            'canPoke' => $canPoke,
            'isCooldown' => $isPokeOnCooldown,
            'presets' => $pokePresets,
        ];

        // Early return if activity is locked for this visitor
        if ($isLocked) {
            return Inertia::render('User/Show', [
                'profileUser' => $profileUser,
                'appreciationsCount' => $appreciationsCount,
                'appreciatingCount' => $appreciatingCount,
                'isAppreciated' => $isAppreciated,
                'isLocked' => true,
                'lockReason' => $lockReason,
                'activityPrivacy' => $activityPrivacy,
                'suggestedUsers' => $suggestedUsers,
                'appreciators' => $appreciators,
                'appreciating' => $appreciating,
                'pokeData' => $pokeData,
            ]);
        }

        $forumPosts = ForumPost::where('user_id', $user->id)
            ->approved()
            ->with(['subject:id,name,course,slug', 'node:id,name,slug'])
            ->latest()
            ->take(5)
            ->get();
        $forumAnswers = ForumAnswer::where('user_id', $user->id)
            ->whereHas('post', fn ($q) => $q->approved())
            ->with(['post:id,body,slug,curriculum'])
            ->latest()
            ->take(5)
            ->get();

        $publishedBlogs = $user->blogs()
            ->where('is_published', true)
            ->withCount(['reactions', 'comments'])
            ->latest()
            ->take(5)
            ->get();

        $notes = Resource::where('user_id', $user->id)
            ->when(! $isOwner, fn ($q) => $q->where('status', 'approved'))
            ->with(['node:id,name,slug,subject_id', 'node.subject:id,name,course,slug'])
            ->orderByDesc('vote_score')
            ->latest('id')
            ->take(8)
            ->get();

        $syllabusProgress = $this->getSyllabusProgress($user);

        return Inertia::render('User/Show', [
            'profileUser' => $profileUser,
            'appreciationsCount' => $appreciationsCount,
            'appreciatingCount' => $appreciatingCount,
            'isAppreciated' => $isAppreciated,
            'isLocked' => false,
            'lockReason' => null,
            'activityPrivacy' => $activityPrivacy,
            'appreciators' => $appreciators,
            'appreciating' => $appreciating,
            'forumPosts' => $forumPosts,
            'forumAnswers' => $forumAnswers,
            'blogs' => $publishedBlogs,
            'notes' => $notes,
            'suggestedUsers' => $suggestedUsers,
            'pokeData' => $pokeData,
            'studyHeatmap' => DailyStudyLog::getHeatmapAndStatsForUser($user),
            'syllabusProgress' => $syllabusProgress,
        ]);
    }

    public function poke(Request $request, User $user)
    {
        $currentAuthUser = auth()->user();

        if ($currentAuthUser->id === $user->id) {
            return back()->with('error', 'You cannot poke yourself.');
        }

        $enabled = (bool) AppSetting::get('peer_poke_enabled', true);
        if (! $enabled) {
            return back()->with('error', 'Pokes are currently disabled.');
        }

        if (! ($user->allow_pokes ?? true)) {
            return back()->with('error', 'This user has disabled pokes.');
        }

        $validated = $request->validate([
            'preset_id' => 'required|string',
        ]);

        $presets = PeerSettingsController::getPresets();
        $selectedPreset = collect($presets)->firstWhere('id', $validated['preset_id']);

        if (! $selectedPreset) {
            return back()->with('error', 'The selected poke message is invalid.');
        }

        $cooldownKey = "study_poke:{$currentAuthUser->id}:{$user->id}";
        $cooldownMinutes = (int) AppSetting::get('peer_poke_cooldown_minutes', 360);
        $cooldownSeconds = max(30, $cooldownMinutes * 60);

        if (! Cache::add($cooldownKey, true, $cooldownSeconds)) {
            return back()->with('error', 'You are on cooldown for poking this peer.');
        }

        try {
            $user->notify(new StudyPokeNotification(
                sender: $currentAuthUser,
                message: $selectedPreset['message'],
                icon: $selectedPreset['icon'] ?? '⚡',
                presetId: $selectedPreset['id'],
            ));
        } catch (\Throwable $e) {
            Cache::forget($cooldownKey);
            throw $e;
        }

        return back()->with('success', "You poked {$user->name}! ⚡");
    }

    public function toggleAppreciate(User $user)
    {
        $currentAuthUser = auth()->user();

        // Cannot appreciate own profile
        if ($currentAuthUser->id === $user->id) {
            return back();
        }

        $existing = UserAppreciation::where('user_id', $user->id)
            ->where('appreciator_id', $currentAuthUser->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            UserAppreciation::create([
                'user_id' => $user->id,
                'appreciator_id' => $currentAuthUser->id,
            ]);

            $totalAppreciations = $user->appreciationsReceived()->count();
            UserAppreciationNotification::sendTo($user, $currentAuthUser, $totalAppreciations);
        }

        return back();
    }

    private function getSyllabusProgress(User $user): array
    {
        $course = $user->curriculum ?: 'hsc';
        $group = $user->group ?: 'science';
        $optionalSubjectIds = (array) ($user->optional_subject_ids ?? []);

        $subjectsQuery = Subject::where('course', $course)
            ->where('is_trackable', true)
            ->where(function ($query) use ($group, $optionalSubjectIds) {
                $query->where(function ($q) use ($group) {
                    $q->where('is_optional', false)
                        ->whereIn('group', [$group, 'common']);
                });

                if (! empty($optionalSubjectIds)) {
                    $query->orWhere(function ($q) use ($group, $optionalSubjectIds) {
                        $q->where('is_optional', true)
                            ->whereIn('group', [$group, 'common'])
                            ->whereIn('id', $optionalSubjectIds);
                    });
                }
            })
            ->orderBy('sort_order', 'asc');

        $trackableSubjects = $subjectsQuery
            ->with(['nodes' => function ($query) {
                $query->where('is_trackable', true)
                    ->with('parent:id,name,sort_order')
                    ->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'parent_id', 'name', 'slug', 'sort_order', 'weight');
            }])
            ->get(['id', 'name', 'slug', 'course', 'tailwind_format', 'icon', 'sort_order']);

        $completedNodeIds = NodeCompletion::where('user_id', $user->id)
            ->pluck('node_id')
            ->toArray();

        $completedSet = array_flip($completedNodeIds);

        $subjectBreakdown = [];
        $totalChapters = 0;
        $completedChapters = 0;
        $totalSubjectPercents = 0;
        $validSubjectsCount = 0;

        foreach ($trackableSubjects as $subj) {
            $subjTotalChapters = $subj->nodes->count();
            $subjCompletedChapters = 0;
            $subjTotalWeight = 0;
            $subjCompletedWeight = 0;

            $chaptersList = [];

            foreach ($subj->nodes as $node) {
                $nodeWeight = (int) ($node->weight ?: 2);
                $subjTotalWeight += $nodeWeight;
                $isDone = isset($completedSet[$node->id]);

                if ($isDone) {
                    $subjCompletedChapters++;
                    $subjCompletedWeight += $nodeWeight;
                }

                $chaptersList[] = [
                    'id' => $node->id,
                    'name' => $node->name,
                    'weight' => $nodeWeight,
                    'is_completed' => $isDone,
                    'parent_id' => $node->parent_id,
                    'parent' => $node->parent ? [
                        'id' => $node->parent->id,
                        'name' => $node->parent->name,
                        'sort_order' => $node->parent->sort_order,
                    ] : null,
                ];
            }

            $totalChapters += $subjTotalChapters;
            $completedChapters += $subjCompletedChapters;

            $subjPercent = $subjTotalWeight > 0 ? (int) round(($subjCompletedWeight / $subjTotalWeight) * 100) : 0;
            if ($subjTotalWeight > 0) {
                $totalSubjectPercents += ($subjCompletedWeight / $subjTotalWeight) * 100;
                $validSubjectsCount++;
            }

            $subjectBreakdown[] = [
                'id' => $subj->id,
                'name' => $subj->name,
                'slug' => $subj->slug,
                'course' => $subj->course,
                'tailwind_format' => $subj->tailwind_format,
                'icon' => $subj->icon,
                'completed' => $subjCompletedChapters,
                'total' => $subjTotalChapters,
                'percent' => $subjPercent,
                'chapters' => $chaptersList,
            ];
        }

        $overallPercent = $validSubjectsCount > 0 ? (int) round($totalSubjectPercents / $validSubjectsCount) : 0;

        return [
            'course' => $course,
            'group' => $group,
            'overallPercent' => $overallPercent,
            'completedChapters' => $completedChapters,
            'totalChapters' => $totalChapters,
            'subjects' => $subjectBreakdown,
        ];
    }

    /**
     * Fetch paginated list of users who appreciated this profile user.
     */
    public function appreciators(Request $request, User $user): JsonResponse
    {
        $paginator = $user->appreciators()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->simplePaginate(15);

        return response()->json([
            'users' => $paginator->items(),
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ]);
    }

    /**
     * Fetch paginated list of users this profile user is appreciating.
     */
    public function appreciating(Request $request, User $user): JsonResponse
    {
        $paginator = $user->appreciatingUsers()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->simplePaginate(15);

        return response()->json([
            'users' => $paginator->items(),
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ]);
    }
}
