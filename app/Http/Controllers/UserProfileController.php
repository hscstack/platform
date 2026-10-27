<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\AppSetting;
use App\Models\ChapterCompletion;
use App\Models\DailyStudyLog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
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
            'website' => $user->website,
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

        // Suggested members: 1 contributor + 3 general users (cached for 10 minutes to avoid expensive inRandomOrder queries)
        $suggestedUsers = Cache::remember("suggested_users_profile_v2_{$user->id}", now()->addMinutes(10), function () use ($user) {
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

            return $contributorUsers->concat($randomUsers)->shuffle()->values()->toArray();
        });

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

        $authId = auth()->id();

        $rawAppreciators = $user->appreciators()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->take(15)
            ->get();

        $rawAppreciating = $user->appreciatingUsers()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->take(15)
            ->get();

        $allModalUserIds = $rawAppreciators->pluck('id')->concat($rawAppreciating->pluck('id'))->unique();
        $myAppreciatingIds = $authId && $allModalUserIds->isNotEmpty()
            ? UserAppreciation::where('appreciator_id', $authId)->whereIn('user_id', $allModalUserIds)->pluck('user_id')->flip()->toArray()
            : [];

        $appreciators = $rawAppreciators->map(function ($u) use ($myAppreciatingIds) {
            $data = $u->toArray();
            $data['is_appreciated'] = isset($myAppreciatingIds[$u->id]);

            return $data;
        });

        $appreciating = $rawAppreciating->map(function ($u) use ($myAppreciatingIds) {
            $data = $u->toArray();
            $data['is_appreciated'] = isset($myAppreciatingIds[$u->id]);

            return $data;
        });

        $pokeEnabled = (bool) AppSetting::get('peer_poke_enabled', true);
        $canPoke = $pokeEnabled && ! $isOwner && ($user->allow_pokes ?? true);
        $isPokeOnCooldown = $canPoke && auth()->check() && Cache::has('study_poke:'.auth()->id().":{$user->id}");
        $pokePresets = $pokeEnabled ? SettingsController::getPresets() : [];
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
            ->when(! $isOwner, fn ($q) => $q->where('is_anonymous', false))
            ->with(['subject:id,name,course', 'chapter:id,name'])
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
            ->with(['chapter:id,name,subject_id', 'chapter.subject:id,name,course', 'subject:id,name,course'])
            ->latest('id')
            ->take(5)
            ->get();

        // Cache the heavy analytical stats (heatmap & syllabus progress) for 5 minutes for public visitors, but bypass for the user's own profile for real-time accuracy
        $studyHeatmap = $isOwner
            ? DailyStudyLog::getHeatmapAndStatsForUser($user)
            : Cache::remember("user_heatmap_{$user->id}", now()->addMinutes(5), function () use ($user) {
                return DailyStudyLog::getHeatmapAndStatsForUser($user);
            });

        $syllabusProgress = $isOwner
            ? $this->getSyllabusProgress($user)
            : Cache::remember("user_syllabus_{$user->id}", now()->addMinutes(5), function () use ($user) {
                return $this->getSyllabusProgress($user);
            });

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
            'studyHeatmap' => $studyHeatmap,
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

        $presets = SettingsController::getPresets();
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
            ->with(['chapters' => function ($query) {
                $query->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'name', 'sort_order', 'weight');
            }])
            ->get(['id', 'name', 'course', 'sort_order']);

        $completedChapterIds = ChapterCompletion::where('user_id', $user->id)
            ->pluck('chapter_id')
            ->toArray();

        $completedSet = array_flip($completedChapterIds);

        $subjectBreakdown = [];
        $totalChapters = 0;
        $completedChapters = 0;
        $totalSubjectPercents = 0;
        $validSubjectsCount = 0;

        foreach ($trackableSubjects as $subj) {
            $subjTotalChapters = $subj->chapters->count();
            $subjCompletedChapters = 0;
            $subjTotalWeight = 0;
            $subjCompletedWeight = 0;

            $chaptersList = [];

            foreach ($subj->chapters as $chapter) {
                $chapterWeight = (int) ($chapter->weight ?: 2);
                $subjTotalWeight += $chapterWeight;
                $isDone = isset($completedSet[$chapter->id]);

                if ($isDone) {
                    $subjCompletedChapters++;
                    $subjCompletedWeight += $chapterWeight;
                }

                $chaptersList[] = [
                    'id' => $chapter->id,
                    'name' => $chapter->name,
                    'weight' => $chapterWeight,
                    'is_completed' => $isDone,
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
                'course' => $subj->course,
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

        $authId = auth()->id();
        $myAppreciatingIds = $authId
            ? UserAppreciation::where('appreciator_id', $authId)->whereIn('user_id', $paginator->pluck('id'))->pluck('user_id')->flip()->toArray()
            : [];

        $items = collect($paginator->items())->map(function ($u) use ($myAppreciatingIds) {
            $data = $u->toArray();
            $data['is_appreciated'] = isset($myAppreciatingIds[$u->id]);

            return $data;
        })->all();

        return response()->json([
            'users' => $items,
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

        $authId = auth()->id();
        $myAppreciatingIds = $authId
            ? UserAppreciation::where('appreciator_id', $authId)->whereIn('user_id', $paginator->pluck('id'))->pluck('user_id')->flip()->toArray()
            : [];

        $items = collect($paginator->items())->map(function ($u) use ($myAppreciatingIds) {
            $data = $u->toArray();
            $data['is_appreciated'] = isset($myAppreciatingIds[$u->id]);

            return $data;
        })->all();

        return response()->json([
            'users' => $items,
            'has_more' => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ]);
    }
}
