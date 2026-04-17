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
        // Cascade is enforced by the FK constraint on account_securities,
        // so pivot rows are removed automatically by the database.
        $account->delete();
    }

    /**
     * Attach securities to a newly created account.
     *
     * Called once at creation time. Skips the call entirely when the list
     * is empty to avoid an unnecessary database round-trip.
     *
     * @param  string[]  $securityIds  Array of Security UUIDs to attach.
     */
    public function attachSecurities(Account $account, array $securityIds): void
    {
        if (empty($securityIds)) {
            return;
        }

        // attach() inserts pivot rows; the unique DB constraint prevents duplicates
        $account->securities()->attach($securityIds);
    }

    /**
     * Sync securities on an existing account.
     *
     * Adds newly selected securities, removes unselected ones, and leaves
     * existing ones untouched — all in a single query via Eloquent's sync().
     * Passing an empty array clears all related securities for the account.
     *
     * @param  string[]  $securityIds  The complete desired set of Security UUIDs.
     */
    public function syncSecurities(Account $account, array $securityIds): void
    {
        // sync() diffs the current pivot rows against $securityIds:
        //   - inserts rows for new IDs
        //   - deletes rows for removed IDs
        //   - leaves unchanged rows intact
        $account->securities()->sync($securityIds);
    }
}
