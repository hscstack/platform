<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index($course)
    {
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

        $featuredBlogs = Blog::where('is_featured', true)
            ->where('is_published', true)
            ->with('user:id,name,username')
            ->withCount(['reactions', 'comments'])
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->toArray();

        $notice = Notice::activeForDisplay()?->toArray();

        return Inertia::render('Home', [
            'subjects' => $subjects,
            'featured_blogs' => $featuredBlogs,
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
