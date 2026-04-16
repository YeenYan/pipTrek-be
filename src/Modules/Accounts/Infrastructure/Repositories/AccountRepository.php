<?php

namespace Src\Modules\Accounts\Infrastructure\Repositories;

use Src\Modules\Accounts\Domain\Account;

class AccountRepository
{
    public function createAccount(array $data): Account
    {
        return Account::create($data);
    }

    public function findAccountById(string $id): ?Account
    {
        return Account::find($id);
    }

    public function findAccountsByUserId(string $userId): array
    {
        return Account::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findActiveAccountsByUserId(string $userId): array
    {
        return Account::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function deleteAccount(Account $account): void
    {
        $account->delete();
    }
}
