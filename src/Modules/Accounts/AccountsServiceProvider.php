<?php

namespace Src\Modules\Accounts;

use Illuminate\Support\ServiceProvider;
use Src\Modules\Accounts\Application\Services\AccountService;
use Src\Modules\Accounts\Infrastructure\Repositories\AccountGoalsRepository;
use Src\Modules\Accounts\Infrastructure\Repositories\AccountRepository;
use Src\Modules\Authentication\Application\Services\AuthenticationService;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountRepository::class, function ($app) {
            return new AccountRepository();
        });

        $this->app->singleton(AccountGoalsRepository::class, function ($app) {
            return new AccountGoalsRepository();
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