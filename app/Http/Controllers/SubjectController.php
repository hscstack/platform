<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ForumPost;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request, $course)
    {
        // If visiting root '/' and user explicitly preferred 'ssc', redirect cleanly on server side
        if ($request->path() === '/' && $request->cookie('preferred_course') === 'ssc') {
            return redirect('/ssc');
        }
        $subjects = Cache::rememberForever("home_page_subjects_{$course}", function () use ($course) {
            return Subject::orderBy('sort_order', 'asc')
                ->where('course', $course)
                ->withCount([
                    'nodes' => function ($query) {
                        $query->whereNull('parent_id');
                    },
                ])
                ->get()
                ->toArray();
        });

        $featuredBlogs = Cache::remember('home_page_featured_blogs', now()->addDay(), function () {
            return Blog::where('is_featured', true)
                ->where('is_published', true)
                ->with('user:id,name,username')
                ->withCount(['reactions', 'comments'])
                ->inRandomOrder()
                ->limit(3)
                ->get()
                ->toArray();
        });

        $trendingPosts = Cache::remember('home_page_trending_posts', now()->addHours(2), function () {
            return ForumPost::query()
                ->approved()
                ->with([
                    'user:id,name,username,image_path,institution,is_verified',
                    'subject:id,name,course,slug',
                    'node:id,name,slug',
                ])
                ->orderByDesc('vote_score')
                ->latest()
                ->limit(4)
                ->get()
                ->toArray();
        });

        $notice = Cache::rememberForever('home_page_notice', function () {
            return Notice::activeForDisplay()?->toArray();
        });

        return Inertia::render('Home', [
            'subjects' => $subjects,
            'featured_blogs' => $featuredBlogs,
            'trending_posts' => $trendingPosts,
            'notice' => $notice,
        ]);
    }

    public function show(Subject $subject)
    {
        $nodes = Cache::rememberForever("subject_page_{$subject->id}", function () use ($subject) {
            return Node::where('subject_id', $subject->id)
                ->whereNull('parent_id')
                ->withCount(['children', 'resources', 'upvotes', 'downvotes'])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug'])->toArray();
        });

        return Inertia::render('Node', [
            'subject' => $subject,
            'currentNode' => null,
            'nodes' => $nodes,
            'breadcrumb' => [],
            'resources' => [],
            'upvotesCount' => 0,
            'downvotesCount' => 0,
            'userVote' => null,
            'upvoters' => [],
        ]);
    }
}
