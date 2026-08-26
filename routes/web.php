<?php

use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ShortUrlController;
use App\Http\Controllers\SubjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->get('/api/auth/status', function (Request $request) {
    return response()->json([
        'authenticated' => Auth::check(),
        'user' => $request->user(),
    ]);
});

Route::middleware(['throttle:60,1', 'auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/api/short-urls', [ShortUrlController::class, 'store'])->name('short-urls.store');
    Route::post('/blogs/{blog}/react', [BlogController::class, 'toggleReaction'])->name('blogs.react');
    Route::post('/blogs/{blog}/comments', [BlogController::class, 'storeComment'])->name('blogs.comments.store');
    Route::delete('/blogs/comments/{comment}', [BlogController::class, 'destroyComment'])->name('blogs.comments.destroy');
    Route::post('/resources/{resource}/complete', [ResourceController::class, 'toggleComplete'])->name('resources.complete');
});

Route::prefix('admin')
    ->middleware(['throttle:45,1', 'auth', 'verified', 'permission:view admin'])
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

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
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

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
