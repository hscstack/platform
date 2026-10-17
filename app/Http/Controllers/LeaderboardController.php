<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $tz = 'Asia/Dhaka';
        $now = Carbon::now($tz);

        $startOfWeek = $now->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth()->startOfDay();

        $dateFilter = match ($timeframe) {
            'this_week' => $startOfWeek->toDateString(),
            'this_month' => $startOfMonth->toDateString(),
            'all_time' => null,
        };

        $utcDateFilter = match ($timeframe) {
            'this_week' => $startOfWeek->copy()->setTimezone('UTC')->toDateTimeString(),
            'this_month' => $startOfMonth->copy()->setTimezone('UTC')->toDateTimeString(),
            'all_time' => null,
        };

        [$leaderboard, $currentUserRank] = $tab === 'contributors'
            ? $this->getContributors($request->user(), $utcDateFilter)
            : $this->getStudents($request->user(), $dateFilter);

        return Inertia::render('Leaderboard/Index', [
            'tab' => $tab,
            'timeframe' => $timeframe,
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank,
        ]);
    }

    private function getContributors(?User $user, ?string $dateFilter): array
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

        $myScore = $user ? (int) $user->resources()->when($dateFilter, fn ($q) => $q->where('created_at', '>=', $dateFilter))->count() : 0;

        return $this->formatLeaderboard($users, 'resources_count', $user, $myScore);
    }

    private function getStudents(?User $user, ?string $dateFilter): array
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

        $myScore = $user ? (int) $user->dailyStudyLogs()->when($dateFilter, fn ($q) => $q->where('study_date', '>=', $dateFilter))->sum('total_seconds') : 0;

        return $this->formatLeaderboard($users, 'total_study_seconds', $user, $myScore);
    }

    private function formatLeaderboard($users, string $scoreKey, ?User $currentUser, int $myScore): array
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

        $currentUserRank = null;
        if ($currentUser && $myScore > 0) {
            $found = collect($leaderboard)->firstWhere('id', $currentUser->id);
            $currentUserRank = [
                'rank' => $found ? $found['rank'] : '50+',
                'score' => $myScore,
            ];
        }

        return [$leaderboard, $currentUserRank];
    }
}
