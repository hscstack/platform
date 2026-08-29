<?php

namespace App\Http\Controllers;

use App\Mail\BlogNotificationMail;
use App\Models\Blog;
use App\Models\BlogComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,image_path,institution',
                'user.roles:id,name',
                'replies' => function ($query) {
                    $query->with([
                        'user:id,name,username,image_path,institution',
                        'user.roles:id,name',
                    ])->oldest();
                },
            ])
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

            $reactionsCount = $blog->reactions()->count();
            $milestones = [1, 10, 25, 50, 100, 250, 500, 1000];
            $isMilestone = in_array($reactionsCount, $milestones, true) || ($reactionsCount > 1000 && $reactionsCount % 500 === 0);

            if ($isMilestone) {
                $blog->loadMissing('user:id,name,email,receive_emails');

                if ($blog->user && $blog->user_id !== $user->id && $blog->user->email && $blog->user->receive_emails !== false) {
                    Mail::to($blog->user->email)->queue(BlogNotificationMail::forReactionMilestone($blog, $user, $reactionsCount));
                }
            }
        }

        return back();
    }

    public function storeComment(Request $request, Blog $blog)
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:blog_comments,id'],
        ]);

        $parentId = null;
        $parentComment = null;

        if (! empty($validated['parent_id'])) {
            $parentComment = BlogComment::where('id', $validated['parent_id'])
                ->where('blog_id', $blog->id)
                ->firstOrFail();

            // If replying to a reply, flatten to the top-level parent comment
            $parentId = $parentComment->parent_id ?: $parentComment->id;
        }

        $comment = $blog->comments()->create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => trim($validated['content']),
        ]);

        $comment->load(['user:id,name,username,image_path,institution', 'user.roles:id,name']);
        $blog->loadMissing('user:id,name,email,receive_emails');

        if ($parentId && $parentComment) {
            // Notification for reply: notify the parent comment owner
            $parentComment->loadMissing('user:id,name,email,receive_emails');
            if (
                $parentComment->user
                && $parentComment->user_id !== $userId
                && $parentComment->user->email
                && $parentComment->user->receive_emails !== false
            ) {
                Mail::to($parentComment->user->email)->queue(BlogNotificationMail::forReply($blog, $comment, $parentComment));
            }

            // Also notify blog author if the blog author is not the replier and not the parent commenter
            if (
                $blog->user
                && $blog->user_id !== $userId
                && $blog->user_id !== $parentComment->user_id
                && $blog->user->email
                && $blog->user->receive_emails !== false
            ) {
                Mail::to($blog->user->email)->queue(BlogNotificationMail::forComment($blog, $comment));
            }
        } else {
            // Top-level comment: notify blog author
            if ($blog->user && $blog->user_id !== $userId && $blog->user->email && $blog->user->receive_emails !== false) {
                Mail::to($blog->user->email)->queue(BlogNotificationMail::forComment($blog, $comment));
            }
        }

        return back()->with('success', $parentId ? 'Reply posted successfully.' : 'Comment posted successfully.');
    }

    public function destroyComment(BlogComment $comment)
    {
        $comment->loadMissing('blog');
        $isBlogAuthor = $comment->blog && auth()->id() === $comment->blog->user_id;

        abort_unless(auth()->id() === $comment->user_id || $isBlogAuthor || auth()->user()?->can('view admin'), 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}
