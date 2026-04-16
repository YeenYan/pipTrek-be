<?php

namespace Src\Modules\Authentication\GraphQL\Resolvers;

use Src\Modules\Authentication\Application\Services\AuthenticationService;
use Src\Modules\Authentication\Application\Services\UserRegistrationRequestService;

class AuthResolver
{
    public function __construct(
        private readonly AuthenticationService $authService,
        private readonly UserRegistrationRequestService $registrationRequestService
    ) {}

    public function login($_, array $args): array
    {
        return $this->authService->loginUser($args['input'] ?? $args);
    }

    public function register($_, array $args): array
    {
        return $this->authService->registerUser($args['input'] ?? $args);
    }

    public function verifyOtp($_, array $args): array
    {
        return $this->authService->verifyOtp($args['input'] ?? $args);
    }

    public function resendOtp($_, array $args): array
    {
        return $this->authService->resendOtp($args['input'] ?? $args);
    }

    public function forgotPassword($_, array $args): array
    {
        return $this->authService->forgotPassword($args['input'] ?? $args);
    }

    public function resetPassword($_, array $args): array
    {
        return $this->authService->resetPassword($args['input'] ?? $args);
    }

    public function refreshToken($_, array $args): array
    {
        $token = $this->authService->refreshJwtToken();
        return ['token' => $token, 'message' => 'Token refreshed successfully.'];
    }

    public function logout($_, array $args): array
    {
        $this->authService->logout();
        return ['message' => 'Successfully logged out.'];
    }

    public function me($_, array $args): \Src\Modules\Authentication\Domain\Authentication
    {
        return $this->authService->getAuthenticatedUser();
    }

    public function changePassword($_, array $args): array
    {
        return $this->authService->changePassword($args['input'] ?? $args);
    }

    public function requestUserRegistration($_, array $args): array
    {
        return $this->authService->requestUserRegistration($args['input'] ?? $args);
    }

    public function userRegistrationRequests($_, array $args): array
    {
        $status = $args['status'] ?? null;
        return $this->registrationRequestService->getAllRequests($status);
    }
}