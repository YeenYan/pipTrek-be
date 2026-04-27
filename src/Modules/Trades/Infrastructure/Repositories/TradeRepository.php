<?php

namespace Src\Modules\Trades\Infrastructure\Repositories;

use Src\Modules\Trades\Domain\Trade;

class TradeRepository
{

 public function createTrade(array $data): Trade
 {
    return Trade::create($data);
 }

 public function findAllTrades(): array
 {
    return Trade::all()->all();
 }

 public function findTradeById(string $id): ?Trade
 {
    return Trade::find($id);
 }

 public function updateTrade(Trade $trade, array $data): Trade
 {
    $trade->update($data);
    return $trade->fresh();
 }

 public function deleteTrade(Trade $trade): void
 {
    $trade->delete();
 }

}