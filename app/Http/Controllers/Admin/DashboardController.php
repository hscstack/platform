<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('admin_dashboard_stats', now()->addSeconds(30), function () {
            $totalVisits = 0;
            $totalUsers = 0;
            $realtimeUsers = 0;
            $topSources = [];
            $isConfigured = false;

            $host = rtrim(config('services.posthog.host', 'https://us.i.posthog.com'), '/');
            $projectId = config('services.posthog.project_id');
            $personalApiKey = config('services.posthog.personal_api_key');

            if ($projectId && $personalApiKey) {
                try {
                    $endpoint = "{$host}/api/projects/{$projectId}/query/";
                    $headers = [
                        'Authorization' => "Bearer {$personalApiKey}",
                        'Content-Type' => 'application/json',
                    ];

                    // Overview Query: Total Pageviews, Unique Visitors, and Realtime Active Users (last 5m)
                    $overviewQuery = "
                        SELECT 
                            count() AS total_pageviews,
                            count(DISTINCT distinct_id) AS unique_visitors,
                            count(DISTINCT if(timestamp >= now() - INTERVAL 5 MINUTE, distinct_id, null)) AS realtime_users
                        FROM events 
                        WHERE event = '\$pageview' 
                          AND timestamp >= now() - INTERVAL 30 DAY
                    ";

                    $resOverview = Http::withHeaders($headers)
                        ->timeout(6)
                        ->post($endpoint, [
                            'query' => [
                                'kind' => 'HogQLQuery',
                                'query' => $overviewQuery,
                            ],
                        ]);

                    if ($resOverview->successful()) {
                        $results = $resOverview->json('results');
                        if (!empty($results) && isset($results[0])) {
                            $totalVisits = (int) ($results[0][0] ?? 0);
                            $totalUsers = (int) ($results[0][1] ?? 0);
                            $realtimeUsers = (int) ($results[0][2] ?? 0);
                            $isConfigured = true;
                        }
                    }

                    // Top Acquisition Sources (Last 30 Days)
                    $sourcesQuery = "
                        SELECT 
                            coalesce(
                                nullif(properties.\$utm_source, ''),
                                nullif(properties.\$initial_utm_source, ''),
                                nullif(properties.\$referrer, ''),
                                'Direct'
                            ) AS source,
                            count() AS count
                        FROM events 
                        WHERE event = '\$pageview' 
                          AND timestamp >= now() - INTERVAL 30 DAY
                        GROUP BY source
                        ORDER BY count DESC
                        LIMIT 10
                    ";

                    $resSources = Http::withHeaders($headers)
                        ->timeout(6)
                        ->post($endpoint, [
                            'query' => [
                                'kind' => 'HogQLQuery',
                                'query' => $sourcesQuery,
                            ],
                        ]);

                    if ($resSources->successful()) {
                        $sourceResults = $resSources->json('results') ?? [];
                        $topSources = collect($sourceResults)->map(function ($row) {
                            $rawSource = $row[0] ?? 'Direct';
                            $cleanSource = parse_url($rawSource, PHP_URL_HOST) ?: $rawSource;

                            return [
                                'source' => $cleanSource,
                                'visits' => (int) ($row[1] ?? 0),
                            ];
                        })->values()->toArray();
                    }
                } catch (\Throwable $e) {
                    Log::warning('PostHog Analytics Query Failed: ' . $e->getMessage());
                }
            }

            return [
                'total_visits' => $totalVisits,
                'total_users' => $totalUsers,
                'realtime_users' => $realtimeUsers,
                'top_sources' => $topSources,
                'configured' => $isConfigured,
            ];
        });

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
