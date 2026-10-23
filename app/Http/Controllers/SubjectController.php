<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Resource;
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

        $group = $request->query('group');
        if (! in_array($group, ['science', 'humanities', 'commerce'], true)) {
            $group = 'science';
        }

        $subjects = Cache::rememberForever("home_page_subjects_{$course}_{$group}", function () use ($course, $group) {
            return Subject::where('is_disabled', false)
                ->orderBy('sort_order', 'asc')
                ->where('course', $course)
                ->whereIn('group', [$group, 'common'])
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

        $trendingPosts = Cache::remember('home_page_trending_posts', now()->addSeconds(30), function () {
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

        if ($userId = auth()->id()) {
            $postIds = array_column($trendingPosts, 'id');
            if (! empty($postIds)) {
                $userVotes = ForumVote::where('user_id', $userId)
                    ->where('voteable_type', ForumPost::class)
                    ->whereIn('voteable_id', $postIds)
                    ->pluck('value', 'voteable_id')
                    ->all();

                foreach ($trendingPosts as &$post) {
                    $post['user_vote'] = $userVotes[$post['id']] ?? null;
                }
                unset($post);
            }
        }

        $trendingNotes = Cache::remember("home_page_trending_notes_{$course}", now()->addMinutes(2), function () use ($course) {
            return Resource::query()
                ->where('status', 'approved')
                ->whereNotNull('items')
                ->whereJsonLength('items', '>', 0)
                ->whereHas('node.subject', function ($query) use ($course) {
                    $query->where('course', $course);
                })
                ->with([
                    'user:id,name,username,image_path,institution,is_verified',
                    'node:id,name,slug,subject_id',
                    'node.subject:id,name,course,slug',
                ])
                ->orderByDesc('vote_score')
                ->latest('id')
                ->limit(4)
                ->get()
                ->toArray();
        });

        $notice = Cache::rememberForever('home_page_notice', function () {
            return Notice::activeForDisplay()?->toArray();
        });

        return Inertia::render('Home', [
            'subjects' => $subjects,
            'current_group' => $group,
            'featured_blogs' => $featuredBlogs,
            'trending_posts' => $trendingPosts,
            'trending_notes' => $trendingNotes,
            'notice' => $notice,
        ]);
    }

    public function show(Subject $subject)
    {
        if ($subject->is_disabled) {
            abort(404);
        }

        $nodes = Cache::rememberForever("subject_page_{$subject->id}", function () use ($subject) {
            return Node::where('subject_id', $subject->id)
                ->whereNull('parent_id')
                ->withCount(['children', 'resources' => function ($q) {
                    $q->where('status', 'approved');
                }])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug'])->toArray();
        });

        return Inertia::render('Node', [
            'subject' => $subject,
            'currentNode' => null,
            'nodes' => $nodes,
            'breadcrumb' => [],
            'resources' => [],
        ]);
    }
}
