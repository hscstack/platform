<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Chapter;
use App\Models\Product;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\ChapterObserver;
use App\Observers\ProductObserver;
use App\Observers\ResourceObserver;
use App\Observers\SubjectObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Http\Discovery\Psr17Factory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\WebPushChannel;

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
        Chapter::observe(ChapterObserver::class);
        Product::observe(ProductObserver::class);
        Resource::observe(ResourceObserver::class);
        Subject::observe(SubjectObserver::class);
        User::observe(UserObserver::class);

        DatabaseNotification::saved(function (DatabaseNotification $notification) {
            if ($notification->notifiable_type === User::class && $notification->notifiable_id && is_null($notification->read_at)) {
                Cache::forever("user:{$notification->notifiable_id}:has_unread_notifs", true);
            }
        });

        // Pass Laravel Logger to WebPush so notices/warnings are logged gracefully rather than triggering E_USER_NOTICE
        $this->app->when(WebPushChannel::class)
            ->needs(WebPush::class)
            ->give(function ($app) {
                $config = config('webpush');
                $auth = [];
                $publicKey = $config['vapid']['public_key'] ?? null;
                $privateKey = $config['vapid']['private_key'] ?? null;

                if (! empty($publicKey) && ! empty($privateKey)) {
                    $auth['VAPID'] = [
                        'subject' => $config['vapid']['subject'] ?: url('/'),
                        'publicKey' => $publicKey,
                        'privateKey' => $privateKey,
                    ];
                }

                $httpFactory = new Psr17Factory;

                return (new WebPush(
                    $auth,
                    [],
                    null,
                    $httpFactory,
                    $httpFactory,
                    null,
                    Log::channel('single')
                ))->setReuseVAPIDHeaders(true)
                    ->setAutomaticPadding($config['automatic_padding'] ?? true);
            });
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
