<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PeerSettingsController;
use App\Models\AppSetting;
use App\Models\BlogComment;
use App\Models\BlogReaction;
use App\Models\DailyStudyLog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Node;
use App\Models\NodeCompletion;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserAppreciation;
use App\Notifications\StudyPokeNotification;
use App\Notifications\UserAppreciationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class UserProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', $username)
            ->firstOrFail();

        $profileUser = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'about' => $user->about,
            'institution' => $user->institution,
            'curriculum' => $user->curriculum ?? 'hsc',
            'image_url' => $user->image_url,
            'facebook' => $user->facebook,
            'instagram' => $user->instagram,
            'github' => $user->github,
            'created_at' => $user->created_at?->format('M Y') ?? '2026',
            'is_verified' => $user->is_verified,
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
            ->take(30)
            ->get();

        $appreciating = $user->appreciatingUsers()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution', 'users.is_verified'])
            ->latest('user_appreciations.id')
            ->take(30)
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
            ->with(['post:id,title,slug,curriculum,is_answered'])
            ->latest()
            ->take(5)
            ->get();

        $publishedBlogs = $user->blogs()
            ->where('is_published', true)
            ->withCount(['reactions', 'comments'])
            ->latest()
            ->take(5)
            ->get();

        // Recent Community Activities
        $recentForumPosts = ForumPost::where('user_id', $user->id)
            ->approved()
            ->latest('id')
            ->take(3)
            ->get()
            ->map(fn ($post) => [
                'type' => 'forum_post',
                'title' => $post->title,
                'subtitle' => strtoupper($post->curriculum).' Forum Question',
                'url' => "/forum/questions/{$post->slug}",
                'created_at' => $post->created_at?->diffForHumans(),
                'timestamp' => $post->created_at?->timestamp ?? 0,
            ]);

        $recentForumAnswers = ForumAnswer::where('user_id', $user->id)
            ->whereHas('post', fn ($q) => $q->approved())
            ->with('post:id,title,slug')
            ->latest('id')
            ->take(3)
            ->get()
            ->map(fn ($ans) => [
                'type' => 'forum_answer',
                'title' => $ans->post?->title ?? 'Forum Question',
                'content' => $ans->body,
                'url' => $ans->post ? "/forum/questions/{$ans->post->slug}" : null,
                'created_at' => $ans->created_at?->diffForHumans(),
                'timestamp' => $ans->created_at?->timestamp ?? 0,
            ])
            ->filter(fn ($item) => $item['url'] !== null);

        $recentFolders = Node::where('user_id', $user->id)
            ->with([
                'subject:id,name,slug',
                'parent:id,name,slug',
                'parent.parent:id,name,slug',
            ])
            ->latest('id')
            ->take(3)
            ->get()
            ->map(function ($node) {
                $url = $this->buildNodeUrl($node);

                return [
                    'type' => 'folder',
                    'title' => $node->name,
                    'subtitle' => $node->subject?->name.($node->parent ? ' · '.$node->parent->name : ''),
                    'url' => $url,
                    'created_at' => $node->created_at?->diffForHumans(),
                    'timestamp' => $node->created_at?->timestamp ?? 0,
                ];
            })
            ->filter()
            ->values();

        $recentUploads = Resource::where('user_id', $user->id)
            ->with(['node:id,name,subject_id', 'node.subject:id,name'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($item) => [
                'type' => 'upload',
                'title' => $item->title,
                'subtitle' => $item->node?->subject?->name.' · '.$item->node?->name,
                'resource_type' => $item->resource_type,
                'url' => "/resources/{$item->id}",
                'created_at' => $item->created_at?->diffForHumans(),
                'timestamp' => $item->created_at?->timestamp ?? 0,
            ]);

        $recentReactions = BlogReaction::where('user_id', $user->id)
            ->with('blog:id,title,slug')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($item) => [
                'type' => 'reaction',
                'title' => $item->blog?->title,
                'url' => $item->blog ? "/blogs/{$item->blog->slug}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
                'timestamp' => $item->created_at?->timestamp ?? 0,
            ])
            ->filter(fn ($item) => $item['title'] !== null);

        $recentComments = BlogComment::where('user_id', $user->id)
            ->with('blog:id,title,slug')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($item) => [
                'type' => 'comment',
                'title' => $item->blog?->title,
                'content' => $item->content,
                'url' => $item->blog ? "/blogs/{$item->blog->slug}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
                'timestamp' => $item->created_at?->timestamp ?? 0,
            ])
            ->filter(fn ($item) => $item['title'] !== null);

        $recentAppreciations = UserAppreciation::where('appreciator_id', $user->id)
            ->with('user:id,name,username')
            ->latest('id')
            ->take(3)
            ->get()
            ->map(fn ($item) => [
                'type' => 'appreciation',
                'title' => $item->user?->name,
                'username' => $item->user?->username,
                'url' => $item->user ? "/u/{$item->user->username}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
                'timestamp' => $item->created_at?->timestamp ?? 0,
            ])
            ->filter(fn ($item) => $item['title'] !== null)
            ->values();

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
            'recentActivities' => [
                'forum_posts' => $recentForumPosts->values(),
                'forum_answers' => $recentForumAnswers->values(),
                'folders' => $recentFolders->values(),
                'uploads' => $recentUploads->values(),
                'reactions' => $recentReactions->values(),
                'comments' => $recentComments->values(),
                'appreciations' => $recentAppreciations->values(),
            ],
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
            $user->notify(new UserAppreciationNotification($currentAuthUser, $totalAppreciations));
        }

        return back();
    }

    private function buildNodeUrl(Node $node): ?string
    {
        if (! $node->subject) {
            return null;
        }

        $slugs = [];
        $curr = $node;
        while ($curr) {
            array_unshift($slugs, $curr->slug);
            $curr = $curr->parent;
        }

        return '/'.$node->subject->slug.'/'.implode('/', $slugs);
    }

    private function getSyllabusProgress(User $user): array
    {
        $course = $user->curriculum ?: 'hsc';
        $trackableSubjects = Subject::where('course', $course)
            ->where('is_trackable', true)
            ->orderBy('sort_order', 'asc')
            ->with(['nodes' => function ($query) {
                $query->where('is_trackable', true)
                    ->orderBy('sort_order', 'asc')
                    ->select('id', 'subject_id', 'name', 'slug', 'sort_order');
            }])
            ->get(['id', 'name', 'english_name', 'slug', 'course', 'tailwind_format', 'icon', 'sort_order']);

        $completedNodeIds = NodeCompletion::where('user_id', $user->id)
            ->pluck('node_id')
            ->toArray();

        $completedSet = array_flip($completedNodeIds);

        $subjectBreakdown = [];
        $totalChapters = 0;
        $completedChapters = 0;

        foreach ($trackableSubjects as $subj) {
            $subjTotal = $subj->nodes->count();
            $subjCompleted = 0;
            foreach ($subj->nodes as $node) {
                if (isset($completedSet[$node->id])) {
                    $subjCompleted++;
                }
            }

            $totalChapters += $subjTotal;
            $completedChapters += $subjCompleted;

            $subjPercent = $subjTotal > 0 ? (int) round(($subjCompleted / $subjTotal) * 100) : 0;

            $subjectBreakdown[] = [
                'id' => $subj->id,
                'name' => $subj->name,
                'english_name' => $subj->english_name,
                'slug' => $subj->slug,
                'course' => $subj->course,
                'tailwind_format' => $subj->tailwind_format,
                'icon' => $subj->icon,
                'completed' => $subjCompleted,
                'total' => $subjTotal,
                'percent' => $subjPercent,
            ];
        }

        $overallPercent = $totalChapters > 0 ? (int) round(($completedChapters / $totalChapters) * 100) : 0;

        return [
            'course' => $course,
            'overallPercent' => $overallPercent,
            'completedChapters' => $completedChapters,
            'totalChapters' => $totalChapters,
            'subjects' => $subjectBreakdown,
        ];
    }
}
