<?php

use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailController as AdminEmailController;
use App\Http\Controllers\Admin\NodeController as AdminNodeController;
use App\Http\Controllers\Admin\NoticeController as AdminNoticeController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');

// Subjects
Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('subjects.index');

Route::middleware('permission:create subjects')->group(function () {
    Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name('subjects.create');
    Route::post('/subjects', [AdminSubjectController::class, 'store'])->name('subjects.store');
});

Route::middleware('permission:edit subjects')->group(function () {
    Route::get('/subjects/edit/{subject}', [AdminSubjectController::class, 'edit'])->name('subjects.edit');
    Route::patch('/subjects/edit/{subject}', [AdminSubjectController::class, 'update'])->name('subjects.update');
});

Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->middleware('permission:delete subjects')->name('subjects.destroy');

// Blogs
Route::get('/blogs', [AdminBlogController::class, 'index'])->name('blogs.index');

Route::middleware('permission:create blogs')->group(function () {
    Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name('blogs.create');
    Route::post('/blogs', [AdminBlogController::class, 'store'])->name('blogs.store');
});

Route::middleware('permission:edit blogs')->group(function () {
    Route::get('/blogs/edit/{blog}', [AdminBlogController::class, 'edit'])->name('blogs.edit');
    Route::post('/blogs/edit/{blog}/patch', [AdminBlogController::class, 'update'])->name('blogs.update');
});

Route::delete('/blogs/{blog}', [AdminBlogController::class, 'destroy'])->middleware('permission:delete blogs')->name('blogs.destroy');

// Nodes (Folders)
Route::middleware('permission:create nodes')->group(function () {
    Route::get('/subjects/{subject:slug}/nodes/create', [AdminNodeController::class, 'create'])->name('nodes.create');
    Route::post('/subjects/{subject}/nodes', [AdminNodeController::class, 'store'])->name('nodes.store');
});

Route::middleware('permission:edit nodes')->group(function () {
    Route::get('/nodes/edit/{node}', [AdminNodeController::class, 'edit'])->name('nodes.edit');
    Route::patch('/subjects/{subject}/nodes/{node}', [AdminNodeController::class, 'update'])->name('nodes.patch');
});

Route::delete('/nodes/{node}', [AdminNodeController::class, 'destroy'])->middleware('permission:delete nodes')->name('nodes.destroy');

Route::get('/subjects/{subject:slug}/nodes/{path?}', [AdminNodeController::class, 'show'])->name('nodes.index')->where('path', '.*');

// Resources
Route::middleware('permission:create resources')->group(function () {
    Route::get('/resources/create', [AdminResourceController::class, 'create']);
    Route::get('/resources/create/bulk/images', [AdminResourceController::class, 'createBulkImages']);
    Route::get('/resources/create/bulk/videos', [AdminResourceController::class, 'createBulkVideos']);
    Route::post('/resources', [AdminResourceController::class, 'store']);
    Route::post('/resources/bulk/images', [AdminResourceController::class, 'storeBulkImages']);
    Route::post('/resources/bulk/videos', [AdminResourceController::class, 'storeBulkVideos']);
});

Route::middleware('permission:edit resources')->group(function () {
    Route::get('/resources/edit/{resource}', [AdminResourceController::class, 'edit']);
    Route::post('/resources/{resource}/patch', [AdminResourceController::class, 'update']);
});

Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->middleware('permission:delete resources');

// Notice
Route::middleware('permission:edit notice')->group(function () {
    Route::get('/notice', [AdminNoticeController::class, 'edit'])->name('notice.edit');
    Route::match(['patch', 'post'], '/notice', [AdminNoticeController::class, 'update'])->name('notice.update');
});

// Cache
Route::post('/clear-cache', function () {
    Cache::flush();

    return back()->with('success', 'Cache cleared.');
})->middleware('permission:clear cache');

// Users
Route::middleware('permission:manage users')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::get('/users/edit/{user}', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/login', [AdminUserController::class, 'loginAs'])->name('users.login-as');
});

// Emails
Route::middleware('permission:send email')->group(function () {
    Route::get('/emails/send', [AdminEmailController::class, 'create'])->name('emails.create');
    Route::post('/emails/send', [AdminEmailController::class, 'store'])->name('emails.store');
});
