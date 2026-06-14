<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class AboutUsController extends Controller
{

    public function index()
    {
        return Inertia::render('platform/AboutUs', [
            'users' => User::with('roles')->get(),
        ]);
    }
}
