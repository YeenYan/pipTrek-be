<?php

namespace Src\Modules\Accounts\Infrastructure\Repositories;

use Src\Modules\Accounts\Domain\AccountGoal;

class AccountGoalsRepository
{
    public function createAccountGoal(array $data): AccountGoal
    {
        return AccountGoal::create($data);
    }

    public function findAccountGoalById(string $id): ?AccountGoal
    {
        return AccountGoal::find($id);
    }

    public function findAccountGoalsByAccountId(string $accountId): array
    {
        return AccountGoal::where('account_id', $accountId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->all();
    }

    public function updateAccountGoals(AccountGoal $accountGoals, array $data): AccountGoal
    {
        $accountGoals->update($data);
        return $accountGoals->fresh();
    }

    public function deleteAccountGoals(AccountGoal $accountGoals): void
    {
        $accountGoals->delete();
    }

}