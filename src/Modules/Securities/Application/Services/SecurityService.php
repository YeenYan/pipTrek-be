<?php

namespace Src\Modules\Securities\Application\Services;

use Src\Modules\Securities\Application\Exceptions\SecurityException;
use Src\Modules\Securities\Infrastructure\Repositories\SecurityRepository;

class SecurityService
{

    public function __construct(private readonly SecurityRepository $repository)
    {}

    public function createSecurity(array $data): array
    {
       $security = $this->repository->createSecurity($data);

        return [
         'security' => $security,
         'message' => 'Security created successfully.',
        ];
    }

    public function getAllSecurities(): array 
    {
        return $this->repository->findAllSecurities();
    }

    public function getSecurity(string $id)
    {
        $security = $this->repository->findSecurityById($id);

        if (!$security) {
            throw new SecurityException('Security not found.');
        }

        return $security;
    }

    public function updateSecurity(string $id, array $data): array
    {
        $security = $this->repository->findSecurityById($id);

        if (!$security) {
            throw new SecurityException('Security not found.');
        }

        $updatedSecurity = $this->repository->updateSecurity($security, $data);

        return [
            'security' => $updatedSecurity,
            'message' => 'Security updated successfully.',
        ];
    }

    public function deleteSecurity(string $id): array
    {
        $security = $this->repository->findSecurityById($id);

        if (!$security) {
            throw new SecurityException('Security not found.');
        }

        $this->repository->deleteSecurity($security);

        return [
            'message' => 'Security deleted successfully.',
        ];
    }
}