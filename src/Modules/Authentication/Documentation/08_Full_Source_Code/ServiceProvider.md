# Full Source Code — Service Provider

## `src/Modules/Authentication/AuthenticationServiceProvider.php`

```php
<?php

namespace Src\Modules\Authentication;

use Src\Modules\Authentication\Application\Services\AuthenticationService;
use Src\Modules\Authentication\Application\Services\UserRegistrationRequestService;
use Src\Modules\Authentication\Infrastructure\Repositories\AuthenticationRepository;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthenticationRepository::class, function ($app) {
            return new AuthenticationRepository();
        });

        $this->app->singleton(UserRegistrationRequestService::class, function ($app) {
            return new UserRegistrationRequestService(
                $app->make(AuthenticationRepository::class)
            );
        });

        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService(
                $app->make(AuthenticationRepository::class),
                $app->make(UserRegistrationRequestService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
    }
}
```
