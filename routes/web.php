<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NodeController as AdminNodeController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/privacy-policy', 'legal/PrivacyPolicy');
Route::inertia('/terms-service', 'legal/TermsConditions');
Route::inertia('/join', 'platform/JoinTeam');
Route::inertia('/about-us', 'platform/AboutUs');
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/subjects', [AdminSubjectController::class, 'index'])->name("subjects.index");
    Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name("subjects.create");
    Route::post('/subjects', [AdminSubjectController::class, 'store'])->name("subjects.store");

});

Route::get('/', [SubjectController::class, 'index']);
Route::get('/resources/{resource:id}', [ResourceController::class, 'show']);
Route::get('/{subject:slug}', [SubjectController::class, 'show']);
Route::get('/{subject:slug}/{path}', [NodeController::class, 'show'])->where('path', '.*');
