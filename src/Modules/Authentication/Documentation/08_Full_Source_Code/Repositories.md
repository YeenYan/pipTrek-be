# Full Source Code — Repositories

## `src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`

```php
<?php

namespace Src\Modules\Authentication\Infrastructure\Repositories;

use Src\Modules\Authentication\Domain\Authentication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthenticationRepository
{
    public function createUser(array $data): Authentication
    {
        return Authentication::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'two_factor_enabled' => $data['two_factor_enabled'] ?? false,
            'is_first_login' => $data['is_first_login'] ?? true,
        ]);
    }

    public function findUserByEmail(string $email): ?Authentication
    {
        return Authentication::where('email', $email)->first();
    }

    public function findUserById(string $uuid): ?Authentication
    {
        return Authentication::find($uuid);
    }

    public function storeOtp(Authentication $user, string $otp): void
    {
        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);
    }

    public function verifyOtp(Authentication $user, string $otp): bool
    {
        if (!$user->otp_code || !$user->otp_expires_at) {
            return false;
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return false;
        }

        return Hash::check($otp, $user->otp_code);
    }

    public function clearOtp(Authentication $user): void
    {
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }

    public function updateLastLogin(Authentication $user): void
    {
        $user->update([
            'updated_at' => Carbon::now(),
        ]);
    }

    public function createPasswordResetToken(Authentication $user): string
    {
        DB::table('password_resets')->where('user_id', $user->id)->delete();

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => Carbon::now()->addHour(),
            'created_at' => Carbon::now(),
        ]);

        return $token;
    }

    public function findValidPasswordReset(string $token): ?object
    {
        $resets = DB::table('password_resets')
            ->where('expires_at', '>', Carbon::now())
            ->get();

        foreach ($resets as $reset) {
            if (Hash::check($token, $reset->token)) {
                return $reset;
            }
        }

        return null;
    }

    public function updatePassword(Authentication $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function updateFirstLoginFlag(Authentication $user, bool $isFirstLogin): void
    {
        $user->update(['is_first_login' => $isFirstLogin]);
    }

    public function deletePasswordResetTokens(string $userId): void
    {
        DB::table('password_resets')->where('user_id', $userId)->delete();
    }

    public function findPendingRegistrationRequestByEmail(string $email): ?object
    {
        return DB::table('user_registration_requests')
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();
    }

    public function createRegistrationRequest(array $data): object
    {
        $id = Str::uuid()->toString();

        DB::table('user_registration_requests')->insert([
            'id' => $id,
            'username' => $data['username'],
            'email' => $data['email'],
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return DB::table('user_registration_requests')->where('id', $id)->first();
    }

    public function markRegistrationRequestAsCreated(string $email, string $userId): bool
    {
        $affected = DB::table('user_registration_requests')
            ->where('email', $email)
            ->where('status', 'pending')
            ->update([
                'status' => 'created',
                'user_id' => $userId,
                'updated_at' => Carbon::now(),
            ]);

        return $affected > 0;
    }

    /**
     * Get all registration requests ordered by newest first.
     * Optionally filter by status ('pending' or 'created').
     *
     * @param  string|null  $status
     * @return array Array of stdClass objects.
     */
    public function getAllRegistrationRequests(?string $status = null): array
    {
        $query = DB::table('user_registration_requests')
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get()->all();
    }
}
```
