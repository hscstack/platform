<?php

use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SubjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1', 'auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('admin')
    ->middleware(['throttle:45,1', 'auth', 'verified', 'permission:view admin'])
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1,login');

Route::get('/local/oauth2callback', function (Request $request) {
    abort_unless(app()->environment('local'), 403);

    dd($request->code);
});

Route::middleware('throttle:60,1')->group(function () {
    Route::inertia('/privacy-policy', 'legal/PrivacyPolicy');
    Route::inertia('/terms-service', 'legal/TermsConditions');
    Route::inertia('/content-policy', 'legal/ContentPolicy');
    Route::inertia('/support', 'Support');
    Route::inertia('/join', 'platform/JoinTeam');
    Route::inertia('/guide', 'ContributorGuide');
    Route::inertia('/ai', 'ai/Index');
    Route::inertia('/projects', 'Projects');

    Route::get('/about-us', [AboutUsController::class, 'index']);

    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/blogs', [BlogController::class, 'index']);
    Route::get('/blogs/{blog}', [BlogController::class, 'show']);

    Route::get('/', [SubjectController::class, 'index'])
        ->defaults('course', 'hsc')
        ->name('index');

    Route::get('/ssc', [SubjectController::class, 'index'])
        ->defaults('course', 'ssc')
        ->name('ssc.index');

    Route::get('/resources/{id}', [ResourceController::class, 'show']);
    Route::get('/{subject:slug}', [SubjectController::class, 'show']);
    Route::get('/{subject:slug}/{path}', [NodeController::class, 'show'])->where('path', '.*');
});
