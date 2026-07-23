<?php

namespace App\Http\Controllers;

use App\Models\Blog;
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
                'featured_image',
                'is_featured',
                'views',
                'created_at',
            ])
            ->with('user:id,name')
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

        $blog->load('user:id,name');

        $blog->increment('views');

        return Inertia::render('Blog/Show', [
            'blog' => $blog,
        ]);
    }
}
