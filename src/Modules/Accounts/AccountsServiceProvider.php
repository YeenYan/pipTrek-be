<?php

namespace Src\Modules\Accounts;

use Src\Modules\Accounts\Application\Services\AccountService;
use Src\Modules\Accounts\Infrastructure\Repositories\AccountRepository;
use Src\Modules\Authentication\Application\Services\AuthenticationService;
use Illuminate\Support\ServiceProvider;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountRepository::class, function ($app) {
            return new AccountRepository();
        });

        $this->app->singleton(AccountService::class, function ($app) {
            return new AccountService(
                $app->make(AccountRepository::class),
                $app->make(AuthenticationService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
    }
}