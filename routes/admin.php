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

Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('subjects.index');
Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name('subjects.create');
Route::get('/subjects/edit/{subject}', [AdminSubjectController::class, 'edit'])->name('subjects.edit');

Route::get('/blogs', [AdminBlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name('blogs.create');
Route::get('/blogs/edit/{blog}', [AdminBlogController::class, 'edit'])->name('blogs.edit');

Route::get('/subjects/{subject:slug}/nodes/create', [AdminNodeController::class, 'create'])->name('nodes.create');
Route::get('/nodes/edit/{node}', [AdminNodeController::class, 'edit'])->name('nodes.edit');

Route::get('/resources/create', [AdminResourceController::class, 'create']);
Route::get('/resources/create/bulk/images', [AdminResourceController::class, 'createBulkImages']);
Route::get('/resources/create/bulk/videos', [AdminResourceController::class, 'createBulkVideos']);
Route::get('/resources/edit/{resource}', [AdminResourceController::class, 'edit']);

Route::get('/notice', [AdminNoticeController::class, 'edit'])->name('notice.edit');
Route::get('/subjects/{subject:slug}/nodes/{path?}', [AdminNodeController::class, 'show'])->name('nodes.index')->where('path', '.*');

Route::patch('/subjects/edit/{subject}', [AdminSubjectController::class, 'update'])->middleware('permission:edit subjects')->name('subjects.update');
Route::post('/subjects', [AdminSubjectController::class, 'store'])->middleware('permission:create subjects')->name('subjects.store');

Route::post('/blogs/edit/{blog}/patch', [AdminBlogController::class, 'update'])->middleware('permission:edit blogs')->name('blogs.update');
Route::post('/blogs', [AdminBlogController::class, 'store'])->middleware('permission:create blogs')->name('blogs.store');

Route::post('/subjects/{subject}/nodes', [AdminNodeController::class, 'store'])->middleware('permission:create nodes')->name('nodes.store');
Route::patch('/subjects/{subject}/nodes/{node}', [AdminNodeController::class, 'update'])->middleware('permission:edit nodes')->name('nodes.patch');

Route::post('/resources', [AdminResourceController::class, 'store'])->middleware('permission:create resources');
Route::post('/resources/{resource}/patch', [AdminResourceController::class, 'update'])->middleware('permission:edit resources');

Route::post('/resources/bulk/images', [AdminResourceController::class, 'storeBulkImages'])->middleware('permission:create resources');
Route::post('/resources/bulk/videos', [AdminResourceController::class, 'storeBulkVideos'])->middleware('permission:create resources');

Route::match(['patch', 'post'], '/notice', [AdminNoticeController::class, 'update'])->middleware('permission:edit notice')->name('notice.update');
Route::post('/clear-cache', function () {
    Cache::flush();

    return back()->with('success', 'Cache cleared.');
})->middleware('permission:clear cache');

Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->middleware('permission:delete resources');
Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->middleware('permission:delete subjects');
Route::delete('/blogs/{blog}', [AdminBlogController::class, 'destroy'])->middleware('permission:delete blogs');
Route::delete('/nodes/{node}', [AdminNodeController::class, 'destroy'])->middleware('permission:delete nodes');

Route::middleware('permission:manage users')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::get('/users/edit/{user}', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/login', [AdminUserController::class, 'loginAs'])->name('users.login-as');
});



Route::get('/emails/send', [AdminEmailController::class, 'create'])->name('emails.create');

Route::middleware('permission:send email')->group(function () {
    Route::post('/emails/send', [AdminEmailController::class, 'store'])->name('emails.store');
});

