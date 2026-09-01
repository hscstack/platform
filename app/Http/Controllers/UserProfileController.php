<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\BlogReaction;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Node;
use App\Models\Resource;
use App\Models\User;
use App\Models\UserAppreciation;
use App\Notifications\UserAppreciationNotification;
use Inertia\Inertia;

class UserProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', $username)
            ->firstOrFail();

        // Forum Contributions
        $questionsCount = ForumPost::where('user_id', $user->id)->count();
        $answersCount = ForumAnswer::where('user_id', $user->id)->count();
        $forumPosts = ForumPost::where('user_id', $user->id)
            ->with(['subject:id,name,course,slug', 'node:id,name,slug'])
            ->latest()
            ->take(5)
            ->get();
        $forumAnswers = ForumAnswer::where('user_id', $user->id)
            ->with(['post:id,title,slug,curriculum,is_answered'])
            ->latest()
            ->take(5)
            ->get();

        // Contributor Stats & Blogs
        $publishedBlogs = $user->blogs()
            ->where('is_published', true)
            ->withCount(['reactions', 'comments'])
            ->latest()
            ->take(5)
            ->get();
        $blogsCount = $user->blogs()->where('is_published', true)->count();
        $totalBlogViews = (int) $user->blogs()->where('is_published', true)->sum('views');
        $sharedResourcesCount = Resource::where('user_id', $user->id)->count();

        // Appreciations (Received & Given)
        $appreciationsCount = $user->appreciationsReceived()->count();
        $appreciatingCount = $user->appreciationsGiven()->count();
        $isAppreciated = auth()->check()
            ? $user->appreciationsReceived()->where('appreciator_id', auth()->id())->exists()
            : false;

        $appreciators = $user->appreciators()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution'])
            ->inRandomOrder()
            ->take(30)
            ->get();

        $appreciating = $user->appreciatingUsers()
            ->select(['users.id', 'users.name', 'users.username', 'users.image_path', 'users.institution'])
            ->inRandomOrder()
            ->take(30)
            ->get();

        // Recent Community Activities
        $recentForumPosts = ForumPost::where('user_id', $user->id)
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

        // Suggested / Discover community members: 2 contributors + 2 general users
        $contributorUsers = User::where('id', '!=', $user->id)
            ->whereNotNull('username')
            ->whereHas('roles')
            ->select(['id', 'name', 'username', 'institution', 'image_path', 'about'])
            ->inRandomOrder()
            ->take(2)
            ->get();

        $excludedIds = $contributorUsers->pluck('id')->push($user->id)->all();
        $remainingNeeded = 4 - $contributorUsers->count();

        $randomUsers = User::whereNotIn('id', $excludedIds)
            ->whereNotNull('username')
            ->select(['id', 'name', 'username', 'institution', 'image_path', 'about'])
            ->inRandomOrder()
            ->take($remainingNeeded)
            ->get();

        $suggestedUsers = $contributorUsers->concat($randomUsers)->shuffle()->values();

        return Inertia::render('User/Show', [
            'profileUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'about' => $user->about,
                'institution' => $user->institution,
                'image_url' => $user->image_url,
                'facebook' => $user->facebook,
                'instagram' => $user->instagram,
                'github' => $user->github,
                'created_at' => $user->created_at?->format('M Y') ?? '2026',
                'is_verified' => $user->is_verified,
                'is_staff' => $user->is_verified,
            ],
            'stats' => [
                'questionsCount' => $questionsCount,
                'answersCount' => $answersCount,
                'blogsCount' => $blogsCount,
                'sharedResourcesCount' => $sharedResourcesCount,
                'totalBlogViews' => (int) $totalBlogViews,
            ],
            'appreciationsCount' => $appreciationsCount,
            'appreciatingCount' => $appreciatingCount,
            'isAppreciated' => $isAppreciated,
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
        ]);
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
}
