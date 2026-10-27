<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAppreciation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = in_array($request->input('tab'), ['students', 'contributors'], true)
            ? $request->input('tab')
            : 'students';

        $timeframe = in_array($request->input('timeframe'), ['this_week', 'this_month', 'all_time'], true)
            ? $request->input('timeframe')
            : 'this_month';

        $curriculum = in_array($request->input('curriculum'), ['hsc', 'ssc'], true)
            ? $request->input('curriculum')
            : null;

        $group = in_array($request->input('group'), ['science', 'humanities', 'commerce'], true)
            ? $request->input('group')
            : null;

        $batch = $request->input('batch');
        if (! in_array($batch, ['hsc_25', 'hsc_26', 'hsc_27', 'hsc_28', 'ssc_25', 'ssc_26', 'ssc_27', 'ssc_28'], true)) {
            $batch = null;
        }

        $appreciationScope = in_array($request->input('scope'), ['appreciating', 'appreciators', 'mutual'], true)
            ? $request->input('scope')
            : null;

        $user = $request->user();

        // If user selects appreciation scope but is not logged in, ignore it
        if (! $user && $appreciationScope) {
            $appreciationScope = null;
        }

        $dateFilter = match ($timeframe) {
            'this_week' => Carbon::now()->startOfWeek(Carbon::SATURDAY)->toDateString(),
            'this_month' => Carbon::now()->startOfMonth()->toDateString(),
            'all_time' => null,
        };

        // If filtering by user's personal appreciation network, resolve user IDs
        $scopedUserIds = null;
        if ($user && $appreciationScope) {
            $scopedUserIds = match ($appreciationScope) {
                'appreciating' => UserAppreciation::where('appreciator_id', $user->id)->pluck('user_id')->push($user->id)->unique()->toArray(),
                'appreciators' => UserAppreciation::where('user_id', $user->id)->pluck('appreciator_id')->push($user->id)->unique()->toArray(),
                'mutual' => UserAppreciation::where('appreciator_id', $user->id)
                    ->whereIn('user_id', function ($query) use ($user) {
                        $query->select('appreciator_id')
                            ->from('user_appreciations')
                            ->where('user_id', $user->id);
                    })
                    ->pluck('user_id')
                    ->push($user->id)
                    ->unique()
                    ->toArray(),
            };
        }

        // Cache public leaderboard queries (un-scoped by personal appreciation)
        if (! $appreciationScope) {
            $cacheKey = "leaderboard:{$tab}:{$timeframe}:".($curriculum ?: 'all').':'.($group ?: 'all').':'.($batch ?: 'all');
            $leaderboard = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tab, $dateFilter, $curriculum, $group, $batch) {
                return $tab === 'contributors'
                    ? $this->queryContributors($dateFilter)
                    : $this->queryStudents($dateFilter, $curriculum, $group, $batch);
            });
        } else {
            // Personal social network query (dynamic)
            $leaderboard = $tab === 'contributors'
                ? $this->queryContributors($dateFilter, $scopedUserIds)
                : $this->queryStudents($dateFilter, $curriculum, $group, $batch, $scopedUserIds);
        }

        $currentUserRank = $this->resolveUserRank($user, $leaderboard, $tab, $dateFilter, $curriculum, $group, $batch);

        return Inertia::render('Leaderboard/Index', [
            'tab' => $tab,
            'timeframe' => $timeframe,
            'curriculum' => $curriculum,
            'group' => $group,
            'batch' => $batch,
            'scope' => $appreciationScope,
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank,
        ]);
    }

    private function queryContributors(?string $dateFilter, ?array $scopedUserIds = null): array
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'image_path', 'institution', 'is_verified', 'title'])
            ->where(fn ($q) => $q->whereNull('banned_until')->orWhere('banned_until', '<=', now()))
            ->when($scopedUserIds !== null, fn ($q) => $q->whereIn('id', $scopedUserIds))
            ->whereHas('resources', fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter)))
            ->withCount(['resources' => fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter))])
            ->orderByDesc('resources_count')
            ->latest('id')
            ->take(50)
            ->get();

        return $this->formatLeaderboard($users, 'resources_count');
    }

    private function queryStudents(
        ?string $dateFilter,
        ?string $curriculum = null,
        ?string $group = null,
        ?string $batch = null,
        ?array $scopedUserIds = null
    ): array {
        $users = User::query()
            ->select(['id', 'name', 'username', 'image_path', 'institution', 'is_verified', 'title', 'curriculum', 'group', 'batch'])
            ->where(fn ($q) => $q->whereNull('banned_until')->orWhere('banned_until', '<=', now()))
            ->where(fn ($q) => $q->whereNull('activity_privacy')->orWhere('activity_privacy', '!=', 'private'))
            ->when($curriculum, fn ($q) => $q->where('curriculum', $curriculum))
            ->when($group, fn ($q) => $q->where('group', $group))
            ->when($batch, fn ($q) => $q->where('batch', $batch))
            ->when($scopedUserIds !== null, fn ($q) => $q->whereIn('id', $scopedUserIds))
            ->whereHas('dailyStudyLogs', fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter))->where('total_seconds', '>', 0))
            ->withSum(['dailyStudyLogs as total_study_seconds' => fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter))], 'total_seconds')
            ->orderByDesc('total_study_seconds')
            ->latest('id')
            ->take(50)
            ->get();

        return $this->formatLeaderboard($users, 'total_study_seconds');
    }

    private function formatLeaderboard($users, string $scoreKey): array
    {
        $leaderboard = [];
        $currentRank = 1;
        $prevScore = null;

        foreach ($users as $index => $u) {
            $score = (int) $u->{$scoreKey};
            if ($prevScore !== null && $score === $prevScore) {
                $rank = $currentRank;
            } else {
                $currentRank = $index + 1;
                $rank = $currentRank;
                $prevScore = $score;
            }

            $leaderboard[] = [
                'rank' => $rank,
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'image_url' => $u->image_url,
                'institution' => $u->institution,
                'title' => $u->title,
                'curriculum' => $u->curriculum ?? null,
                'group' => $u->group ?? null,
                'batch' => $u->batch ?? null,
                'is_verified' => (bool) $u->is_verified,
                'score' => $score,
            ];
        }

        return $leaderboard;
    }

    private function resolveUserRank(
        ?User $currentUser,
        array $leaderboard,
        string $tab,
        ?string $dateFilter,
        ?string $curriculum = null,
        ?string $group = null,
        ?string $batch = null
    ): ?array {
        if (! $currentUser) {
            return null;
        }

        if ($curriculum && $currentUser->curriculum !== $curriculum) {
            return null;
        }
        if ($group && $currentUser->group !== $group) {
            return null;
        }
        if ($batch && $currentUser->batch !== $batch) {
            return null;
        }

        $myScore = $tab === 'contributors'
            ? (int) $currentUser->resources()->when($dateFilter, fn ($q) => $q->where('created_at', '>=', $dateFilter))->count()
            : (int) $currentUser->dailyStudyLogs()->when($dateFilter, fn ($q) => $q->where('study_date', '>=', $dateFilter))->sum('total_seconds');

        if ($myScore <= 0) {
            return null;
        }

        $found = collect($leaderboard)->firstWhere('id', $currentUser->id);

        return [
            'rank' => $found ? $found['rank'] : '50+',
            'score' => $myScore,
        ];
    }
}
