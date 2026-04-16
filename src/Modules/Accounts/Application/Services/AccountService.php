<?php

namespace Src\Modules\Accounts\Application\Services;

use Src\Modules\Accounts\Domain\Account;
use Src\Modules\Accounts\Infrastructure\Repositories\AccountRepository;
use Src\Modules\Accounts\Application\Exceptions\AccountException;
use Src\Modules\Authentication\Application\Services\AuthenticationService;

class AccountService
{
    public function __construct(
        private readonly AccountRepository $repository,
        private readonly AuthenticationService $authService
    ) {}

    public function createAccount(array $data): array
    {
        $user = $this->authService->getAuthenticatedUser();

        $data['user_id'] = $user->id;

        $account = $this->repository->createAccount($data);

        return [
            'account' => $account,
            'message' => 'Account created successfully.',
        ];
    }

    public function getMyAccounts(): array
    {
        $user = $this->authService->getAuthenticatedUser();

        return $this->repository->findAccountsByUserId($user->id);
    }

    public function getAccount(string $id): Account
    {
        $account = $this->repository->findAccountById($id);

        if (!$account) {
            throw new AccountException('Account not found.');
        }

        $user = $this->authService->getAuthenticatedUser();

        if ($account->user_id !== $user->id) {
            throw new AccountException('Unauthorized. You do not own this account.');
        }

        return $account;
    }

    public function updateAccount(string $id, array $data): array
    {
        $account = $this->repository->findAccountById($id);

        if (!$account) {
            throw new AccountException('Account not found.');
        }

        $user = $this->authService->getAuthenticatedUser();

        if ($account->user_id !== $user->id) {
            throw new AccountException('Unauthorized. You do not own this account.');
        }

        $account = $this->repository->updateAccount($account, $data);

        return [
            'account' => $account,
            'message' => 'Account updated successfully.',
        ];
    }

    public function deleteAccount(string $id): array
    {
        $account = $this->repository->findAccountById($id);

        if (!$account) {
            throw new AccountException('Account not found.');
        }

        $user = $this->authService->getAuthenticatedUser();

        if ($account->user_id !== $user->id) {
            throw new AccountException('Unauthorized. You do not own this account.');
        }

        $this->repository->deleteAccount($account);

        return [
            'message' => 'Account deleted successfully.',
        ];
    }

    public function toggleAccountActive(string $id): array
    {
        $account = $this->repository->findAccountById($id);

        if (!$account) {
            throw new AccountException('Account not found.');
        }

        $user = $this->authService->getAuthenticatedUser();

        if ($account->user_id !== $user->id) {
            throw new AccountException('Unauthorized. You do not own this account.');
        }

        $account = $this->repository->updateAccount($account, [
            'is_active' => !$account->is_active,
        ]);

        return [
            'account' => $account,
            'message' => $account->is_active
                ? 'Account activated successfully.'
                : 'Account deactivated successfully.',
        ];
    }
}
