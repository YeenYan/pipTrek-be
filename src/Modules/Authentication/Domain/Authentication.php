<?php

namespace Src\Modules\Authentication\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class Authentication extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, Notifiable, HasRoles;

    protected $table = 'users';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_enabled',
        'otp_code',
        'otp_expires_at',
        'is_first_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'otp_expires_at' => 'datetime',
            'is_first_login' => 'boolean',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'roles' => $this->getRoleNames()->toArray(),
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }
}