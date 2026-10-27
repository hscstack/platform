<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Blog;
use App\Models\BlogReaction;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\Resource;
use App\Models\ResourceVote;
use App\Models\Subject;
use App\Models\User;
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

        // Remove guest-level whole-feed caching to ensure real-time vote scores and reactions across all users
        $feed = $this->getFeed($course, $group, $sort, $page);

        $subjects = Cache::rememberForever("curriculum_subjects_{$course}_{$group}", function () use ($course, $group) {
            $subjectsQuery = Subject::where('is_disabled', false)
                ->where('course', $course);

            if ($group) {
                $subjectsQuery->whereIn('group', [$group, 'common']);
            }

            return $subjectsQuery->orderBy('sort_order', 'asc')
                ->with(['chapters' => function ($q) {
                    $q->select('id', 'subject_id', 'name')
                        ->orderBy('sort_order');
                }])
                ->get(['id', 'name', 'course', 'group'])
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

        // Cache the raw interleaved items as pure arrays for 2 minutes
        $cacheKey = "home_feed_raw_arrays_{$course}_{$group}_{$sort}_{$page}";
        $cachedFeed = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($course, $group, $sort, $page) {
            // 1. Forum Posts (7 items)
            $postsQuery = ForumPost::approved()
                ->where('curriculum', $course)
                ->when($group, function ($q) use ($group) {
                    $q->where(function ($subQ) use ($group) {
                        $subQ->whereNull('subject_id')
                            ->orWhereHas('subject', fn ($s) => $s->whereIn('group', [$group, 'common']));
                    });
                })
                ->with(['user:id,name,username,image_path,institution,is_verified', 'subject:id,name,group', 'chapter:id,name'])
                ->withCount('answers');

            // 2. Resources (4 items)
            $resourcesQuery = Resource::approved()
                ->where(function ($query) use ($course, $group) {
                    $query->whereHas('subject', function ($s) use ($course, $group) {
                        $s->where('course', $course);
                        if ($group) {
                            $s->whereIn('group', [$group, 'common']);
                        }
                    })->orWhereHas('chapter.subject', function ($s) use ($course, $group) {
                        $s->where('course', $course);
                        if ($group) {
                            $s->whereIn('group', [$group, 'common']);
                        }
                    });
                })
                ->with(['user:id,name,username,image_path,institution,is_verified', 'chapter:id,name,subject_id', 'chapter.subject:id,name,course,group', 'subject:id,name,course,group'])
                ->withCount('comments');

            // 3. Blogs (1 item)
            $blogsQuery = Blog::where('is_published', true)
                ->with(['user:id,name,username,image_path,institution,is_verified'])
                ->withCount(['reactions', 'comments']);

            if ($sort === 'recent') {
                $postsQuery->latest('id');
                $resourcesQuery->latest('id');
                $blogsQuery->latest('id');
            } else {
                // Trending with time decay formula: ((score + 1) / (hours_old + 2))
                $isSqlite = DB::getDriverName() === 'sqlite';
                if ($isSqlite) {
                    $postsQuery->orderByRaw('((vote_score + (answers_count * 2) + 1) / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
                    $resourcesQuery->orderByRaw('((vote_score + 1) / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
                    $blogsQuery->orderByRaw('((reactions_count * 2 + comments_count + 1) / (((julianday("now") - julianday(created_at)) * 24) + 2)) DESC')->latest('id');
                } else {
                    $postsQuery->orderByRaw('((vote_score + (answers_count * 2) + 1) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
                    $resourcesQuery->orderByRaw('((vote_score + 1) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
                    $blogsQuery->orderByRaw('((reactions_count * 2 + comments_count + 1) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2)) DESC')->latest('id');
                }
            }

            $posts = $postsQuery->forPage($page, 7)->get()->map(function ($p) {
                $p->feed_type = 'forum_post';

                return $p;
            });

            $resources = $resourcesQuery->forPage($page, 4)->get()->map(function ($r) {
                $r->feed_type = 'resource';

                return $r;
            });

            $blogs = $blogsQuery->forPage($page, 1)->get()->map(function ($b) {
                $b->feed_type = 'blog';

                return $b;
            });

            // Mask anonymous posts before caching
            foreach ($posts as $p) {
                if ($p->is_anonymous) {
                    $p->user_id = null;
                    $p->setRelation('user', (object) [
                        'id' => null,
                        'name' => 'Anonymous Student',
                        'username' => null,
                        'image_path' => null,
                        'image_url' => null,
                        'institution' => null,
                        'is_verified' => false,
                        'is_appreciated' => false,
                    ]);
                }
            }

            $pArr = $posts->toArray();
            $rArr = $resources->toArray();
            $bArr = $blogs->toArray();

            $merged = [];
            $pIndex = 0;
            $rIndex = 0;
            $bIndex = 0;

            while ($pIndex < count($pArr) || $rIndex < count($rArr)) {
                if ($pIndex < count($pArr)) {
                    $merged[] = $pArr[$pIndex++];
                }
                if ($rIndex < count($rArr)) {
                    $merged[] = $rArr[$rIndex++];
                }
                if ($bIndex < count($bArr) && count($merged) % 4 === 2) {
                    $merged[] = $bArr[$bIndex++];
                }
                if ($pIndex < count($pArr)) {
                    $merged[] = $pArr[$pIndex++];
                }
                if ($bIndex < count($bArr) && count($merged) % 5 === 0) {
                    $merged[] = $bArr[$bIndex++];
                }
            }

            while ($bIndex < count($bArr)) {
                $merged[] = $bArr[$bIndex++];
            }

            return [
                'items' => $merged,
                'has_more' => (count($pArr) === 7 || count($rArr) === 4 || count($bArr) === 1),
            ];
        });

        $items = $cachedFeed['items'];
        $userId = Auth::id();

        $authorIds = [];
        $postIds = [];
        $resourceIds = [];
        $blogIds = [];

        foreach ($items as $item) {
            if (! empty($item['user_id'])) {
                $authorIds[] = $item['user_id'];
            }
            if (($item['feed_type'] ?? '') === 'forum_post' && ! empty($item['id'])) {
                $postIds[] = $item['id'];
            } elseif (($item['feed_type'] ?? '') === 'resource' && ! empty($item['id'])) {
                $resourceIds[] = $item['id'];
            } elseif (($item['feed_type'] ?? '') === 'blog' && ! empty($item['id'])) {
                $blogIds[] = $item['id'];
            }
        }

        // Fetch fresh real-time vote scores, upvotes/downvotes counts, and reaction counts
        $freshPosts = ! empty($postIds)
            ? ForumPost::whereIn('id', $postIds)
                ->get(['id', 'vote_score', 'upvotes_count', 'downvotes_count'])
                ->keyBy('id')
            : [];

        $freshResources = ! empty($resourceIds)
            ? Resource::whereIn('id', $resourceIds)
                ->get(['id', 'vote_score', 'upvotes_count', 'downvotes_count'])
                ->keyBy('id')
            : [];

        $freshBlogReactions = ! empty($blogIds)
            ? BlogReaction::whereIn('blog_id', $blogIds)
                ->selectRaw('blog_id, count(*) as count')
                ->groupBy('blog_id')
                ->pluck('count', 'blog_id')
                ->all()
            : [];

        if ($userId) {
            $appreciatedAuthorIds = ! empty($authorIds)
                ? UserAppreciation::where('appreciator_id', $userId)
                    ->whereIn('user_id', array_unique($authorIds))
                    ->pluck('user_id')
                    ->flip()
                    ->all()
                : [];

            $forumPostVotes = ! empty($postIds)
                ? ForumVote::where('user_id', $userId)
                    ->where('voteable_type', ForumPost::class)
                    ->whereIn('voteable_id', $postIds)
                    ->pluck('value', 'voteable_id')
                    ->all()
                : [];

            $resourceVotes = ! empty($resourceIds)
                ? ResourceVote::where('user_id', $userId)
                    ->whereIn('resource_id', $resourceIds)
                    ->pluck('value', 'resource_id')
                    ->all()
                : [];

            $reactedBlogIds = ! empty($blogIds)
                ? BlogReaction::where('user_id', $userId)
                    ->whereIn('blog_id', $blogIds)
                    ->pluck('blog_id')
                    ->flip()
                    ->all()
                : [];

            foreach ($items as &$item) {
                if (! empty($item['user']) && is_array($item['user']) && ! empty($item['user']['id'])) {
                    $item['user']['is_appreciated'] = isset($appreciatedAuthorIds[$item['user']['id']]);
                }

                if (($item['feed_type'] ?? '') === 'forum_post') {
                    if (isset($freshPosts[$item['id']])) {
                        $item['vote_score'] = $freshPosts[$item['id']]->vote_score;
                        $item['upvotes_count'] = $freshPosts[$item['id']]->upvotes_count;
                        $item['downvotes_count'] = $freshPosts[$item['id']]->downvotes_count;
                    }
                    $item['user_vote'] = $forumPostVotes[$item['id']] ?? null;
                } elseif (($item['feed_type'] ?? '') === 'resource') {
                    if (isset($freshResources[$item['id']])) {
                        $item['vote_score'] = $freshResources[$item['id']]->vote_score;
                        $item['upvotes_count'] = $freshResources[$item['id']]->upvotes_count;
                        $item['downvotes_count'] = $freshResources[$item['id']]->downvotes_count;
                    }
                    $item['user_vote'] = $resourceVotes[$item['id']] ?? null;
                } elseif (($item['feed_type'] ?? '') === 'blog') {
                    $item['reactions_count'] = $freshBlogReactions[$item['id']] ?? 0;
                    $item['is_reacted'] = isset($reactedBlogIds[$item['id']]);
                }
            }
            unset($item);
        } else {
            foreach ($items as &$item) {
                if (! empty($item['user']) && is_array($item['user'])) {
                    $item['user']['is_appreciated'] = false;
                }

                if (($item['feed_type'] ?? '') === 'forum_post') {
                    if (isset($freshPosts[$item['id']])) {
                        $item['vote_score'] = $freshPosts[$item['id']]->vote_score;
                        $item['upvotes_count'] = $freshPosts[$item['id']]->upvotes_count;
                        $item['downvotes_count'] = $freshPosts[$item['id']]->downvotes_count;
                    }
                    $item['user_vote'] = null;
                } elseif (($item['feed_type'] ?? '') === 'resource') {
                    if (isset($freshResources[$item['id']])) {
                        $item['vote_score'] = $freshResources[$item['id']]->vote_score;
                        $item['upvotes_count'] = $freshResources[$item['id']]->upvotes_count;
                        $item['downvotes_count'] = $freshResources[$item['id']]->downvotes_count;
                    }
                    $item['user_vote'] = null;
                } elseif (($item['feed_type'] ?? '') === 'blog') {
                    $item['reactions_count'] = $freshBlogReactions[$item['id']] ?? 0;
                    $item['is_reacted'] = false;
                }
            }
            unset($item);
        }

        return [
            'data' => $items,
            'has_more' => $cachedFeed['has_more'],
            'page' => $page,
        ];
    }

    public function aboutUs()
    {
        $users = Cache::remember('about_us_info', now()->addHours(24), function () {
            return User::where('is_verified', true)
                ->select(['id', 'name', 'username', 'title', 'about', 'institution', 'image_path', 'priority', 'is_board_member'])
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'title' => $user->title,
                    'about' => $user->about,
                    'institution' => $user->institution,
                    'image_url' => $user->image_url,
                    'priority' => (int) $user->priority,
                    'is_board_member' => (bool) $user->is_board_member,
                ])
                ->all();
        });

        return Inertia::render('platform/AboutUs', [
            'users' => $users,
        ]);
    }
}
