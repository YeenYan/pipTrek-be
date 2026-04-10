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