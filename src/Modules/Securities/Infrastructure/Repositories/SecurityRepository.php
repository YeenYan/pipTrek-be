<?php

namespace Src\Modules\Securities\Infrastructure\Repositories;

use Src\Modules\Securities\Domain\Security;

class SecurityRepository
{
    public function createSecurity(array $data)
    {
        return Security::create($data);
    }

    public function findAllSecurities(): array
    {
        return Security::all()->all();
    }

    public function findSecurityById(string $id): ?Security
    {
        return Security::find($id);
    }

    public function updateSecurity(Security $security, array $data): Security
    {
        $security->update($data);
        return $security->fresh();
    }

    public function deleteSecurity(Security $security): void
    {
        $security->delete();
    }

    public function findSecuritiesByAccountId(string $accountId): array
    {
        return Security::whereHas('accounts', function ($query) use ($accountId) {
            $query->where('account_id', $accountId);
        })->get()->all();
    }
}