<?php

namespace Src\Modules\Pairs;

use Illuminate\Support\ServiceProvider;

class PairsServiceProvider extends ServiceProvider
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