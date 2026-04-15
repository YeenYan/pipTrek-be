# Full Source Code — External Configuration

## `config/auth.php`

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => Src\Modules\Authentication\Domain\Authentication::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
```

---

## `bootstrap/providers.php`

```php
<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
];
```

---

## `app/GraphQL/ErrorHandlers/SanitizedValidationErrorHandler.php`

```php
<?php

namespace App\GraphQL\ErrorHandlers;

use Closure;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;

/**
 * Cleans up GraphQL error responses for frontend consumption.
 *
 * Handles:
 * - Lighthouse ValidationException: strips 'input.' prefix, returns field-level errors
 * - ClientAware exceptions: returns the message with the exception's category
 * - Returns clean responses without file paths, line numbers, or stack traces
 * - Short-circuits the error pipeline (bypasses the debug formatter)
 */
class SanitizedValidationErrorHandler implements ErrorHandler
{
    public function __invoke(?Error $error, Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $previous = $error->getPrevious();

        // Handle Lighthouse validation errors (field-level)
        if ($previous instanceof ValidationException) {
            $extensions = $previous->getExtensions();
            $validation = $extensions['validation'] ?? [];

            $cleaned = [];
            foreach ($validation as $field => $messages) {
                $cleanField = preg_replace('/^input\./', '', $field);
                $cleanMessages = array_map(
                    fn (string $msg) => str_replace('input.', '', $msg),
                    $messages,
                );
                $cleaned[$cleanField] = $cleanMessages;
            }

            return [
                'message' => 'Validation failed.',
                'extensions' => [
                    'category' => 'validation',
                    'validation' => $cleaned,
                ],
            ];
        }

        // Handle ClientAware exceptions (authentication, business logic)
        if ($previous instanceof ClientAware && $previous->isClientSafe()) {

            $category = method_exists($previous, 'getCategory')
                ? $previous->getCategory()
                : 'authentication';

            return [
                'message' => $previous->getMessage(),
                'extensions' => [
                    'category' => $category,
                ],
        ];
}

        return $next($error);
    }
}
```
