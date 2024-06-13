<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Channels\FCMChannel;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessClient;
use Mockery\Generator\StringManipulation\Pass\Pass;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::tokensExpireIn(now()->addHours(1));
        Passport::refreshTokensExpireIn(now()->addHours(2));
        Passport::personalAccessTokensExpireIn(now()->addDay());
    }

    /**
     * Register custom chanel.
     *
     * @return void
     */
    public function register()
    {
        // Notification::extend('fcm', function ($app) {
        //     return new FCMChannel();
        // });
    }
}
