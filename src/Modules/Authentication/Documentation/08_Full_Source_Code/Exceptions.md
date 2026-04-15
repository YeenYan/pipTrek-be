# Full Source Code — Exceptions

## `src/Modules/Authentication/Application/Exceptions/AuthenticationException.php`

```php
<?php

namespace Src\Modules\Authentication\Application\Exceptions;

use GraphQL\Error\ClientAware;

class AuthenticationException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'authentication';
    }
}
```

---

## `src/Modules/Authentication/Application/Exceptions/BusinessLogicException.php`

```php
<?php

namespace Src\Modules\Authentication\Application\Exceptions;

use GraphQL\Error\ClientAware;

class BusinessLogicException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'business';
    }
}
```
