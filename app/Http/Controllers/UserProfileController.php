<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\BlogReaction;
use App\Models\Resource;
use App\Models\ResourceCompletion;
use App\Models\User;
use Inertia\Inertia;

class UserProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', $username)
            ->firstOrFail();

        $user->load('roles:id,name');

        // Study Completions
        $completedResourcesCount = $user->completedResources()->count();
        $recentCompletions = $user->completedResources()
            ->with(['node:id,name,subject_id', 'node.subject:id,name'])
            ->latest('resource_completions.created_at')
            ->take(6)
            ->get();

        // Contributor Stats & Blogs
        $publishedBlogs = $user->blogs()
            ->where('is_published', true)
            ->withCount(['reactions', 'comments'])
            ->latest()
            ->take(3)
            ->get();
        $blogsCount = $user->blogs()->where('is_published', true)->count();
        $totalBlogViews = (int) $user->blogs()->where('is_published', true)->sum('views');
        $totalBlogLikes = BlogReaction::whereIn('blog_id', $user->blogs()->where('is_published', true)->select('id'))->count();
        $sharedResourcesCount = Resource::where('user_id', $user->id)->count();

        // Recent Community Activities (Uploads, completed topics, comments made, reactions given)
        $recentUploads = Resource::where('user_id', $user->id)
            ->with(['node:id,name,subject_id', 'node.subject:id,name'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => [
                'type' => 'upload',
                'title' => $item->title,
                'subtitle' => $item->node?->subject?->name.' · '.$item->node?->name,
                'resource_type' => $item->resource_type,
                'url' => "/resources/{$item->id}",
                'created_at' => $item->created_at?->diffForHumans(),
            ]);

        $recentCompleted = ResourceCompletion::where('user_id', $user->id)
            ->with(['resource:id,title,node_id', 'resource.node:id,name,subject_id', 'resource.node.subject:id,name'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => [
                'type' => 'completion',
                'title' => $item->resource?->title,
                'subtitle' => $item->resource?->node?->subject?->name.' · '.$item->resource?->node?->name,
                'url' => $item->resource ? "/resources/{$item->resource->id}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
            ])
            ->filter(fn ($item) => $item['title'] !== null);

        $recentReactions = BlogReaction::where('user_id', $user->id)
            ->with('blog:id,title,slug')
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => [
                'type' => 'reaction',
                'title' => $item->blog?->title,
                'url' => $item->blog ? "/blogs/{$item->blog->slug}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
            ])
            ->filter(fn ($item) => $item['title'] !== null);

        $recentComments = BlogComment::where('user_id', $user->id)
            ->with('blog:id,title,slug')
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => [
                'type' => 'comment',
                'title' => $item->blog?->title,
                'content' => $item->content,
                'url' => $item->blog ? "/blogs/{$item->blog->slug}" : null,
                'created_at' => $item->created_at?->diffForHumans(),
            ])
            ->filter(fn ($item) => $item['title'] !== null);

        // Suggested / Discover community members: 2 contributors + 2 general users
        $contributorUsers = User::where('id', '!=', $user->id)
            ->whereNotNull('username')
            ->whereHas('roles')
            ->select(['id', 'name', 'username', 'title', 'institution', 'image_path', 'about'])
            ->with('roles:id,name')
            ->inRandomOrder()
            ->take(2)
            ->get();

        $excludedIds = $contributorUsers->pluck('id')->push($user->id)->all();
        $remainingNeeded = 4 - $contributorUsers->count();

        $randomUsers = User::whereNotIn('id', $excludedIds)
            ->whereNotNull('username')
            ->select(['id', 'name', 'username', 'title', 'institution', 'image_path', 'about'])
            ->with('roles:id,name')
            ->inRandomOrder()
            ->take($remainingNeeded)
            ->get();

        $suggestedUsers = $contributorUsers->concat($randomUsers)->shuffle()->values();

        return Inertia::render('User/Show', [
            'profileUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'title' => $user->title,
                'about' => $user->about,
                'institution' => $user->institution,
                'image_url' => $user->image_url,
                'facebook' => $user->facebook,
                'instagram' => $user->instagram,
                'github' => $user->github,
                'created_at' => $user->created_at?->format('M Y') ?? '2026',
                'roles' => $user->roles->pluck('name'),
                'is_staff' => $user->roles->isNotEmpty(),
            ],
            'stats' => [
                'completedResourcesCount' => $completedResourcesCount,
                'blogsCount' => $blogsCount,
                'totalBlogLikes' => (int) $totalBlogLikes,
                'totalBlogViews' => (int) $totalBlogViews,
                'sharedResourcesCount' => $sharedResourcesCount,
            ],
            'recentCompletions' => $recentCompletions,
            'blogs' => $publishedBlogs,
            'recentActivities' => [
                'uploads' => $recentUploads->values(),
                'completions' => $recentCompleted->values(),
                'reactions' => $recentReactions->values(),
                'comments' => $recentComments->values(),
            ],
            'suggestedUsers' => $suggestedUsers,
        ]);
    }
}
