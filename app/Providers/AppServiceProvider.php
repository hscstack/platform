<?php

namespace App\Providers;

use App\Listeners\LogSentEmailListener;
use App\Models\Blog;
use App\Models\Node;
use App\Models\NodeVote;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\NodeObserver;
use App\Observers\NodeVoteObserver;
use App\Observers\NoticeObserver;
use App\Observers\ResourceObserver;
use App\Observers\SubjectObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        NodeVote::observe(NodeVoteObserver::class);
        Notice::observe(NoticeObserver::class);
        Resource::observe(ResourceObserver::class);
        Subject::observe(SubjectObserver::class);
        User::observe(UserObserver::class);

        Event::listen(MessageSent::class, LogSentEmailListener::class);
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
            fn (): ?Password => app()->isProduction()
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
