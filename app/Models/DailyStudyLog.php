<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStudyLog extends Model
{
    protected $fillable = [
        'user_id',
        'study_date',
        'total_seconds',
    ];

    protected function casts(): array
    {
        return [
            'study_date' => 'date:Y-m-d',
            'total_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get heatmap series and streaks stats for a given user.
     */
    public static function getHeatmapAndStatsForUser(User $user): array
    {
        $today = Carbon::today();
        $startDate = (clone $today)->subDays(364);

        $logs = static::where('user_id', $user->id)
            ->where('study_date', '>=', $startDate->format('Y-m-d'))
            ->get(['study_date', 'total_seconds'])
            ->keyBy(function ($log) {
                return Carbon::parse($log->study_date)->format('Y-m-d');
            });

        $period = CarbonPeriod::create($startDate, $today);
        $heatmapData = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $seconds = (int) ($logs->get($dateStr)?->total_seconds ?? 0);

            $level = 0;
            if ($seconds > 0) {
                if ($seconds < 7200) {
                    $level = 1;
                } elseif ($seconds < 14400) {
                    $level = 2;
                } elseif ($seconds < 21600) {
                    $level = 3;
                } elseif ($seconds < 28800) {
                    $level = 4;
                } else {
                    $level = 5;
                }
            }

            $heatmapData[] = [
                'date' => $dateStr,
                'seconds' => $seconds,
                'level' => $level,
            ];
        }

        $allActiveLogs = static::where('user_id', $user->id)
            ->where('total_seconds', '>', 0)
            ->orderBy('study_date', 'asc')
            ->pluck('study_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values();

        $activeDatesSet = array_flip($allActiveLogs->toArray());
        $totalSeconds = (int) static::where('user_id', $user->id)->sum('total_seconds');
        $totalDays = count($activeDatesSet);

        // Calculate current streak
        $currentStreak = 0;
        $checkDate = clone $today;

        if (! isset($activeDatesSet[$checkDate->format('Y-m-d')])) {
            $checkDate->subDay();
        }

        while (isset($activeDatesSet[$checkDate->format('Y-m-d')])) {
            $currentStreak++;
            $checkDate->subDay();
        }

        // Calculate longest streak
        $longestStreak = 0;
        $tempStreak = 0;
        $prevDate = null;

        foreach ($allActiveLogs as $dateStr) {
            $currentCarbon = Carbon::parse($dateStr);
            if ($prevDate === null) {
                $tempStreak = 1;
            } else {
                $diff = $prevDate->diffInDays($currentCarbon);
                if ($diff === 1) {
                    $tempStreak++;
                } elseif ($diff > 1) {
                    $tempStreak = 1;
                }
            }
            $prevDate = $currentCarbon;
            if ($tempStreak > $longestStreak) {
                $longestStreak = $tempStreak;
            }
        }

        return [
            'heatmapData' => $heatmapData,
            'stats' => [
                'currentStreak' => $currentStreak,
                'longestStreak' => $longestStreak,
                'totalSeconds' => $totalSeconds,
                'totalDays' => $totalDays,
            ],
        ];
    }
}
