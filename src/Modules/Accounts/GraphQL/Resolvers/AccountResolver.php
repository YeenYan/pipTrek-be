<?php

namespace Src\Modules\Accounts\GraphQL\Resolvers;

use Src\Modules\Accounts\Application\Services\AccountService;
use Src\Modules\Accounts\Domain\Account;

class AccountResolver
{
    public function __construct(
        private readonly AccountService $accountService
    ) {}

    public function myAccounts($_, array $args): array
    {
        return $this->accountService->getMyAccounts();
    }

    public function account($_, array $args): Account
    {
        return $this->accountService->getAccount($args['id']);
    }

    public function createAccount($_, array $args): array
    {
        return $this->accountService->createAccount($args['input'] ?? $args);
    }

    public function updateAccount($_, array $args): array
    {
        return $this->accountService->updateAccount($args['id'], $args['input'] ?? $args);
    }

    public function deleteAccount($_, array $args): array
    {
        return $this->accountService->deleteAccount($args['id']);
    }

    public function toggleAccountActive($_, array $args): array
    {
        return $this->accountService->toggleAccountActive($args['id']);
    }
}
