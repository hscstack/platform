<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Traffic;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('admin_dashboard_stats', now()->addHour(), function () {
            return [
                'stats' => [
                    'total_visits' => Traffic::sum('visits'),

                    'total_users' => Traffic::count(),

                    'top_sources' => Traffic::select('source')
                        ->selectRaw('count(*) as visits')
                        ->groupBy('source')
                        ->orderByDesc('visits')
                        ->limit(10)
                        ->get()->toArray(),
                ],
            ];
        });

        return Inertia::render('admin/Dashboard', $stats);
    }
}
