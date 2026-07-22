    <?php

    use App\Http\Controllers\AboutUsController;
    use App\Http\Controllers\Admin\AuthController;
    use App\Http\Controllers\Admin\BlogController as AdminBlogController;
    use App\Http\Controllers\Admin\DashboardController;
    use App\Http\Controllers\Admin\NodeController as AdminNodeController;
    use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
    use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
    use App\Http\Controllers\Admin\NoticeController as AdminNoticeController;
    use App\Http\Controllers\Admin\UserController as AdminUserController;
    use App\Http\Controllers\BlogController;
    use App\Http\Controllers\NodeController;
    use App\Http\Controllers\ResourceController;
    use App\Http\Controllers\SubjectController;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Route;





    Route::prefix('admin')->middleware(['throttle:45,1', 'auth', 'verified', 'permission:view admin'])->name('admin.')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::get('/subjects', [AdminSubjectController::class, 'index'])->name("subjects.index");
        Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name("subjects.create");
        Route::get('/subjects/edit/{subject}', [AdminSubjectController::class, 'edit'])->name("subjects.edit");

        Route::get('/blogs', [AdminBlogController::class, 'index'])->name("blogs.index");
        Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name("blogs.create");
        Route::get('/blogs/edit/{blog}', [AdminBlogController::class, 'edit'])->name("blogs.edit");

        Route::get('/subjects/{subject:slug}/nodes/create', [AdminNodeController::class, 'create'])->name('nodes.create');
        Route::get('/nodes/edit/{node}', [AdminNodeController::class, 'edit'])->name('nodes.edit');

        Route::get('/resources/create', [AdminResourceController::class, 'create']);
        Route::get('/resources/edit/{resource}', [AdminResourceController::class, 'edit']);

        Route::get('/users', [AdminUserController::class, 'index'])->name("users.index");
        Route::get('/users/create', [AdminUserController::class, 'create']);
        Route::get('/users/edit/{user}', [AdminUserController::class, 'edit']);

        Route::get('/notice', [AdminNoticeController::class, 'edit'])->name('notice.edit');
        Route::get('/subjects/{subject:slug}/nodes/{path?}', [AdminNodeController::class, 'show'])->name('nodes.index')->where('path', '.*');

        Route::patch('/subjects/edit/{subject}', [AdminSubjectController::class, 'update'])->middleware("permission:edit subjects")->name("subjects.update");
        Route::post('/subjects', [AdminSubjectController::class, 'store'])->middleware("permission:create subjects")->name("subjects.store");

        Route::post('/blogs/edit/{blog}/patch', [AdminBlogController::class, 'update'])->middleware("permission:edit blogs")->name("blogs.update");
        Route::post('/blogs', [AdminBlogController::class, 'store'])->middleware("permission:create blogs")->name("blogs.store");

        Route::post('/subjects/{subject}/nodes', [AdminNodeController::class, 'store'])->middleware("permission:create nodes")->name('nodes.store');
        Route::patch('/subjects/{subject}/nodes/{node}', [AdminNodeController::class, 'update'])->middleware("permission:edit nodes")->name('nodes.patch');

        Route::post('/resources', [AdminResourceController::class, 'store'])->middleware("permission:create resources");
        Route::post('/resources/{resource}/patch', [AdminResourceController::class, 'update'])->middleware("permission:edit resources");

        Route::patch('/notice', [AdminNoticeController::class, 'update'])->middleware("permission:edit notice")->name('notice.update');
        Route::post('/clear-cache', function () {
            Cache::flush();
            return back()->with('success', 'Cache cleared.');
        })->middleware("permission:clear cache");

        Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->middleware("permission:delete resources");
        Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->middleware("permission:delete subjects");
        Route::delete('/blogs/{blog}', [AdminBlogController::class, 'destroy'])->middleware("permission:delete blogs");
        Route::delete('/nodes/{node}', [AdminNodeController::class, 'destroy'])->middleware("permission:delete nodes");

        Route::middleware('permission:manage users')->group(function () {
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
            Route::post('/users', [AdminUserController::class, 'store'])->name("users.store");
        });
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name("users.update");
    });

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');


    Route::middleware('throttle:60,1')->group(function () {
        Route::inertia('/privacy-policy', 'legal/PrivacyPolicy');
        Route::inertia('/terms-service', 'legal/TermsConditions');
        Route::inertia('/join', 'platform/JoinTeam');

        Route::get('/about-us', [AboutUsController::class, 'index']);

        Route::get('/login', [AuthController::class, 'index'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/blogs', [BlogController::class, 'index']);
        Route::get('/blogs/{blog}', [BlogController::class, 'show']);

        Route::get('/', [SubjectController::class, 'index'])->name('index');
        Route::get('/resources/{id}', [ResourceController::class, 'show']);
        Route::get('/{subject:slug}', [SubjectController::class, 'show']);
        Route::get('/{subject:slug}/{path}', [NodeController::class, 'show'])->where('path', '.*');
    });
