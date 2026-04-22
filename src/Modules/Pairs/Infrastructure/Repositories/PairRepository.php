<?php

namespace Src\Modules\Pairs\Infrastructure\Repositories;

use Src\Modules\Pairs\Domain\Pair;

class PairRepository
{
    public function createPair(array $data)
    {
        return Pair::create($data);
    }

    public function findAllPairs(): array
    {
        return Pair::all()->all();
    }

    public function findPairById(string $id): ?Pair
    {
        return Pair::find($id);
    }

    public function updatePair(Pair $pair, array $data): Pair
    {
        $pair->update($data);
        return $pair->fresh();
    }

    public function deletePair(Pair $pair): void
    {
        $pair->delete();
    }

} 