<?php

namespace Src\Modules\Pairs\GraphQL\Resolvers;

use Src\Modules\Pairs\Application\Services\PairService;

class PairResolver
{
    public function __construct(private readonly PairService $pairService) {}

    public function pairs($_, array $args): array
    {
        return $this->pairService->getAllPairs();
    }

    public function pair($_, array $args)
    {
        return $this->pairService->getPair($args['id']);
    }

    public function createPair($_, array $args): array
    {
        return $this->pairService->createPair($args['input'] ?? $args);
    }

    public function updatePair($_, array $args): array
    {
        return $this->pairService->updatePair($args['id'], $args['input'] ?? $args);
    }

    public function deletePair($_, array $args): array
    {
        return $this->pairService->deletePair($args['id']);
    }
}