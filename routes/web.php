<?php

use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ForumAnswerController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumVoteController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ShortUrlController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserProfileController;
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
    Route::post('/nodes/{node}/vote', [NodeController::class, 'vote'])->name('nodes.vote');
    Route::post('/u/{user}/appreciate', [UserProfileController::class, 'toggleAppreciate'])->name('user.appreciate');
    Route::get('/support/my-tickets', [SupportTicketController::class, 'myTickets'])->name('support.my-tickets');
    Route::post('/support/tickets', [SupportTicketController::class, 'store'])->name('support.tickets.store');

    // Forum Authenticated Actions
    Route::get('/forum/ask', [ForumController::class, 'create'])->name('forum.create');
    Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');
    Route::delete('/forum/posts/{post:id}', [ForumController::class, 'destroy'])->name('forum.destroy');
    Route::post('/forum/posts/{post:id}/toggle-answered', [ForumController::class, 'toggleAnswered'])->name('forum.toggle-answered');
    Route::post('/forum/posts/{post:id}/report', [ForumController::class, 'report'])->name('forum.posts.report');
    Route::post('/forum/posts/{post:id}/answers', [ForumAnswerController::class, 'store'])->name('forum.answers.store');
    Route::delete('/forum/answers/{answer}', [ForumAnswerController::class, 'destroy'])->name('forum.answers.destroy');
    Route::post('/forum/answers/{answer}/report', [ForumAnswerController::class, 'report'])->name('forum.answers.report');
    Route::post('/forum/posts/{post:id}/vote', [ForumVoteController::class, 'votePost'])->name('forum.posts.vote');
    Route::post('/forum/answers/{answer}/vote', [ForumVoteController::class, 'voteAnswer'])->name('forum.answers.vote');

    Route::get('/me', function (Request $request) {
        return redirect()->route('user.profile', ['username' => $request->user()->username]);
    })->name('me');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    // Global Chat Actions
    Route::post('/api/chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');
    Route::delete('/api/chat/messages/{message}', [ChatController::class, 'destroy'])->name('chat.messages.destroy');
    Route::post('/api/chat/reports', [ChatController::class, 'report'])->name('chat.reports.store');
    Route::middleware('throttle:60,1')->post('/api/chat/messages/{message}/reactions', [ChatController::class, 'toggleReaction'])->name('chat.messages.react');
});

// Global Chat Messages List (Public Read)
Route::middleware('throttle:60,1')->get('/api/chat/messages', [ChatController::class, 'index'])->name('chat.messages.index');

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
    Route::inertia('/donate', 'Donate')->name('donate');
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::inertia('/join', 'platform/JoinTeam');
    Route::inertia('/guide', 'ContributorGuide');
    Route::inertia('/ai', 'ai/Index');
    Route::inertia('/projects', 'Projects');

    Route::get('/about-us', [AboutUsController::class, 'index']);

    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/onboarding', [AuthController::class, 'showOnboarding'])->name('onboarding');
    Route::post('/onboarding', [AuthController::class, 'completeOnboarding'])->name('onboarding.complete');

    Route::get('/blogs', [BlogController::class, 'index']);
    Route::get('/blogs/{blog}', [BlogController::class, 'show']);
    Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');
    Route::get('/forum/questions/{post:slug}', [ForumController::class, 'show'])->name('forum.show');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/u/{username}', [UserProfileController::class, 'show'])->name('user.profile');

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
