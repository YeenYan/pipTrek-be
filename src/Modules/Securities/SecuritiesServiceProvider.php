<?php

namespace Src\Modules\Securities;

use Illuminate\Support\ServiceProvider;

class SecuritiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
    }
}