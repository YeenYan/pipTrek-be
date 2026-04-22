<?php

namespace Src\Modules\Pairs\Application\Exceptions;

use GraphQL\Error\ClientAware;

class PairException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'pair';
    }
}