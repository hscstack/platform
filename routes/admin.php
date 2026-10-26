<?php

use App\Http\Controllers\Admin\ChatSettingsController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailController as AdminEmailController;
use App\Http\Controllers\Admin\ForumController as AdminForumController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');

// Subjects & Chapters
Route::get('/subjects', [CurriculumController::class, 'subjectIndex'])->name('subjects.index');
Route::get('/subjects/{subject}/chapters', [CurriculumController::class, 'chapterIndex'])->name('chapters.index');

Route::middleware('permission:manage curriculum')->group(function () {
    Route::post('/subjects', [CurriculumController::class, 'storeSubject'])->name('subjects.store');
    Route::patch('/subjects/edit/{subject}', [CurriculumController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [CurriculumController::class, 'destroySubject'])->name('subjects.destroy');

    Route::post('/subjects/{subject}/chapters/batch', [CurriculumController::class, 'batchStoreChapters'])->name('chapters.batch');
    Route::post('/subjects/{subject}/chapters', [CurriculumController::class, 'storeChapter'])->name('chapters.store');
    Route::patch('/subjects/{subject}/chapters/{chapter}', [CurriculumController::class, 'updateChapter'])->name('chapters.patch');
    Route::delete('/chapters/{chapter}', [CurriculumController::class, 'destroyChapter'])->name('chapters.destroy');
});

// Resources
Route::middleware('permission:moderate resources')->group(function () {
    Route::get('/moderation/resources', [AdminResourceController::class, 'moderationIndex'])->name('resources.moderation.index');
    Route::post('/resources/approve', [AdminResourceController::class, 'approve'])->name('resources.approve');
    Route::post('/resources/reject', [AdminResourceController::class, 'reject'])->name('resources.reject');
});

Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->middleware('can:delete,resource');

// Platform Settings & Maintenance
Route::middleware('permission:manage settings')->group(function () {
    Route::get('/settings/platform', [SettingsController::class, 'platform'])->name('platform.settings');
    Route::post('/settings/platform', [SettingsController::class, 'updatePlatform'])->name('platform.settings.update');

    Route::get('/notice', [SettingsController::class, 'notice'])->name('notice.edit');
    Route::match(['patch', 'post'], '/notice', [SettingsController::class, 'updateNotice'])->name('notice.update');

    Route::get('/peers/settings', [SettingsController::class, 'peers'])->name('peers.settings.edit');
    Route::post('/peers/settings', [SettingsController::class, 'updatePeers'])->name('peers.settings.update');

    Route::post('/clear-cache', function () {
        Cache::flush();

        return back()->with('success', 'Cache cleared.');
    })->name('cache.clear');
});

// Users
Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:view users')->name('users.index');

Route::middleware('permission:manage users')->group(function () {
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/edit/{user}', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/send-delete-link', [AdminUserController::class, 'sendDeleteLink'])->name('users.send-delete-link');
});

Route::post('/users/{user}/login', [AdminUserController::class, 'loginAs'])->middleware('permission:impersonate users')->name('users.login-as');

// Emails
Route::middleware('permission:send email')->group(function () {
    Route::get('/emails/send', [AdminEmailController::class, 'create'])->name('emails.create');
    Route::get('/emails/recipients', [AdminEmailController::class, 'recipients'])->name('emails.recipients');
    Route::post('/emails/send', [AdminEmailController::class, 'store'])->name('emails.store');
});

// Chat Management & Settings
Route::middleware('permission:manage chat')->group(function () {
    Route::get('/chat', [ChatSettingsController::class, 'edit'])->name('chat.edit');
    Route::post('/chat/settings', [ChatSettingsController::class, 'update'])->name('chat.settings.update');
    Route::post('/chat/clear', [ChatSettingsController::class, 'clearMessages'])->name('chat.clear');
});

// Centralized Reports & Moderation (Only users with `manage reports` can access reports & ban users)
Route::middleware('permission:manage reports')->group(function () {
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.update-status');
    Route::delete('/reports/clear', [AdminReportController::class, 'clear'])->name('reports.clear');
    Route::delete('/reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');

    // Centralized user suspension action (with backward-compatible route name for existing modals)
    Route::post('/reports/users/{user}/ban', [AdminReportController::class, 'updateUserBan'])->name('users.ban');
    Route::post('/chat/users/{user}/ban', [AdminReportController::class, 'updateUserBan'])->name('chat.users.ban');

    // Platform-wide Banned Words & Safety settings
    Route::post('/reports/banned-words', [AdminReportController::class, 'updateBannedWords'])->name('reports.banned-words.update');
});

// Support Tickets
Route::middleware('permission:manage tickets')->group(function () {
    Route::get('/tickets', [AdminSupportTicketController::class, 'index'])->name('tickets.index');
    Route::patch('/tickets/{ticket}', [AdminSupportTicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [AdminSupportTicketController::class, 'destroy'])->name('tickets.destroy');
});

// Forum Management & Settings
Route::middleware('permission:manage forums')->group(function () {
    Route::get('/forums', [AdminForumController::class, 'index'])->name('forums.index');
    Route::patch('/forums/{post:id}/lock', [AdminForumController::class, 'toggleLock'])->name('forums.lock');
    Route::patch('/forums/{post:id}/status', [AdminForumController::class, 'updateModerationStatus'])->name('forums.update-status');
    Route::delete('/forums/{post:id}', [AdminForumController::class, 'destroy'])->name('forums.destroy');

    Route::get('/forums/settings', [AdminForumController::class, 'settings'])->name('forums.settings.edit');
    Route::post('/forums/settings', [AdminForumController::class, 'updateSettings'])->name('forums.settings.update');
});
