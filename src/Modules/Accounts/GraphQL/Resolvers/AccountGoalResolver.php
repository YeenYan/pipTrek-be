<?php

namespace Src\Modules\Accounts\GraphQL\Resolvers;

use Src\Modules\Accounts\Application\Services\AccountGoalService;


class AccountGoalResolver
{
    public function __construct(
        private readonly AccountGoalService $accountGoalService
    ) {}

    public function accountGoals($_, array $args): array
    {
        return $this->accountGoalService->getAccountGoalsByAccountId($args['account_id']);
    }

    public function accountGoal($_, array $args)
    {
        return $this->accountGoalService->findAccountGoalById($args['id']);
    }

    public function createAccountGoal($_, array $args): array
    {
        return $this->accountGoalService->createAccountGoal($args['input'] ?? $args);
    }

    public function updateAccountGoal($_, array $args): array
    {
        return $this->accountGoalService->updateAccountGoal($args['id'], $args['input'] ?? $args);
    }

    public function deleteAccountGoal($_, array $args): array
    {
        return $this->accountGoalService->deleteAccountGoal($args['id']);
    }
}