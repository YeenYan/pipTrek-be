<?php

namespace Src\Modules\Authentication\Application\Services;

use Src\Modules\Authentication\Infrastructure\Repositories\AuthenticationRepository;
use Illuminate\Support\Facades\Log;

class UserRegistrationRequestService
{
    public function __construct(
        private readonly AuthenticationRepository $repository
    ) {}

    /**
     * Mark a pending registration request as 'created' and link the new user's ID.
     * This is called AFTER an admin successfully creates a user account.
     * Wrapped in try/catch so any failure here never interrupts the user creation flow.
     */
    public function markAsCreatedByEmail(string $email, string $userId): void
    {
        try {
            $this->repository->markRegistrationRequestAsCreated($email, $userId);
        } catch (\Throwable $e) {
            Log::warning('Failed to update registration request status.', [
                'email' => $email,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all user registration requests.
     * Optionally filter by status ('pending' or 'created').
     *
     * @param  string|null  $status  Filter by status. Pass null to return all.
     * @return array Array of registration request objects.
     */
    public function getAllRequests(?string $status = null): array
    {
        return $this->repository->getAllRegistrationRequests($status);
    }
}
