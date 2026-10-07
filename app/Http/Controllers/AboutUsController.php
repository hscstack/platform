<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AboutUsController extends Controller
{
    public function index()
    {
        $users = Cache::rememberForever('about_us_info', function () {
            return User::where('is_verified', true)
                ->select(['id', 'name', 'username', 'title', 'about', 'institution', 'image_path'])
                ->with('roles:id,name')
                ->get()
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
