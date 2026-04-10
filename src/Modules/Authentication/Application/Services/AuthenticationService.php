<?php

namespace Src\Modules\Authentication\Application\Services;

use Src\Modules\Authentication\Domain\Authentication;
use Src\Modules\Authentication\Infrastructure\Repositories\AuthenticationRepository;
use App\Mail\OtpMail;
use App\Mail\PasswordResetMail;
use App\Mail\TempPasswordMail; 
use Src\Modules\Authentication\Application\Exceptions\AuthenticationException;
use Src\Modules\Authentication\Application\Exceptions\BusinessLogicException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthenticationService
{
    public function __construct(
        private readonly AuthenticationRepository $repository
    ) {}

    /**
     * Register a new user (admin-only).
     * Generates a temporary password and emails it to the user.
     */
    public function registerUser(array $data): array
    {
        $admin = $this->getAuthenticatedUser();

        if (!$admin->hasRole('admin')) {
            throw new AuthenticationException('Unauthorized. Only administrators can create new users.');
        }

        $tempPassword = Str::random(16);

        $user = $this->repository->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $tempPassword,
            'two_factor_enabled' => $data['two_factor_enabled'] ?? false,
            'is_first_login' => true,
        ]);

        $user->assignRole('user');

        Mail::to($user->email)->send(new TempPasswordMail($tempPassword, $user->name));

        return [
            'user' => $user,
            'message' => 'User created successfully. Temporary password sent to ' . $user->email,
        ];
    }

    /**
     * Authenticate a user with email and password.
     * Checks for first-time login and 2FA.
     */
    public function loginUser(array $data): array
    {
        $user = $this->repository->findUserByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials. Please check your email and password.');
        }

        // First-time login — must change password
        if ($user->is_first_login) {
            $tempToken = JWTAuth::customClaims(['first_login' => true])->fromUser($user);

            return [
                'user' => $user,
                'token' => $tempToken,
                'requires_otp' => false,
                'is_first_login' => true,
                'message' => 'First-time login detected. Please change your password.',
            ];
        }

        // 2FA enabled — send OTP
        if ($user->hasTwoFactorEnabled()) {
            $this->generateOtp($user);

            $tempToken = JWTAuth::customClaims(['otp_pending' => true])->fromUser($user);

            return [
                'user' => $user,
                'token' => $tempToken,
                'requires_otp' => true,
                'is_first_login' => false,
                'message' => 'OTP has been sent to your email. Please verify to complete login.',
            ];
        }

        // Normal login
        $token = $this->issueJwtToken($user);
        $this->repository->updateLastLogin($user);

        return [
            'user' => $user,
            'token' => $token,
            'requires_otp' => false,
            'is_first_login' => false,
            'message' => 'Login successful.',
        ];
    }

    public function generateOtp(Authentication $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->repository->storeOtp($user, $otp);
        Mail::to($user->email)->send(new OtpMail($otp, $user->name));
    }

    public function resendOtp(array $data): array
    {
        $user = $this->repository->findUserByEmail($data['email']);

        if (!$user) {
            throw new BusinessLogicException('User not found.');
        }

        $this->generateOtp($user);

        return ['message' => 'OTP has been resent to your email.'];
    }

    public function verifyOtp(array $data): array
    {
        $user = $this->repository->findUserByEmail($data['email']);

        if (!$user) {
            throw new BusinessLogicException('User not found.');
        }

        if (!$this->repository->verifyOtp($user, $data['otp'])) {
            throw new BusinessLogicException('Invalid or expired OTP. Please request a new one.');
        }

        $this->repository->clearOtp($user);
        $this->repository->updateLastLogin($user);
        $token = $this->issueJwtToken($user);

        return [
            'user' => $user,
            'token' => $token,
            'message' => 'OTP verified successfully. Login complete.',
        ];
    }

    public function forgotPassword(array $data): array
    {
        $user = $this->repository->findUserByEmail($data['email']);

        if (!$user) {
            return ['message' => 'If this email exists, a password reset link has been sent.'];
        }

        $token = $this->repository->createPasswordResetToken($user);
        Mail::to($user->email)->send(new PasswordResetMail($token, $user->name));

        return ['message' => 'If this email exists, a password reset link has been sent.'];
    }

    public function resetPassword(array $data): array
    {
        $resetRecord = $this->repository->findValidPasswordReset($data['token']);

        if (!$resetRecord) {
            throw new BusinessLogicException('Invalid or expired password reset token.');
        }

        $user = $this->repository->findUserById($resetRecord->user_id);

        if (!$user) {
            throw new BusinessLogicException('User not found.');
        }

        $this->repository->updatePassword($user, $data['password']);
        $this->repository->deletePasswordResetTokens($user->id);

        return ['message' => 'Password has been reset successfully.'];
    }

    public function changePassword(array $data): array
    {
        $user = $this->getAuthenticatedUser();

        if (!$user->is_first_login) {
            throw new BusinessLogicException('Password change is only required for first-time login users.');
        }

        $this->repository->updatePassword($user, $data['password']);
        $this->repository->updateFirstLoginFlag($user, false);
        $this->repository->updateLastLogin($user);
        $token = $this->issueJwtToken($user);

        return [
            'user' => $user,
            'token' => $token,
            'message' => 'Password changed successfully. Welcome!',
        ];
    }

    public function issueJwtToken(Authentication $user): string
    {
        try {
            $token = JWTAuth::fromUser($user);

            if (!$token) {
                throw new AuthenticationException('Failed to create JWT token.');
            }

            return $token;
        } catch (JWTException $e) {
            throw new AuthenticationException('Could not create token: ' . $e->getMessage());
        }
    }

    public function refreshJwtToken(): string
    {
        try {
            return JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            throw new AuthenticationException('Could not refresh token: ' . $e->getMessage());
        }
    }

    public function logout(): void
    {
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (JWTException $e) {
            throw new AuthenticationException('Could not invalidate token: ' . $e->getMessage());
        }
    }

    public function getAuthenticatedUser(): Authentication
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                throw new AuthenticationException('User not found.');
            }

            return $user;
        } catch (JWTException $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage());
        }
    }
}