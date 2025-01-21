<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::ignoreRoutes();
        // Passport::routes();

        // 啟用 password grant
        Passport::enableImplicitGrant();
        Passport::enablePasswordGrant();
    }
}