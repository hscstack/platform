<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Blog;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Resource;
use App\Models\ResourceVote;
use App\Models\Subject;
use App\Models\UserAppreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $course = in_array($request->query('course'), ['hsc', 'ssc'], true) ? $request->query('course') : 'hsc';
        $group = in_array($request->query('group'), ['science', 'commerce', 'humanities'], true) ? $request->query('group') : 'science';
        $sort = in_array($request->query('sort'), ['trending', 'recent'], true) ? $request->query('sort') : 'trending';
        $page = max(1, (int) $request->query('page', 1));

        $feed = $this->getFeed($course, $group, $sort, $page);

        $subjects = Cache::rememberForever("curriculum_subjects_{$course}_{$group}", function () use ($course, $group) {
            $subjectsQuery = Subject::where('is_disabled', false)
                ->where('course', $course);

            if ($group) {
                $subjectsQuery->whereIn('group', [$group, 'common']);
            }

            return $subjectsQuery->orderBy('sort_order', 'asc')
                ->with(['nodes' => function ($q) {
                    $q->whereNull('parent_id')
                        ->select('id', 'subject_id', 'parent_id', 'name', 'slug')
                        ->orderBy('sort_order');
                }])
                ->get(['id', 'name', 'slug', 'course', 'group'])
                ->toArray();
        });

        $notice = Cache::rememberForever('active_notice_for_display', function () {
            $raw = AppSetting::get('site_notice');
            if (! $raw) {
                return null;
            }
            $data = is_array($raw) ? $raw : json_decode($raw, true);
            if (empty($data['is_active'])) {
                return null;
            }
            if (! empty($data['image'])) {
                $data['image'] = str($data['image'])->startsWith(['http://', 'https://'])
                    ? $data['image']
                    : Storage::url($data['image']);
            }

            return $data;
        });

        $allowUploads = (bool) AppSetting::get('allow_resource_uploads', true);

        return Inertia::render('Feed', [
            'feed' => $feed,
            'subjects' => $subjects,
            'course' => $course,
            'group' => $group,
            'sort' => $sort,
            'notice' => $notice,
            'allow_resource_uploads' => $allowUploads,
        ]);
    }

    public function feed(Request $request)
    {
        $course = in_array($request->query('course'), ['hsc', 'ssc'], true) ? $request->query('course') : 'hsc';
        $group = in_array($request->query('group'), ['science', 'commerce', 'humanities'], true) ? $request->query('group') : 'science';
        $sort = in_array($request->query('sort'), ['trending', 'recent'], true) ? $request->query('sort') : 'trending';
        $page = max(1, (int) $request->query('page', 1));

        return response()->json($this->getFeed($course, $group, $sort, $page));
    }

    private function getFeed(string $course, string $group, string $sort, int $page = 1): array
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
            // Trending with time decay formula: (score / (hours_old + 2))
            // Divides raw upvote/reaction points by elapsed hours (+2 smoothing buffer)
            // so fresh content with good engagement surfaces ahead of older static posts.
            $isSqlite = DB::getDriverName() === 'sqlite';
            if ($isSqlite) {
                $postsQuery->orderByRaw('(vote_score / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
                $resourcesQuery->orderByRaw('(vote_score / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
                $blogsQuery->orderByRaw('((reactions_count * 2 + comments_count) / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
            } else {
                $postsQuery->orderByRaw('(vote_score / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
                $resourcesQuery->orderByRaw('(vote_score / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
                $blogsQuery->orderByRaw('((reactions_count * 2 + comments_count) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
            }
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

        // Compute is_appreciated and user_vote if logged in
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

            $forumPostVotes = ForumVote::where('user_id', $userId)
                ->where('voteable_type', ForumPost::class)
                ->whereIn('voteable_id', $posts->pluck('id'))
                ->pluck('value', 'voteable_id')
                ->all();

            $resourceVotes = ResourceVote::where('user_id', $userId)
                ->whereIn('resource_id', $resources->pluck('id'))
                ->pluck('value', 'resource_id')
                ->all();

            foreach ($posts as $p) {
                if ($p->user) {
                    $p->user->is_appreciated = isset($appreciatedAuthorIds[$p->user->id]);
                }
                $p->user_vote = $forumPostVotes[$p->id] ?? null;
            }
            foreach ($resources as $r) {
                if ($r->user) {
                    $r->user->is_appreciated = isset($appreciatedAuthorIds[$r->user->id]);
                }
                $r->user_vote = $resourceVotes[$r->id] ?? null;
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
                $p->user_vote = null;
            }
            foreach ($resources as $r) {
                if ($r->user) {
                    $r->user->is_appreciated = false;
                }
                $r->user_vote = null;
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

        while ($pIndex < count($pArr) || $rIndex < count($rArr)) {
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
