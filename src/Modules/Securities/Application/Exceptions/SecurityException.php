<?php

namespace Src\Modules\Securities\Application\Exceptions;

use GraphQL\Error\ClientAware;

class SecurityException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'security';
    }
}