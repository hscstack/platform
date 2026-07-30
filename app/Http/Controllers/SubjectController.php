<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index()
    {
        $homeData = Cache::rememberForever("home_page_data", function () {
            $subjects = Subject::orderBy('sort_order', 'asc')
                ->withCount([
                    'nodes' => function ($query) {
                        $query->whereNull('parent_id');
                    }
                ])
                ->get();


            return  [
                'subjects' => $subjects->toArray(),
                'featured_blogs' => Blog::where('is_featured', true)
                    ->where('is_published', true)
                    ->with('user:id,name')
                    ->latest()
                    ->limit(3)
                    ->get()
                    ->toArray(),
                'subjectCount' => $subjects->count(),
                'resourceCount' => Resource::count(),
                'contributorCount' => User::count(),
                'notice' => Notice::activeForDisplay()?->toArray(),
            ];
        });

        return Inertia::render('Home', $homeData);
    }

    public function show(Subject $subject)
    {
        $nodes = Cache::rememberForever("subject_page_{$subject->id}", function () use ($subject) {
            return Node::where('subject_id', $subject->id)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->withCount(['children', 'resources'])
                ->get(['id', 'name', 'slug'])->toArray();
        });

        return Inertia::render('Node', [
            'subject' => $subject,
            'nodes' => $nodes,
            'breadcrumb' => [],
            'resources' => [],
        ]);
    }
}
