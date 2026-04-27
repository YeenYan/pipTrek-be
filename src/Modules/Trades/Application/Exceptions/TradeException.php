<?php

namespace Src\Modules\Trades\Application\Exceptions;

use GraphQL\Error\ClientAware;

class TradeException extends \Exception implements ClientAware
{
 public function isClientSafe(): bool
 {
   return true;
 }

 public function getCategory(): string
 {
   return 'trade';
 }
}