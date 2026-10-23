<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ForumPost;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\UserAppreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $course = in_array($request->query('course'), ['hsc', 'ssc'], true) ? $request->query('course') : 'hsc';
        $group = in_array($request->query('group'), ['science', 'commerce', 'humanities'], true) ? $request->query('group') : null;
        $sort = in_array($request->query('sort'), ['trending', 'recent'], true) ? $request->query('sort') : 'trending';
        $page = max(1, (int) $request->query('page', 1));

        $feed = $this->getFeed($course, $group, $sort, $page);

        $subjectsQuery = Subject::where('is_disabled', false)
            ->where('course', $course);

        if ($group) {
            $subjectsQuery->whereIn('group', [$group, 'common']);
        }

        $subjects = $subjectsQuery->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'slug', 'course', 'group']);

        return Inertia::render('Feed', [
            'feed' => $feed,
            'subjects' => $subjects,
            'course' => $course,
            'group' => $group,
            'sort' => $sort,
            'notice' => Notice::activeForDisplay(),
        ]);
    }

    public function feed(Request $request)
    {
        $course = in_array($request->query('course'), ['hsc', 'ssc'], true) ? $request->query('course') : 'hsc';
        $group = in_array($request->query('group'), ['science', 'commerce', 'humanities'], true) ? $request->query('group') : null;
        $sort = in_array($request->query('sort'), ['trending', 'recent'], true) ? $request->query('sort') : 'trending';
        $page = max(1, (int) $request->query('page', 1));

        return response()->json($this->getFeed($course, $group, $sort, $page));
    }

    private function getFeed(string $course, ?string $group, string $sort, int $page = 1): array
    {
        $page = max(1, $page);
        $currentUserId = Auth::id();

        // 1. Forum Posts (6 items)
        $postsQuery = ForumPost::approved()
            ->where('curriculum', $course)
            ->when($group, function ($q) use ($group) {
                $q->where(function ($subQ) use ($group) {
                    $subQ->whereNull('subject_id')
                        ->orWhereHas('subject', fn ($s) => $s->whereIn('group', [$group, 'common']));
                });
            })
            ->when($currentUserId, fn ($q) => $q->where('user_id', '!=', $currentUserId))
            ->with(['user:id,name,username,image_path,institution,is_verified', 'subject:id,name,slug,group', 'node:id,name,slug'])
            ->withCount('answers');

        // 2. Resources (4 items)
        $resourcesQuery = Resource::approved()
            ->whereHas('node.subject', function ($s) use ($course, $group) {
                $s->where('course', $course);
                if ($group) {
                    $s->whereIn('group', [$group, 'common']);
                }
            })
            ->when($currentUserId, fn ($q) => $q->where('user_id', '!=', $currentUserId))
            ->with(['user:id,name,username,image_path,institution,is_verified', 'node:id,name,slug,subject_id', 'node.subject:id,name,course,slug,group']);

        // 3. Blogs (2 items)
        $blogsQuery = Blog::where('is_published', true)
            ->when($currentUserId, fn ($q) => $q->where('user_id', '!=', $currentUserId))
            ->with(['user:id,name,username,image_path,institution,is_verified'])
            ->withCount(['reactions', 'comments']);

        if ($currentUserId) {
            $blogsQuery->withExists([
                'reactions as is_reacted' => fn ($q) => $q->where('user_id', $currentUserId),
            ]);
        }

        if ($sort === 'recent') {
            $postsQuery->latest('id');
            $resourcesQuery->latest('id');
            $blogsQuery->latest('id');
        } else {
            // Trending: sort by vote_score / reactions/views DESC
            $postsQuery->orderByDesc('vote_score')->latest('id');
            $resourcesQuery->orderByDesc('vote_score')->latest('id');
            $blogsQuery->orderByDesc('views')->latest('id');
        }

        $posts = $postsQuery->forPage($page, 6)->get()->map(function ($p) {
            $p->feed_type = 'forum_post';

            return $p;
        });

        $resources = $resourcesQuery->forPage($page, 4)->get()->map(function ($r) {
            $r->feed_type = 'resource';

            return $r;
        });

        $blogs = $blogsQuery->forPage($page, 2)->get()->map(function ($b) {
            $b->feed_type = 'blog';

            return $b;
        });

        // Compute is_appreciated for authors if logged in
        $userId = Auth::id();
        if ($userId) {
            $authorIds = array_unique(array_filter(array_merge(
                $posts->pluck('user_id')->all(),
                $resources->pluck('user_id')->all(),
                $blogs->pluck('user_id')->all()
            )));

            $appreciatedAuthorIds = UserAppreciation::where('appreciator_id', $userId)
                ->whereIn('user_id', $authorIds)
                ->pluck('user_id')
                ->flip()
                ->all();

            foreach ($posts as $p) {
                if ($p->user) {
                    $p->user->is_appreciated = isset($appreciatedAuthorIds[$p->user->id]);
                }
            }
            foreach ($resources as $r) {
                if ($r->user) {
                    $r->user->is_appreciated = isset($appreciatedAuthorIds[$r->user->id]);
                }
            }
            foreach ($blogs as $b) {
                if ($b->user) {
                    $b->user->is_appreciated = isset($appreciatedAuthorIds[$b->user->id]);
                }
            }
        } else {
            foreach ($posts as $p) {
                if ($p->user) {
                    $p->user->is_appreciated = false;
                }
            }
            foreach ($resources as $r) {
                if ($r->user) {
                    $r->user->is_appreciated = false;
                }
            }
            foreach ($blogs as $b) {
                if ($b->user) {
                    $b->user->is_appreciated = false;
                }
            }
        }

        // Naturally interleave 6 posts, 4 resources, 2 blogs
        $pArr = $posts->values()->all();
        $rArr = $resources->values()->all();
        $bArr = $blogs->values()->all();

        $merged = [];
        $pIndex = 0;
        $rIndex = 0;
        $bIndex = 0;

        while ($pIndex < count($pArr) || $rIndex < count($rArr) || $bIndex < count($bArr)) {
            // Post
            if ($pIndex < count($pArr)) {
                $merged[] = $pArr[$pIndex++];
            }
            // Resource
            if ($rIndex < count($rArr)) {
                $merged[] = $rArr[$rIndex++];
            }
            // Blog (interleaved every other cycle)
            if ($bIndex < count($bArr) && count($merged) % 4 === 2) {
                $merged[] = $bArr[$bIndex++];
            }
            // Another Post to maintain the 6:4:2 ratio
            if ($pIndex < count($pArr)) {
                $merged[] = $pArr[$pIndex++];
            }
            // Remaining blog if any
            if ($bIndex < count($bArr) && count($merged) % 5 === 0) {
                $merged[] = $bArr[$bIndex++];
            }
        }

        // Catch any remaining blogs
        while ($bIndex < count($bArr)) {
            $merged[] = $bArr[$bIndex++];
        }

        return [
            'data' => $merged,
            'has_more' => ($posts->count() === 6 || $resources->count() === 4 || $blogs->count() === 2),
            'page' => $page,
        ];
    }
}
