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
            return User::has('roles')->with('roles')->get()->toArray();
        });

        return Inertia::render('platform/AboutUs', [
            'users' => $users,
        ]);
    }
}
