<?php

namespace Src\Modules\Tags\Application\Exceptions;

use GraphQL\Error\ClientAware;

class TagException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'tag';
    }
}
{
    
}