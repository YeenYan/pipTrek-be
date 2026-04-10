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