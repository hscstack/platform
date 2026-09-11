<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        if (mb_strlen($search) < 3) {
            $search = '';
        }
        $sort = $request->input('sort', 'relevant');
        if (! in_array($sort, ['relevant', 'appreciated'], true)) {
            $sort = 'relevant';
        }

        $currentUser = $request->user();

        $query = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.username',
                'users.image_path',
                'users.institution',
                'users.is_verified',
                'users.created_at',
            ])
            ->withCount(['appreciationsReceived']);

        if ($currentUser) {
            $query->where('users.id', '!=', $currentUser->id)
                ->withExists([
                    'appreciationsReceived as is_appreciated' => fn ($q) => $q->where('appreciator_id', $currentUser->id),
                ]);

            // Exclude already appreciated peers in "You May Know" discovery when not searching
            if ($sort === 'relevant' && $search === '') {
                $query->whereDoesntHave('appreciationsReceived', fn ($q) => $q->where('appreciator_id', $currentUser->id));
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%")
                    ->orWhere('users.institution', 'like', "%{$search}%");
            });
        }

        if ($sort === 'appreciated') {
            $query->orderByDesc('appreciations_received_count')->latest('users.id');
        } else {
            $targetLocation = $currentUser?->institution ? trim($currentUser->institution) : '';

            // For guests or users without institution, infer location from Cloudflare headers
            if ($targetLocation === '') {
                $cfCity = trim((string) $request->header('CF-IPCity', ''));
                if ($cfCity !== '' && strcasecmp($cfCity, 'xx') !== 0) {
                    $targetLocation = $cfCity;
                }
            }

            if ($targetLocation !== '') {
                preg_match_all('/[\p{L}\p{N}]{3,}/u', mb_strtolower($targetLocation), $matches);
                $words = array_values(array_unique($matches[0] ?? []));

                $scoreSql = [];
                $bindings = [];

                foreach ($words as $word) {
                    $scoreSql[] = '(CASE WHEN LOWER(users.institution) LIKE ? THEN 1 ELSE 0 END)';
                    $bindings[] = "%{$word}%";
                }

                if (! empty($scoreSql)) {
                    $rawSql = implode(' + ', $scoreSql);
                    $query->selectRaw("({$rawSql}) as match_score", $bindings)
                        ->orderByDesc('match_score');
                }
            }

            $query->latest('users.id');
        }

        $peers = $query->simplePaginate(20)->withQueryString();

        return Inertia::render('Peers/Index', [
            'peers' => $peers,
            'filters' => [
                'search' => $search ?: null,
                'sort' => $sort,
            ],
        ]);
    }
}
