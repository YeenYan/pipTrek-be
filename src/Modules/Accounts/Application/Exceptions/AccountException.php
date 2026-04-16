<?php

namespace Src\Modules\Accounts\Application\Exceptions;

use GraphQL\Error\ClientAware;

class AccountException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'account';
    }
}
