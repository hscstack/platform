<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Notifications\BlogCommentNotification;
use App\Notifications\BlogReactionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $myBlogs = $request->boolean('mine');

        $blogs = Blog::query()
            ->select([
                'id',
                'user_id',
                'title',
                'slug',
                'excerpt',
                'featured_image_path',
                'is_published',
                'is_featured',
                'views',
                'created_at',
            ])
            ->with('user:id,name,username')
            ->withCount(['reactions', 'comments']);

        if (auth()->check()) {
            $user = auth()->user();
            if ($myBlogs) {
                // If filtering by "mine", show only the current user's blogs (published and drafts)
                $blogs->where('user_id', $user->id);
            } elseif ($user->can('manage blogs')) {
                // Authorities with manage blogs see all blogs
            } else {
                // Authors see published blogs plus their own drafts
                $blogs->where(function ($query) use ($user) {
                    $query->where('is_published', true)
                        ->orWhere('user_id', $user->id);
                });
            }
        } else {
            $blogs->where('is_published', true);
        }

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
            'filters' => [
                'q' => $request->input('q', ''),
                'mine' => $myBlogs,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Blog/CreateOrEdit');
    }

    public function store(StoreBlogRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('blogs');
            $data['featured_image_path'] = $path;
        }

        $blog = Blog::create($data);

        return redirect()->route('blogs.show', $blog)->with('success', 'Blog created successfully.');
    }

    public function edit(Blog $blog)
    {
        return Inertia::render('Blog/CreateOrEdit', [
            'blog' => $blog,
        ]);
    }

    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image_path) {
                Storage::delete($blog->featured_image_path);
            }

            $path = $request->file('featured_image')->store('blogs');
            $data['featured_image_path'] = $path;
        }

        $blog->update($data);

        return redirect()
            ->route('blogs.show', $blog)
            ->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->featured_image_path) {
            Storage::delete($blog->featured_image_path);
        }

        $blog->delete();

        return redirect()
            ->route('blogs.index')
            ->with('success', 'Blog deleted successfully.');
    }

    public function show(Blog $blog)
    {
        $blog->load('user:id,name,username,image_path,is_verified');
        $blog->increment('views');

        $reactionsCount = $blog->reactions()->count();

        $reactors = $blog->reactions()
            ->with(['user:id,name,username,image_path,institution,is_verified'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        $comments = $blog->comments()
            ->with(['user:id,name,username,image_path,institution,is_verified'])
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
        $user = auth()->user();

        // Check if user is suspended
        if ($user && $user->isBanned()) {
            return back()->with('error', 'You are temporarily suspended from community participation.');
        }

        $userId = $user->id;

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
