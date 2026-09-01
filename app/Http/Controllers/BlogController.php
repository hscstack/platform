<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Notifications\BlogCommentNotification;
use App\Notifications\BlogReactionNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $blogs = Blog::query()
            ->select([
                'id',
                'user_id',
                'title',
                'slug',
                'excerpt',
                'featured_image_path',
                'is_featured',
                'views',
                'created_at',
            ])
            ->with('user:id,name,username')
            ->withCount(['reactions', 'comments'])
            ->where('is_published', true);

        if ($request->filled('q')) {
            $search = $request->q;

            $blogs->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('seo_tags', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $blogs = $blogs
            ->latest()
            ->paginate(6)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'blogs' => $blogs,
        ]);
    }

    public function show(Blog $blog)
    {
        abort_unless($blog->is_published, 404);

        $blog->load('user:id,name,username,image_path');
        $blog->increment('views');

        $reactionsCount = $blog->reactions()->count();

        $reactors = $blog->reactions()
            ->with(['user:id,name,username,image_path,institution'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        $comments = $blog->comments()
            ->with(['user:id,name,username,image_path,institution'])
            ->latest()
            ->get();

        $isReacted = auth()->check()
            ? $blog->reactions()->where('user_id', auth()->id())->exists()
            : false;

        return Inertia::render('Blog/Show', [
            'blog' => $blog,
            'reactionsCount' => $reactionsCount,
            'isReacted' => $isReacted,
            'reactors' => $reactors,
            'comments' => $comments,
        ]);
    }

    public function toggleReaction(Blog $blog)
    {
        $user = auth()->user();
        $existing = $blog->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $blog->reactions()->create(['user_id' => $user->id]);

            if ($blog->user_id !== $user->id) {
                $blog->user->notify(new BlogReactionNotification($blog, $user, $blog->reactions()->count()));
            }
        }

        return back();
    }

    public function storeComment(Request $request, Blog $blog)
    {
        $userId = auth()->id();

        if ($blog->comments()->where('user_id', $userId)->exists()) {
            return back()->with('error', 'You have already posted a comment on this blog.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $comment = $blog->comments()->create([
            'user_id' => $userId,
            'content' => trim($validated['content']),
        ]);

        if ($blog->user_id !== $userId) {
            $blog->user->notify(new BlogCommentNotification($blog, $comment));
        }

        return back()->with('success', 'Comment posted successfully.');
    }

    public function destroyComment(BlogComment $comment)
    {
        abort_unless(auth()->id() === $comment->user_id || auth()->user()?->can('view admin'), 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}
