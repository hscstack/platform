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
