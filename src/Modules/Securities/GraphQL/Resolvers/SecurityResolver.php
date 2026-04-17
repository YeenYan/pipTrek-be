<?php

namespace Src\Modules\Securities\GraphQL\Resolvers;

use Src\Modules\Securities\Application\Services\SecurityService;

class SecurityResolver
{
    public function __construct(private readonly SecurityService $securityService) {}

    public function securities($_, array $args): array
    {
        return $this->securityService->getAllSecurities();
    }

    public function security($_, array $args)
    {
        return $this->securityService->getSecurity($args['id']);
    }

    public function createSecurity($_, array $args): array
    {
        return $this->securityService->createSecurity($args['input'] ?? $args);
    }

    public function updateSecurity($_, array $args): array
    {
        return $this->securityService->updateSecurity($args['id'], $args['input'] ?? $args);
    }

    public function deleteSecurity($_, array $args): array
    {
        return $this->securityService->deleteSecurity($args['id']);
    }

}