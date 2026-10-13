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
        $tab = $request->input('tab');
        if (! in_array($tab, ['students', 'contributors'], true)) {
            $tab = 'students';
        }

        $timeframe = $request->input('timeframe');
        if (! in_array($timeframe, ['this_week', 'this_month', 'all_time'], true)) {
            $timeframe = 'this_week';
        }

        $dateFilter = match ($timeframe) {
            'this_week' => Carbon::now()->startOfWeek()->toDateString(),
            'this_month' => Carbon::now()->startOfMonth()->toDateString(),
            'all_time' => null,
        };

        $currentUser = $request->user();
        $currentUserRank = null;

        if ($tab === 'contributors') {
            $query = User::query()
                ->select([
                    'users.id',
                    'users.name',
                    'users.username',
                    'users.image_path',
                    'users.institution',
                    'users.is_verified',
                    'users.title',
                ])
                ->where(function ($q) {
                    $q->whereNull('banned_until')->orWhere('banned_until', '<=', now());
                })
                ->whereHas('resources', function ($q) use ($dateFilter) {
                    $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter));
                })
                ->withCount(['resources' => function ($q) use ($dateFilter) {
                    $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter));
                }])
                ->orderByDesc('resources_count')
                ->latest('users.id');

            $leaderboard = $query->take(50)->get()->map(function ($u, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'image_url' => $u->image_url,
                    'institution' => $u->institution,
                    'title' => $u->title,
                    'is_verified' => $u->is_verified,
                    'score' => (int) $u->resources_count,
                ];
            });

            if ($currentUser) {
                $myScore = (int) $currentUser->resources()
                    ->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter))
                    ->count();

                if ($myScore > 0) {
                    // Check if user is in the fetched top 50
                    $found = $leaderboard->firstWhere('id', $currentUser->id);
                    if ($found) {
                        $currentUserRank = [
                            'rank' => $found['rank'],
                            'score' => $myScore,
                        ];
                    } else {
                        $higherCount = User::query()
                            ->where(function ($q) {
                                $q->whereNull('banned_until')->orWhere('banned_until', '<=', now());
                            })
                            ->whereHas('resources', function ($q) use ($dateFilter) {
                                $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter));
                            })
                            ->withCount(['resources' => function ($q) use ($dateFilter) {
                                $q->when($dateFilter, fn ($sq) => $sq->where('created_at', '>=', $dateFilter));
                            }])
                            ->having('resources_count', '>', $myScore)
                            ->count();

                        $currentUserRank = [
                            'rank' => $higherCount + 1,
                            'score' => $myScore,
                        ];
                    }
                }
            }
        } else {
            // Students: Ranked by total study time
            $query = User::query()
                ->select([
                    'users.id',
                    'users.name',
                    'users.username',
                    'users.image_path',
                    'users.institution',
                    'users.is_verified',
                    'users.title',
                    'users.curriculum',
                    'users.group',
                ])
                ->where(function ($q) {
                    $q->whereNull('banned_until')->orWhere('banned_until', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('activity_privacy')->orWhere('activity_privacy', '!=', 'private');
                })
                ->whereHas('dailyStudyLogs', function ($q) use ($dateFilter) {
                    $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter))
                        ->where('total_seconds', '>', 0);
                })
                ->withSum(['dailyStudyLogs as total_study_seconds' => function ($q) use ($dateFilter) {
                    $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter));
                }], 'total_seconds')
                ->withCount('nodeCompletions')
                ->orderByDesc('total_study_seconds')
                ->latest('users.id');

            $leaderboard = $query->take(50)->get()->map(function ($u, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'image_url' => $u->image_url,
                    'institution' => $u->institution,
                    'title' => $u->title,
                    'curriculum' => $u->curriculum,
                    'group' => $u->group,
                    'is_verified' => $u->is_verified,
                    'score' => (int) $u->total_study_seconds,
                    'completed_chapters_count' => (int) $u->node_completions_count,
                ];
            });

            if ($currentUser) {
                $myScore = (int) $currentUser->dailyStudyLogs()
                    ->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter))
                    ->sum('total_seconds');

                if ($myScore > 0) {
                    $found = $leaderboard->firstWhere('id', $currentUser->id);
                    if ($found) {
                        $currentUserRank = [
                            'rank' => $found['rank'],
                            'score' => $myScore,
                        ];
                    } else {
                        $higherCount = User::query()
                            ->where(function ($q) {
                                $q->whereNull('banned_until')->orWhere('banned_until', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('activity_privacy')->orWhere('activity_privacy', '!=', 'private');
                            })
                            ->whereHas('dailyStudyLogs', function ($q) use ($dateFilter) {
                                $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter))
                                    ->where('total_seconds', '>', 0);
                            })
                            ->withSum(['dailyStudyLogs as total_study_seconds' => function ($q) use ($dateFilter) {
                                $q->when($dateFilter, fn ($sq) => $sq->where('study_date', '>=', $dateFilter));
                            }], 'total_seconds')
                            ->having('total_study_seconds', '>', $myScore)
                            ->count();

                        $currentUserRank = [
                            'rank' => $higherCount + 1,
                            'score' => $myScore,
                        ];
                    }
                }
            }
        }

        return Inertia::render('Leaderboard/Index', [
            'tab' => $tab,
            'timeframe' => $timeframe,
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank,
        ]);
    }
}
