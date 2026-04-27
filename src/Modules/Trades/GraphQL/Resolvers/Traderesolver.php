<?php

namespace Src\Modules\Trades\GraphQL\Resolvers;

use Src\Modules\Trades\Application\Services\TradeService;

class TradeResolver
{
 public function __construct(private readonly TradeService $tradeService) {}

 public function createTrade($_, array $args): array
 {
    return $this->tradeService->createTrade($args['input'] ?? $args);
 }

 public function updateTrade($_, array $args): array
 {
    return $this->tradeService->updateTrade($args['id'], $args['input'] ?? $args);
 }

 public function deleteTrade($_, array $args): array
 {
    return $this->tradeService->deleteTrade($args['id']);
 }

 public function trades($_, array $args): array
 {
    return $this->tradeService->getAllTrades();
 }

 public function trade($_, array $args)
 {
    return $this->tradeService->getTradeById($args['id']);
 }
}