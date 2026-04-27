<?php

namespace Src\Modules\Trades;

use Illuminate\Support\ServiceProvider;

class TradesServiceProvider extends ServiceProvider
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