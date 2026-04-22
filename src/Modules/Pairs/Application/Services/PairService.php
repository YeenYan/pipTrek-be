<?php

namespace Src\Modules\Pairs\Application\Services;

use Src\Modules\Pairs\Application\Exceptions\PairException;
use Src\Modules\Pairs\Infrastructure\Repositories\PairRepository;


class PairService
{

 public function __construct(private readonly PairRepository $repository)
 {}

 public function createPair(array $data): array
 {
    $pair = $this->repository->createPair($data);

     return [
      'pair' => $pair,
      'message' => 'Pair created successfully.',
     ];
 }

 public function getAllPairs(): array 
 {
     return $this->repository->findAllPairs();
 }

 public function getPair(string $id)
 {
     $pair = $this->repository->findPairById($id);

     if (!$pair) {
         throw new PairException('Pair not found.');
     }

     return $pair;
 }

 public function updatePair(string $id, array $data): array
 {
     $pair = $this->repository->findPairById($id);

     if (!$pair) {
         throw new PairException('Pair not found.');
     }

     $updatedPair = $this->repository->updatePair($pair, $data);

     return [
         'pair' => $updatedPair,
         'message' => 'Pair updated successfully.',
     ];
 }

 public function deletePair(string $id): array
 {
     $pair = $this->repository->findPairById($id);

     if (!$pair) {
         throw new PairException('Pair not found.');
     }

     $this->repository->deletePair($pair);

     return [
         'message' => 'Pair deleted successfully.',
     ];
 }
}