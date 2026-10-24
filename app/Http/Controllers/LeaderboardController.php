<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $dateFilter = match ($timeframe) {
            'this_week' => Carbon::now()->startOfWeek(Carbon::SATURDAY)->toDateString(),
            'this_month' => Carbon::now()->startOfMonth()->toDateString(),
            'all_time' => null,
        };

        // Cache the formatted top 50 leaderboard for 30 minutes
        $cacheKey = "leaderboard:{$tab}:{$timeframe}";
        $leaderboard = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tab, $dateFilter) {
            return $tab === 'contributors'
                ? $this->queryContributors($dateFilter)
                : $this->queryStudents($dateFilter);
        });

        $currentUserRank = $this->resolveUserRank($request->user(), $leaderboard, $tab, $dateFilter);

        return Inertia::render('Leaderboard/Index', [
            'tab' => $tab,
            'timeframe' => $timeframe,
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank,
        ]);
    }

    private function queryContributors(?string $dateFilter): array
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'image_path', 'institution', 'is_verified', 'title'])
            ->where(fn ($q) => $q->whereNull('banned_until')->orWhere('banned_until', '<=', now()))
            ->whereHas('resources', fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter)))
            ->withCount(['resources' => fn ($q) => $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter))])
            ->orderByDesc('resources_count')
            ->latest('id')
            ->take(50)
            ->get();

        return $this->formatLeaderboard($users, 'resources_count');
    }

    private function queryStudents(?string $dateFilter): array
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'image_path', 'institution', 'is_verified', 'title', 'curriculum', 'group'])
            ->where(fn ($q) => $q->whereNull('banned_until')->orWhere('banned_until', '<=', now()))
            ->where(fn ($q) => $q->whereNull('activity_privacy')->orWhere('activity_privacy', '!=', 'private'))
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
                'is_verified' => (bool) $u->is_verified,
                'score' => $score,
            ];
        }

        return $leaderboard;
    }

    private function resolveUserRank(?User $currentUser, array $leaderboard, string $tab, ?string $dateFilter): ?array
    {
        if (! $currentUser) {
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
