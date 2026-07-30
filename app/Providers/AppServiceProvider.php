<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Models\Blog;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\NodeObserver;
use App\Observers\NoticeObserver;
use App\Observers\ResourceObserver;
use App\Observers\SubjectObserver;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Blog::observe(BlogObserver::class);
        Node::observe(NodeObserver::class);
        Notice::observe(NoticeObserver::class);
        Resource::observe(ResourceObserver::class);
        Subject::observe(SubjectObserver::class);
        User::observe(UserObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
                : null,
        );
    }
}
