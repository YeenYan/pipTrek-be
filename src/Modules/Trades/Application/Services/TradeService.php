<?php

namespace Src\Modules\Trades\Application\Services;

use Src\Modules\Trades\Application\Exceptions\TradeException;
use Src\Modules\Trades\Infrastructure\Repositories\TradeRepository;

class TradeService
{
 public function __construct(private readonly TradeRepository $repository)
 {
 }

 public function createTrade(array $data): array
 {
    $trade = $this->repository->createTrade($data);

    return [
        'trade' => $trade,
        'message' => 'Trade created successfully.',
    ];
 }

 public function getAllTrades(): array
 {
    return $this->repository->findAllTrades();
 }

 public function getTradeById(string $id)
 {
    return $this->repository->findTradeById($id);
 }

 public function updateTrade(string $id, array $data): array
 {
    $trade = $this->repository->findTradeById($id);

    if (!$trade) {
        throw new TradeException('Trade not found.');
    }

    $updatedTrade = $this->repository->updateTrade($trade, $data);

    return [
        'trade' => $updatedTrade,
        'message' => 'Trade updated successfully.',
    ];
 }

 public function deleteTrade(string $id): array
 {
   $trade = $this->repository->findTradeById($id);

   if (!$trade) {
    throw new TradeException('Trade not found.');
   }

   $this->repository->deleteTrade($trade);

   return [
    'message' => 'Trade deleted successfully.',
   ];
 } 
 
}