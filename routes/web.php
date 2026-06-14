    <?php

    use App\Http\Controllers\AboutUsController;
    use App\Http\Controllers\Admin\AuthController;
    use App\Http\Controllers\Admin\DashboardController;
    use App\Http\Controllers\Admin\NodeController as AdminNodeController;
    use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
    use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
    use App\Http\Controllers\Admin\UserController as AdminUserController;
    use App\Http\Controllers\NodeController;
    use App\Http\Controllers\ResourceController;
    use App\Http\Controllers\SubjectController;
    use Illuminate\Support\Facades\Route;

    Route::inertia('/privacy-policy', 'legal/PrivacyPolicy');
    Route::inertia('/terms-service', 'legal/TermsConditions');
    Route::inertia('/join', 'platform/JoinTeam');

    Route::prefix('admin')->middleware(['auth', 'verified', 'role:manager|admin|editor'])->name('admin.')->group(function () {

        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/subjects', [AdminSubjectController::class, 'index'])->name("subjects.index");
        Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name("subjects.create");
        Route::get('/subjects/edit/{subject}', [AdminSubjectController::class, 'edit'])->name("subjects.edit");

        Route::get('/subjects/{subject:slug}/nodes/create', [AdminNodeController::class, 'create'])->name('nodes.create');
        Route::get('/subjects/{subject}/nodes/edit/{node}', [AdminNodeController::class, 'edit'])->name('nodes.edit');

        Route::get('/resources/create', [AdminResourceController::class, 'create']);
        Route::get('/resources/edit/{resource}', [AdminResourceController::class, 'edit']);

        Route::get('/users', [AdminUserController::class, 'index'])->name("users.index");
        Route::get('/users/create', [AdminUserController::class, 'create']);
        Route::get('/users/edit/{user}', [AdminUserController::class, 'edit']);

        Route::get('/subjects/{subject:slug}/nodes/{path?}', [AdminNodeController::class, 'show'])->name('nodes.index')->where('path', '.*');
    });

    Route::prefix('admin')->middleware(['auth', 'verified', 'role:editor|admin'])->name('admin.')->group(function () {

        Route::patch('/subjects/edit/{subject}', [AdminSubjectController::class, 'update'])->name("subjects.update");
        Route::post('/subjects', [AdminSubjectController::class, 'store'])->name("subjects.store");

        Route::post('/subjects/{subject}/nodes', [AdminNodeController::class, 'store'])->name('nodes.store');
        Route::patch('/subjects/{subject}/nodes/{node}', [AdminNodeController::class, 'update'])->name('nodes.patch');

        Route::post('/resources', [AdminResourceController::class, 'store']);
        Route::post('/resources/{resource}/patch', [AdminResourceController::class, 'update']);
    });

    Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin'])->name('admin.')->group(function () {
        Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy']);
        Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy']);
        Route::delete('/nodes/{node}', [AdminNodeController::class, 'destroy']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

        Route::post('/users', [AdminUserController::class, 'store'])->name("admin.users.store");
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name("admin.users.update");
    });


    Route::get('/about-us', [AboutUsController::class, 'index']);

    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


    Route::get('/', [SubjectController::class, 'index'])->name('index');
    Route::get('/resources/{resource:id}', [ResourceController::class, 'show']);
    Route::get('/{subject:slug}', [SubjectController::class, 'show']);
    Route::get('/{subject:slug}/{path}', [NodeController::class, 'show'])->where('path', '.*');
