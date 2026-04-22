<?php

namespace Src\Modules\Tags;

use Illuminate\Support\ServiceProvider;

class TagsServiceProvider extends ServiceProvider
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