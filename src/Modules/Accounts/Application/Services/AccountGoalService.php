<?php

namespace Src\Modules\Accounts\Application\Services;

use Src\Modules\Accounts\Domain\AccountGoal;
use Src\Modules\Accounts\Infrastructure\Repositories\AccountGoalsRepository;

class AccountGoalService
{
    public function __construct(
        private readonly AccountGoalsRepository $repository,
       private readonly AccountService $accountService
    ) {}

    public function createAccountGoal(array $data): array
    {
      $account = $this->accountService->getAccount($data['account_id']);

      if (!$account) {
       throw new \Exception('Account not found.');
      }

      $data['end_date'] = empty($data['end_date']) ? null : $data['end_date'];

      $accountGoal = $this->repository->createAccountGoal([
        'account_id' => $account->id,
        ...$data
      ]);

      return [
       'accountGoal' => $accountGoal,
       'message' => 'Account goal created successfully.',
      ];
    }

    public function findAccountGoalById(string $id): AccountGoal
    {
        $accountGoal = $this->repository->findAccountGoalById($id);

        if (!$accountGoal) {
            throw new \Exception('Account goal not found.');
        }

        return $accountGoal;
    }

    public function getAccountGoalsByAccountId(string $accountId): array
    {
        return $this->repository->findAccountGoalsByAccountId($accountId);
    }

    public function updateAccountGoal(string $id, array $data): array
    {
        $accountGoal = $this->findAccountGoalById($id);

        $updatedAccountGoal = $this->repository->updateAccountGoals($accountGoal, $data);

        return [
            'accountGoal' => $updatedAccountGoal,
            'message' => 'Account goal updated successfully.',
        ];
    }

    public function deleteAccountGoal(string $id): array
    {
        $accountGoal = $this->findAccountGoalById($id);

        $this->repository->deleteAccountGoals($accountGoal);

        return [
            'message' => 'Account goal deleted successfully.',
        ];
    }
}