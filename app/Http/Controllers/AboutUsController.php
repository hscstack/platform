<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AboutUsController extends Controller
{
    public function index()
    {
        $users = Cache::remember('about_us_info', now()->addHours(24), function () {
            return User::where('is_verified', true)
                ->select(['id', 'name', 'username', 'title', 'about', 'institution', 'image_path', 'priority'])
                ->with('roles:id,name')
                ->get()
                ->sort(function (User $a, User $b) {
                    // 1. Priority (descending: higher priority first)
                    if ($a->priority !== $b->priority) {
                        return $b->priority <=> $a->priority;
                    }

                    // 2. Role rank: admin (1) -> editor (2) -> staff/manager (3) -> other (4)
                    $getRoleRank = function (User $user) {
                        if ($user->roles->contains('name', 'admin')) {
                            return 1;
                        }
                        if ($user->roles->contains('name', 'editor')) {
                            return 2;
                        }
                        if ($user->roles->isNotEmpty()) {
                            return 3;
                        }

                        return 4;
                    };

                    $rankA = $getRoleRank($a);
                    $rankB = $getRoleRank($b);

                    if ($rankA !== $rankB) {
                        return $rankA <=> $rankB;
                    }

                    // 3. Stable tie-breaker by id
                    return $a->id <=> $b->id;
                })
                ->values()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'title' => $user->title,
                    'about' => $user->about,
                    'institution' => $user->institution,
                    'image_url' => $user->image_url,
                    'roles' => $user->roles->map(fn ($role) => ['name' => $role->name])->all(),
                ])
                ->all();
        });

        return Inertia::render('platform/AboutUs', [
            'users' => $users,
        ]);
    }
}
