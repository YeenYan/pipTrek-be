# Authentication Module — Complete Documentation

A **complete, beginner-friendly guide** to understanding every part of the Authentication module. This document assumes you have **zero prior knowledge** of Laravel, GraphQL, JWT, or backend development.

---

## Table of Contents

1. [Module Overview](#1-module-overview)
2. [Folder & File Structure](#2-folder--file-structure)
3. [Architecture Flow](#3-architecture-flow)
4. [Code Explanation (Beginner Friendly)](#4-code-explanation-beginner-friendly)
5. [Authentication Features](#5-authentication-features)
6. [GraphQL API Documentation](#6-graphql-api-documentation)
7. [GraphQL API & Postman Guide](DOCUMENTATION_GraphQL_API.md) _(separate file)_
8. [Security Explanation](#8-security-explanation)
9. [How to Recreate This Module from Scratch](#9-how-to-recreate-this-module-from-scratch)
10. [Glossary](#10-glossary)
11. [User Registration Request Flow (New Feature)](#11-user-registration-request-flow-new-feature)
12. [Code Walkthrough — User Registration Requests](DOCUMENTATION_PART_2.md#12-code-walkthrough--user-registration-requests) _(Part 2)_

---

## 1. Module Overview

### What Does This Module Do?

The Authentication module is the **"gatekeeper"** of the application. It handles everything related to:

- **Who are you?** (Login — proving your identity)
- **Can you come in?** (Token validation — checking your access pass)
- **What can you do?** (Roles — admin vs. regular user)

Think of it like the security desk at an office building:

1. You show your ID (email + password) → the guard checks it → you get a **visitor pass** (JWT token).
2. Every time you want to enter a room (access data), you show your visitor pass.
3. Your pass has an expiration time. After it expires, you need a new one.

### Why Does This Module Exist?

In any application that has user accounts, you need a system to:

- **Create accounts** (registration)
- **Verify identity** (login)
- **Protect data** (only logged-in users can access certain features)
- **Reset forgotten passwords**
- **Add extra security** (two-factor authentication / 2FA)

Without this module, anyone could access any data — there would be no security at all.

### High-Level Authentication Flow (Simple Version)

```
1. Admin creates a user account
   → User receives a temporary password via email

2. User logs in with email + temporary password
   → System says: "Change your password first!"
   → User sets a new password
   → User gets a JWT token (access pass)

3. User makes requests to the API
   → Includes the JWT token in every request
   → System checks the token → allows or denies access

4. Token expires after 60 minutes
   → User can refresh the token for a new one
   → Or log in again
```

---

## 2. Folder & File Structure

Here is every folder and file inside the Authentication module, with an explanation of what each one does.

```
src/Modules/Authentication/
│
├── AuthenticationServiceProvider.php      ← Registers the module with Laravel
│
├── Domain/
│   └── Authentication.php                 ← The User model (defines what a "user" looks like)
│
├── Application/
│   ├── Services/
│   │   └── AuthenticationService.php      ← ALL business logic (login, register, OTP, etc.)
│   └── Exceptions/
│       ├── AuthenticationException.php    ← Error for auth failures (bad password, invalid token)
│       └── BusinessLogicException.php     ← Error for business rule violations (expired OTP, etc.)
│
├── Infrastructure/
│   ├── Repositories/
│   │   └── AuthenticationRepository.php   ← ALL database operations (find user, save OTP, etc.)
│   └── Database/
│       └── migrations/
│           └── 2026_04_15_000001_create_user_registration_requests_table.php ← Migration for registration requests
│
└── GraphQL/
    ├── Resolvers/
    │   └── AuthResolver.php               ← Receives GraphQL requests and calls the service
    ├── Scalars/
    │   └── JSON.php                        ← Custom data type for flexible JSON responses
    ├── inputs.graphql                      ← Defines the shape of input data (login fields, etc.)
    ├── types.graphql                       ← Defines the shape of output data (user fields, etc.)
    ├── queries.graphql                     ← Defines "read" operations (get current user)
    └── mutations.graphql                   ← Defines "write" operations (login, register, etc.)
```

> **Note:** The `Infrastructure/Database/migrations/` folder is currently empty because database table modifications for users (like `two_factor_enabled`, `otp_code`, etc.) were added directly in the main `database/migrations/` folder. In a fully modular setup, module-specific migrations would go here.

### Related Files Outside the Module

These files live outside the module but are used by it:

| File                                  | Location                     | Purpose                                                   |
| ------------------------------------- | ---------------------------- | --------------------------------------------------------- |
| `OtpMail.php`                         | `app/Mail/`                  | Email template logic for OTP codes                        |
| `TempPasswordMail.php`                | `app/Mail/`                  | Email template logic for temporary passwords              |
| `PasswordResetMail.php`               | `app/Mail/`                  | Email template logic for password reset tokens            |
| `RegistrationRequestMail.php`         | `app/Mail/`                  | Email template logic for admin registration notifications |
| `otp.blade.php`                       | `resources/views/emails/`    | HTML template for OTP email                               |
| `temp-password.blade.php`             | `resources/views/emails/`    | HTML template for temporary password email                |
| `password-reset.blade.php`            | `resources/views/emails/`    | HTML template for password reset email                    |
| `registration-request.blade.php`      | `resources/views/emails/`    | HTML template for admin registration notification email   |
| `SanitizedValidationErrorHandler.php` | `app/GraphQL/ErrorHandlers/` | Cleans up error messages for the frontend                 |
| `schema.graphql`                      | `graphql/`                   | Root GraphQL schema that imports module schemas           |
| `auth.php`                            | `config/`                    | Configures JWT as the authentication driver               |
| `providers.php`                       | `bootstrap/`                 | Registers `AuthenticationServiceProvider`                 |

### How the Files Connect to Each Other

```
                    bootstrap/providers.php
                           │
                           ▼
              AuthenticationServiceProvider.php
              (registers Repository & Service)
                     │            │
                     ▼            ▼
    AuthenticationRepository   AuthenticationService
            │                       │
            ▼                       ▼
    Authentication.php         AuthResolver.php ◄── GraphQL Schema files
    (Domain Model)             (receives API requests)
            │                       │
            ▼                       ▼
        Database               Client (Postman / Frontend)
```

**In plain English:**

1. When the application starts, `providers.php` tells Laravel to load `AuthenticationServiceProvider`.
2. The provider creates a single instance of `AuthenticationRepository` and `AuthenticationService`.
3. When a GraphQL request arrives, `AuthResolver` receives it and calls the appropriate method on `AuthenticationService`.
4. The service applies business rules and calls `AuthenticationRepository` to talk to the database.
5. The repository uses the `Authentication` model to read/write data in the `users` table.

---

## 3. Architecture Flow

This module uses a **layered architecture** with four layers. Each layer has a specific job, and they communicate in a strict order:

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CLIENT (Postman / Frontend)                  │
│                  Sends a GraphQL request to /graphql                │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   GRAPHQL LAYER (Presentation)                                      │
│   Files: AuthResolver.php, *.graphql schema files                   │
│                                                                     │
│   Job: Receive the request, validate input with @rules directives,  │
│        and pass data to the Service layer.                           │
│   Does NOT contain any business logic.                              │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   APPLICATION LAYER (Business Logic)                                │
│   Files: AuthenticationService.php, Exception files                 │
│                                                                     │
│   Job: Apply ALL business rules.                                    │
│   Examples: Check if user is admin before registering, generate     │
│   temporary passwords, send emails, issue JWT tokens.               │
│   Does NOT talk to the database directly.                           │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   INFRASTRUCTURE LAYER (Data Access)                                │
│   Files: AuthenticationRepository.php                               │
│                                                                     │
│   Job: ALL database operations.                                     │
│   Examples: Find user by email, save OTP code, create reset token.  │
│   Does NOT contain any business rules.                              │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   DOMAIN LAYER (Data Model)                                         │
│   Files: Authentication.php                                         │
│                                                                     │
│   Job: Define what a "user" looks like in the database.             │
│   Maps PHP code to the database table.                              │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
                          ┌──────────────┐
                          │  PostgreSQL   │
                          │   Database    │
                          └──────────────┘
```

### Login Flow (Step by Step)

```
Client                 AuthResolver          AuthenticationService       AuthenticationRepository       Database
  │                        │                         │                           │                        │
  │  POST /graphql         │                         │                           │                        │
  │  mutation login(...)   │                         │                           │                        │
  │───────────────────────>│                         │                           │                        │
  │                        │  loginUser(input)       │                           │                        │
  │                        │────────────────────────>│                           │                        │
  │                        │                         │  findUserByEmail(email)   │                        │
  │                        │                         │─────────────────────────->│  SELECT * FROM users   │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │  user data             │
  │                        │                         │                           │<───────────────────────│
  │                        │                         │  user found               │                        │
  │                        │                         │<─────────────────────────-│                        │
  │                        │                         │                           │                        │
  │                        │                         │  Check password (Hash)    │                        │
  │                        │                         │  Check is_first_login     │                        │
  │                        │                         │  Check 2FA enabled        │                        │
  │                        │                         │  Generate JWT token       │                        │
  │                        │                         │                           │                        │
  │                        │  { token, user, msg }   │                           │                        │
  │                        │<────────────────────────│                           │                        │
  │  { token, user, msg } │                         │                           │                        │
  │<───────────────────────│                         │                           │                        │
```

**What happens during login:**

1. Client sends email + password to the `/graphql` endpoint.
2. `AuthResolver.login()` receives the request and calls `AuthenticationService.loginUser()`.
3. The service asks the repository to find the user by email.
4. The repository queries the database and returns the user (or `null`).
5. The service checks the password using a hash comparison.
6. **If `is_first_login` is `true`:** Returns a temporary token and a message telling the user to change their password.
7. **If 2FA is enabled:** Generates a 6-digit OTP, emails it to the user, and returns a temporary token.
8. **Otherwise:** Generates a full JWT token and returns it with the user data.

### Registration Flow (Step by Step)

```
Admin Client           AuthResolver          AuthenticationService       AuthenticationRepository       Database
  │                        │                         │                           │                        │
  │  mutation register()   │                         │                           │                        │
  │  + JWT token (admin)   │                         │                           │                        │
  │───────────────────────>│                         │                           │                        │
  │                        │                         │                           │                        │
  │                  @guard(with: ["api"])            │                           │                        │
  │                  JWT Middleware checks token      │                           │                        │
  │                        │                         │                           │                        │
  │                        │  registerUser(input)    │                           │                        │
  │                        │────────────────────────>│                           │                        │
  │                        │                         │  getAuthenticatedUser()   │                        │
  │                        │                         │  (verify JWT = admin)     │                        │
  │                        │                         │                           │                        │
  │                        │                         │  Generate temp password   │                        │
  │                        │                         │  (random 16 chars)        │                        │
  │                        │                         │                           │                        │
  │                        │                         │  createUser(data)         │                        │
  │                        │                         │─────────────────────────->│  INSERT INTO users     │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │                        │
  │                        │                         │  assignRole('user')       │                        │
  │                        │                         │  Send email with temp pwd │                        │
  │                        │                         │                           │                        │
  │  { user, message }    │                         │                           │                        │
  │<───────────────────────│                         │                           │                        │
```

**What happens during registration:**

1. An **admin** sends a request with a valid JWT token to create a new user.
2. The `@guard(with: ["api"])` directive in the GraphQL schema checks the JWT token **before** the resolver runs.
3. The service verifies the logged-in user has the `admin` role.
4. A random 16-character temporary password is generated.
5. The new user is created in the database with `is_first_login = true`.
6. The user is assigned the `user` role.
7. The temporary password is emailed to the new user.

### Token Validation Flow

```
Client                 Laravel Middleware     AuthResolver          AuthenticationService
  │                        │                      │                         │
  │  query me { ... }      │                      │                         │
  │  Header: Authorization │                      │                         │
  │  Bearer <JWT token>    │                      │                         │
  │───────────────────────>│                      │                         │
  │                        │                      │                         │
  │                 Decode JWT token               │                         │
  │                 Check signature                │                         │
  │                 Check expiry                   │                         │
  │                 Find user from token           │                         │
  │                        │                      │                         │
  │                 If INVALID → 401 error         │                         │
  │                        │                      │                         │
  │                 If VALID:                       │                         │
  │                        │  me()                │                         │
  │                        │─────────────────────>│  getAuthenticatedUser() │
  │                        │                      │────────────────────────>│
  │                        │                      │  return user object     │
  │                        │                      │<────────────────────────│
  │  { id, name, email }  │                      │                         │
  │<───────────────────────│                      │                         │
```

**What happens during token validation:**

1. Client includes the JWT token in the `Authorization` header.
2. The `@guard(with: ["api"])` directive triggers JWT middleware.
3. The middleware decodes the token, checks the signature and expiry.
4. If invalid → returns a `401 Unauthenticated` error immediately.
5. If valid → the resolver runs and the authenticated user is available.

---

## 4. Code Explanation (Beginner Friendly)

### 4.1 Authentication.php — The User Model (Domain Layer)

**Location:** `Domain/Authentication.php`

**What it does:** This file defines what a "user" looks like in your application. It maps to the `users` table in the database. Think of it as a blueprint — "a user has a name, an email, a password, etc."

```php
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
```

**Line-by-line breakdown:**

| Code                      | What It Means                                                                                                               |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `extends Authenticatable` | This class is a "user" that can log in. Laravel provides built-in login features through this.                              |
| `implements JWTSubject`   | This class can be converted into a JWT token. Required by the JWT library.                                                  |
| `use HasUuids`            | Instead of IDs like 1, 2, 3, this uses UUIDs like `550e8400-e29b-41d4-a716-446655440000`. UUIDs are harder to guess.        |
| `use HasRoles`            | This user can have roles (like "admin" or "user"). Provided by the Spatie Permission package.                               |
| `$table = 'users'`        | This model reads/writes to the `users` database table.                                                                      |
| `$guard_name = 'api'`     | Tells Spatie Permission to use the `api` guard (JWT-based), not web sessions.                                               |
| `$fillable`               | Only these fields can be mass-assigned (security feature to prevent unwanted data injection).                               |
| `$hidden`                 | These fields are **never** included when converting a user to JSON (so passwords don't leak).                               |
| `casts()`                 | Tells Laravel how to convert database values: `'password' => 'hashed'` means passwords are automatically hashed when saved. |
| `getJWTIdentifier()`      | Returns the user's unique ID to embed in the JWT token.                                                                     |
| `getJWTCustomClaims()`    | Adds extra data inside the JWT token — the user's email and roles.                                                          |
| `hasTwoFactorEnabled()`   | Helper method to check if the user has 2FA turned on.                                                                       |

---

### 4.2 AuthenticationService.php — The Brain (Application Layer)

**Location:** `Application/Services/AuthenticationService.php`

**What it does:** This is the **most important file** in the module. It contains ALL the business logic — the rules and decisions that make authentication work. It does **not** talk to the database directly; it uses the repository for that.

Here is each method explained:

#### `registerUser(array $data): array`

**Purpose:** Create a new user account. Only admins can do this.

**Step-by-step logic:**

1. Get the currently logged-in user (from their JWT token).
2. Check if the logged-in user has the `admin` role → if not, throw an error.
3. Generate a random 16-character temporary password.
4. Create the user in the database with `is_first_login = true`.
5. Assign the `user` role to the new account.
6. Send an email to the new user with their temporary password.
7. Return the user data and a success message.

```php
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
```

#### `loginUser(array $data): array`

**Purpose:** Authenticate a user and return a JWT token.

**Step-by-step logic:**

1. Find the user by their email address.
2. Check if the password matches → if not, throw an error.
3. **If this is the user's first login:** Return a temporary token with `first_login = true` claim. The user must change their password before doing anything else.
4. **If 2FA is enabled:** Generate a 6-digit OTP, email it to the user, and return a temporary token with `otp_pending = true` claim. The user must verify the OTP to complete login.
5. **Otherwise:** Generate a full JWT token and return it with the user data.

```php
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
```

#### `changePassword(array $data): array`

**Purpose:** Allow first-time login users to set their own password.

**Step-by-step logic:**

1. Get the currently authenticated user from their JWT token.
2. Verify that `is_first_login` is `true` → if not, throw an error.
3. Update the password in the database.
4. Set `is_first_login` to `false` (so they won't be asked again).
5. Issue a full JWT token and return it.

#### `verifyOtp(array $data): array`

**Purpose:** Complete login for users with 2FA enabled.

**Step-by-step logic:**

1. Find the user by email.
2. Check if the OTP matches and hasn't expired (5-minute window).
3. If invalid → throw an error.
4. Clear the OTP from the database.
5. Issue a full JWT token and return it.

#### `forgotPassword(array $data): array`

**Purpose:** Start the password reset process.

**Step-by-step logic:**

1. Find the user by email.
2. If the email doesn't exist, still return a generic message (security: don't reveal if an email exists).
3. Generate a random 64-character reset token.
4. Store the hashed token in the `password_resets` table with a 1-hour expiry.
5. Email the plain token to the user.

#### `resetPassword(array $data): array`

**Purpose:** Set a new password using the reset token.

**Step-by-step logic:**

1. Look up all non-expired reset records.
2. Hash-compare the provided token against each record.
3. If no match → throw an error.
4. Update the user's password.
5. Delete all reset tokens for that user.

#### `refreshJwtToken(): string`

**Purpose:** Get a new JWT token before the current one expires.

#### `logout(): void`

**Purpose:** Invalidate the current JWT token so it can't be used again.

#### `getAuthenticatedUser(): Authentication`

**Purpose:** Parse the JWT token from the request header and return the user it belongs to.

---

### 4.3 AuthenticationRepository.php — The Database Worker (Infrastructure Layer)

**Location:** `Infrastructure/Repositories/AuthenticationRepository.php`

**What it does:** This file handles **all database operations**. The service never talks to the database directly — it always goes through the repository.

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
}
```

**Method summary:**

| Method                        | What It Does                                                          |
| ----------------------------- | --------------------------------------------------------------------- |
| `createUser()`                | Inserts a new row into the `users` table                              |
| `findUserByEmail()`           | Searches for a user by their email address                            |
| `findUserById()`              | Searches for a user by their UUID                                     |
| `storeOtp()`                  | Saves a hashed OTP code and expiry time to the user's record          |
| `verifyOtp()`                 | Checks if a provided OTP matches the stored one and hasn't expired    |
| `clearOtp()`                  | Removes the OTP code and expiry from the user's record                |
| `updateLastLogin()`           | Updates the `updated_at` timestamp                                    |
| `createPasswordResetToken()`  | Generates a random token, stores it hashed, returns the plain version |
| `findValidPasswordReset()`    | Finds a non-expired password reset record matching the given token    |
| `updatePassword()`            | Updates the user's password                                           |
| `updateFirstLoginFlag()`      | Sets `is_first_login` to `true` or `false`                            |
| `deletePasswordResetTokens()` | Removes all password reset records for a user                         |

---

### 4.4 AuthResolver.php — The Traffic Controller (GraphQL Layer)

**Location:** `GraphQL/Resolvers/AuthResolver.php`

**What it does:** This is the **entry point** for all GraphQL requests related to authentication. It receives the request from the client, extracts the input data, and passes it to the service. It does **not** contain any business logic — it's intentionally thin.

```php
<?php

namespace Src\Modules\Authentication\GraphQL\Resolvers;

use Src\Modules\Authentication\Application\Services\AuthenticationService;

class AuthResolver
{
    public function __construct(
        private readonly AuthenticationService $authService
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

    public function me($_, array $args)
    {
        return $this->authService->getAuthenticatedUser();
    }

    public function changePassword($_, array $args): array
    {
        return $this->authService->changePassword($args['input'] ?? $args);
    }
}
```

**Why is `$_` the first parameter?** In Lighthouse (the GraphQL library), resolvers always receive two arguments: the "root" value (which we don't use, so we name it `$_`) and the `$args` array containing the client's input.

---

### 4.5 AuthenticationServiceProvider.php — The Registrar

**Location:** `AuthenticationServiceProvider.php` (module root)

**What it does:** Tells Laravel about this module's classes so they can be automatically injected where needed.

```php
<?php

namespace Src\Modules\Authentication;

use Src\Modules\Authentication\Application\Services\AuthenticationService;
use Src\Modules\Authentication\Infrastructure\Repositories\AuthenticationRepository;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthenticationRepository::class, function ($app) {
            return new AuthenticationRepository();
        });

        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService(
                $app->make(AuthenticationRepository::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
    }
}
```

**Key concepts:**

- **`singleton`**: Creates only ONE instance of a class for the entire application. Every time someone asks for `AuthenticationService`, they get the same instance.
- **`register()`**: Called when the app starts. Defines HOW to create the service and repository.
- **`boot()`**: Called after all providers are registered. Loads any migrations from the module's own migrations folder.

---

### 4.6 Exception Classes — Error Types

**Location:** `Application/Exceptions/`

These define two types of errors that can occur:

#### AuthenticationException.php

Used when authentication fails (wrong password, invalid token, not logged in).

```php
class AuthenticationException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool { return true; }
    public function getCategory(): string { return 'authentication'; }
}
```

#### BusinessLogicException.php

Used when a business rule is violated (expired OTP, user not found, invalid reset token).

```php
class BusinessLogicException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool { return true; }
    public function getCategory(): string { return 'business'; }
}
```

**Why `ClientAware`?** By implementing `ClientAware`, we tell GraphQL that it's safe to show these error messages to the client. Without this, GraphQL would hide the message and show a generic "Internal server error" instead.

**Why two separate exceptions?** So the frontend can distinguish between "you're not logged in" errors (`authentication` category) and "something went wrong with your request" errors (`business` category) and handle them differently.

---

### 4.7 GraphQL Schema Files

These files define the **API contract** — what operations are available, what input they expect, and what they return.

#### inputs.graphql — What the Client Sends

Defines the shape of input data for each operation:

| Input Type                     | Used By                            | Fields                                                                                     |
| ------------------------------ | ---------------------------------- | ------------------------------------------------------------------------------------------ |
| `LoginInput`                   | `login` mutation                   | `email` (required), `password` (required, min 6 chars)                                     |
| `RegisterInput`                | `register` mutation                | `name` (required), `email` (required, unique), `two_factor_enabled` (optional)             |
| `ChangePasswordInput`          | `changePassword` mutation          | `password` (required, min 8 chars), `password_confirmation` (required)                     |
| `VerifyOtpInput`               | `verifyOtp` mutation               | `email` (required), `otp` (required, exactly 6 chars)                                      |
| `ResendOtpInput`               | `resendOtp` mutation               | `email` (required)                                                                         |
| `ForgotPasswordInput`          | `forgotPassword` mutation          | `email` (required)                                                                         |
| `ResetPasswordInput`           | `resetPassword` mutation           | `token` (required), `password` (required, min 8 chars), `password_confirmation` (required) |
| `RequestUserRegistrationInput` | `requestUserRegistration` mutation | `username` (required, min 3 chars), `email` (required)                                     |

The `@rules` directives provide **server-side validation**. For example, `@rules(apply: ["required", "email"])` means the field must be present and must be a valid email format. If validation fails, GraphQL returns an error **before** the resolver even runs.

#### types.graphql — What the Client Receives

Defines the shape of response data:

| Type                              | Fields                                                                                    |
| --------------------------------- | ----------------------------------------------------------------------------------------- |
| `User`                            | `id`, `name`, `email`, `two_factor_enabled`, `is_first_login`, `created_at`, `updated_at` |
| `RegisterResponse`                | `user` (User), `message`                                                                  |
| `ChangePasswordResponse`          | `user` (User), `token`, `message`                                                         |
| `OtpVerificationResponse`         | `user` (User), `token`, `message`                                                         |
| `RequestUserRegistrationResponse` | `success` (Boolean), `message`                                                            |

Operations that return `JSON` (like `login`, `forgotPassword`, `logout`) can return any shape of data. This is useful when the response varies (e.g., login can return different fields depending on first login vs. 2FA vs. normal login).

#### mutations.graphql — Operations That Change Data

| Mutation                  | Protected?     | Description                              |
| ------------------------- | -------------- | ---------------------------------------- |
| `login`                   | No             | Authenticates a user                     |
| `register`                | Yes (`@guard`) | Creates a new user (admin only)          |
| `changePassword`          | Yes (`@guard`) | First-time password change               |
| `verifyOtp`               | No             | Verifies 2FA OTP code                    |
| `resendOtp`               | No             | Resends OTP to email                     |
| `forgotPassword`          | No             | Requests password reset email            |
| `resetPassword`           | No             | Resets password with token               |
| `refreshToken`            | Yes (`@guard`) | Gets a new JWT token                     |
| `logout`                  | Yes (`@guard`) | Invalidates current token                |
| `requestUserRegistration` | No             | Submits a registration request to admins |

**What does `@guard(with: ["api"])` mean?** It tells Lighthouse to check for a valid JWT token **before** running the resolver. If no valid token is found, the request is rejected with a 401 error. Operations without `@guard` are public — anyone can call them.

#### queries.graphql — Operations That Read Data

| Query | Protected?     | Description                                    |
| ----- | -------------- | ---------------------------------------------- |
| `me`  | Yes (`@guard`) | Returns the currently logged-in user's profile |

---

### 4.8 JSON.php — Custom GraphQL Scalar

**Location:** `GraphQL/Scalars/JSON.php`

**What it does:** GraphQL has built-in types like `String`, `Int`, and `Boolean`. But sometimes you need to return flexible data that doesn't fit a fixed type. The `JSON` scalar allows returning any JSON data.

This is used for operations like `login` where the response shape varies depending on the scenario (first login, 2FA, normal login).

---

## 5. Authentication Features

### 5.1 Login

**What it does:** Verifies a user's email + password and returns a JWT token.

**Three possible outcomes:**

| Scenario             | What Happens                                         | What to Do Next                                       |
| -------------------- | ---------------------------------------------------- | ----------------------------------------------------- |
| **First-time login** | Returns `is_first_login: true` and a temporary token | Call `changePassword` with the temporary token        |
| **2FA enabled**      | Returns `requires_otp: true` and an OTP is emailed   | Call `verifyOtp` with the 6-digit code from the email |
| **Normal login**     | Returns a full JWT token                             | Use the token for all subsequent requests             |

### 5.2 Register (Admin Only)

**What it does:** Creates a new user account. Only users with the `admin` role can do this.

**How it works:**

1. Admin provides: name, email, and optionally enables 2FA.
2. System generates a random 16-character temporary password.
3. The new user receives an email with their temporary password.
4. On first login, the user is forced to change their password.

> **Why can't users register themselves?** This is a design decision — the system uses admin-controlled registration for security.

### 5.3 Logout

**What it does:** Invalidates the current JWT token so it can never be used again, even if it hasn't expired.

**How it works:**

1. Client sends the JWT token in the request header.
2. The server adds the token to a "blacklist."
3. Any future request with that token will be rejected.

### 5.4 Refresh Token

**What it does:** Exchanges a valid (but potentially soon-expiring) JWT token for a brand new one.

**Why is this useful?** JWT tokens expire after 60 minutes (configurable). Instead of forcing the user to log in again, the frontend can silently refresh the token. The refresh window is 14 days (20,160 minutes).

**How it works:**

1. Client sends the current JWT token.
2. Server verifies it's still within the refresh window.
3. Server issues a new token and returns it.
4. The old token is invalidated.

### 5.5 Password Reset

**What it does:** Allows a user who forgot their password to set a new one.

**Two-step process:**

**Step 1 — Request Reset (`forgotPassword`):**

1. User provides their email.
2. Server generates a random 64-character token.
3. Token is hashed and stored in the `password_resets` table (expires in 1 hour).
4. The plain token is emailed to the user.
5. **Security:** The response is always the same whether the email exists or not (prevents email enumeration).

**Step 2 — Reset Password (`resetPassword`):**

1. User provides the token + new password + password confirmation.
2. Server finds the matching reset record and verifies the token.
3. Password is updated and all reset tokens for that user are deleted.

### 5.6 Two-Factor Authentication (2FA / OTP)

**What it does:** Adds an extra layer of security. Even if someone knows your password, they also need access to your email.

**How it works:**

1. During registration, admin can set `two_factor_enabled: true`.
2. When the user logs in, instead of getting a token directly:
    - A 6-digit OTP (One-Time Password) is generated.
    - The OTP is hashed and stored on the user record.
    - The OTP expires after 5 minutes.
    - The plain OTP is emailed to the user.
3. User calls `verifyOtp` with the code from the email.
4. If correct and not expired → full JWT token is issued.
5. If wrong or expired → error is returned. User can call `resendOtp` to get a new code.

### 5.7 First-Time Password Change

**What it does:** Forces newly created users to set their own password before using the system.

**How it works:**

1. Admin creates a user → user gets a temporary password via email.
2. User logs in with the temporary password → system detects `is_first_login = true`.
3. A temporary JWT token is returned (with a `first_login` claim).
4. User calls `changePassword` with the temporary token and their new password.
5. `is_first_login` is set to `false` → user gets a full JWT token.

### 5.8 JWT Middleware Protection

**What it does:** Protects certain GraphQL operations so only logged-in users can access them.

**How it works in the schema:**

```graphql
# This mutation is PROTECTED — requires a valid JWT token
register(input: RegisterInput!): RegisterResponse
    @guard(with: ["api"])

# This mutation is PUBLIC — anyone can call it
login(input: LoginInput!): JSON
```

When `@guard(with: ["api"])` is present:

1. Laravel checks the `Authorization` header for a `Bearer` token.
2. The token is decoded and verified.
3. If valid → request proceeds.
4. If invalid/missing → `401 Unauthenticated` error is returned.

---

## 6. GraphQL API Documentation

All requests are sent as `POST` requests to:

```
http://localhost:8000/graphql
```

### 6.1 Login

**Mutation:**

```graphql
mutation Login($input: LoginInput!) {
    login(input: $input)
}
```

**Variables:**

```json
{
    "input": {
        "email": "user@example.com",
        "password": "password123"
    }
}
```

**Response — Normal Login (no 2FA, not first login):**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "John Doe",
                "email": "user@example.com",
                "two_factor_enabled": false,
                "is_first_login": false
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "requires_otp": false,
            "is_first_login": false,
            "message": "Login successful."
        }
    }
}
```

**Response — First-Time Login:**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "New User",
                "email": "newuser@example.com",
                "two_factor_enabled": false,
                "is_first_login": true
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "requires_otp": false,
            "is_first_login": true,
            "message": "First-time login detected. Please change your password."
        }
    }
}
```

**Response — 2FA Enabled:**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "Secure User",
                "email": "secure@example.com",
                "two_factor_enabled": true,
                "is_first_login": false
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "requires_otp": true,
            "is_first_login": false,
            "message": "OTP has been sent to your email. Please verify to complete login."
        }
    }
}
```

**Error Response — Invalid Credentials:**

```json
{
    "errors": [
        {
            "message": "Invalid credentials. Please check your email and password.",
            "extensions": {
                "category": "authentication"
            }
        }
    ],
    "data": {
        "login": null
    }
}
```

---

### 6.2 Register (Admin Only)

**Mutation:**

```graphql
mutation Register($input: RegisterInput!) {
    register(input: $input) {
        user {
            id
            name
            email
            two_factor_enabled
            is_first_login
        }
        message
    }
}
```

**Variables:**

```json
{
    "input": {
        "name": "Jane Smith",
        "email": "jane@example.com",
        "two_factor_enabled": false
    }
}
```

**Required Header:**

```
Authorization: Bearer <admin-jwt-token>
```

**Response — Success:**

```json
{
    "data": {
        "register": {
            "user": {
                "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
                "name": "Jane Smith",
                "email": "jane@example.com",
                "two_factor_enabled": false,
                "is_first_login": true
            },
            "message": "User created successfully. Temporary password sent to jane@example.com"
        }
    }
}
```

**Error Response — Not Admin:**

```json
{
    "errors": [
        {
            "message": "Unauthorized. Only administrators can create new users.",
            "extensions": {
                "category": "authentication"
            }
        }
    ],
    "data": {
        "register": null
    }
}
```

**Error Response — Validation Failed (duplicate email):**

```json
{
    "errors": [
        {
            "message": "Validation failed.",
            "extensions": {
                "category": "validation",
                "validation": {
                    "email": ["The email has already been taken."]
                }
            }
        }
    ],
    "data": {
        "register": null
    }
}
```

---

### 6.3 Change Password (First-Time Login)

**Mutation:**

```graphql
mutation ChangePassword($input: ChangePasswordInput!) {
    changePassword(input: $input) {
        user {
            id
            name
            email
            is_first_login
        }
        token
        message
    }
}
```

**Variables:**

```json
{
    "input": {
        "password": "MyNewSecurePassword123",
        "password_confirmation": "MyNewSecurePassword123"
    }
}
```

**Required Header:**

```
Authorization: Bearer <temporary-jwt-token-from-first-login>
```

**Response — Success:**

```json
{
    "data": {
        "changePassword": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "New User",
                "email": "newuser@example.com",
                "is_first_login": false
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "message": "Password changed successfully. Welcome!"
        }
    }
}
```

---

### 6.4 Verify OTP (Two-Factor Authentication)

**Mutation:**

```graphql
mutation VerifyOtp($input: VerifyOtpInput!) {
    verifyOtp(input: $input) {
        user {
            id
            name
            email
        }
        token
        message
    }
}
```

**Variables:**

```json
{
    "input": {
        "email": "secure@example.com",
        "otp": "482931"
    }
}
```

**Response — Success:**

```json
{
    "data": {
        "verifyOtp": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "Secure User",
                "email": "secure@example.com"
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "message": "OTP verified successfully. Login complete."
        }
    }
}
```

**Error Response — Invalid OTP:**

```json
{
    "errors": [
        {
            "message": "Invalid or expired OTP. Please request a new one.",
            "extensions": {
                "category": "business"
            }
        }
    ],
    "data": {
        "verifyOtp": null
    }
}
```

---

### 6.5 Resend OTP

**Mutation:**

```graphql
mutation ResendOtp($input: ResendOtpInput!) {
    resendOtp(input: $input)
}
```

**Variables:**

```json
{
    "input": {
        "email": "secure@example.com"
    }
}
```

**Response — Success:**

```json
{
    "data": {
        "resendOtp": {
            "message": "OTP has been resent to your email."
        }
    }
}
```

---

### 6.6 Forgot Password

**Mutation:**

```graphql
mutation ForgotPassword($input: ForgotPasswordInput!) {
    forgotPassword(input: $input)
}
```

**Variables:**

```json
{
    "input": {
        "email": "user@example.com"
    }
}
```

**Response (always the same, whether email exists or not):**

```json
{
    "data": {
        "forgotPassword": {
            "message": "If this email exists, a password reset link has been sent."
        }
    }
}
```

---

### 6.7 Reset Password

**Mutation:**

```graphql
mutation ResetPassword($input: ResetPasswordInput!) {
    resetPassword(input: $input)
}
```

**Variables:**

```json
{
    "input": {
        "token": "Abc123XyzLongRandomTokenFromEmail...",
        "password": "MyNewPassword123",
        "password_confirmation": "MyNewPassword123"
    }
}
```

**Response — Success:**

```json
{
    "data": {
        "resetPassword": {
            "message": "Password has been reset successfully."
        }
    }
}
```

---

### 6.8 Refresh Token

**Mutation:**

```graphql
mutation RefreshToken {
    refreshToken
}
```

**Required Header:**

```
Authorization: Bearer <current-jwt-token>
```

**Response — Success:**

```json
{
    "data": {
        "refreshToken": {
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "message": "Token refreshed successfully."
        }
    }
}
```

---

### 6.9 Logout

**Mutation:**

```graphql
mutation Logout {
    logout
}
```

**Required Header:**

```
Authorization: Bearer <current-jwt-token>
```

**Response — Success:**

```json
{
    "data": {
        "logout": {
            "message": "Successfully logged out."
        }
    }
}
```

---

### 6.10 Get Current User (Me)

**Query:**

```graphql
query Me {
    me {
        id
        name
        email
        two_factor_enabled
        is_first_login
        created_at
        updated_at
    }
}
```

**Required Header:**

```
Authorization: Bearer <valid-jwt-token>
```

**Response — Success:**

```json
{
    "data": {
        "me": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "name": "John Doe",
            "email": "user@example.com",
            "two_factor_enabled": false,
            "is_first_login": false,
            "created_at": "2026-04-10 12:00:00",
            "updated_at": "2026-04-15 08:30:00"
        }
    }
}
```

**Error Response — No Token / Invalid Token:**

```json
{
    "errors": [
        {
            "message": "Unauthenticated.",
            "extensions": {
                "category": "authentication"
            }
        }
    ],
    "data": {
        "me": null
    }
}
```

---

## 7. GraphQL API & Postman Guide

> **Moved to a dedicated file:** [DOCUMENTATION_GraphQL_API.md](DOCUMENTATION_GraphQL_API.md)
>
> That file contains the complete reference for all 12 GraphQL operations (10 mutations + 2 queries), including:
>
> - Endpoint and header setup
> - Every mutation and query with GraphQL syntax, variables, and response examples
> - Step-by-step Postman setup for each operation (using the GraphQL body tab)
> - Auto-token-save scripts
> - A full end-to-end testing walkthrough
> - Quick reference table

---

## 8. Security Explanation

### What is JWT and Why is it Used?

**JWT** stands for **JSON Web Token**. It's a way to prove who you are without sending your password every time.

**Analogy:** Imagine going to a theme park:

1. You show your ticket (email + password) at the entrance gate (login).
2. The gate gives you a **wristband** (JWT token).
3. For every ride (API request), you just show your wristband — you don't need to buy another ticket.
4. The wristband has your information printed on it (your ID, name, roles) and an **expiration time**.
5. After closing time (token expires), the wristband stops working.

**A JWT token looks like this:**

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiZW1haWwiOiJ1c2VyQGV4YW1wbGUuY29tIiwicm9sZXMiOlsidXNlciJdfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c
```

It has three parts separated by dots:

1. **Header** — says it's a JWT and which algorithm was used to sign it.
2. **Payload** — contains data: user ID, email, roles, expiration time.
3. **Signature** — a cryptographic hash that proves the token hasn't been tampered with. Only the server (which knows the `JWT_SECRET`) can create a valid signature.

**Why not just use sessions (like websites)?**

- Sessions require the server to remember who you are (state).
- JWT is **stateless** — the token itself contains all the information.
- This makes JWT ideal for APIs where the client could be a mobile app, another server, or a single-page application.

### Why Does Middleware Exist?

**Middleware** is code that runs **before** your request reaches the resolver. Think of it as a security checkpoint.

In this module, the `@guard(with: ["api"])` directive acts as middleware:

```
Client Request → JWT Middleware → Resolver → Service → Database
                     │
                If no valid token
                     │
                     ▼
               401 Unauthenticated
               (request is blocked)
```

Without middleware, anyone could call the `register` mutation and create unlimited user accounts, or call `me` and retrieve other users' data.

### How Protected Routes Work

In GraphQL, there are no "routes" like in REST APIs. Instead, individual **queries** and **mutations** are protected in the schema file:

```graphql
# PROTECTED — requires @guard
register(input: RegisterInput!): RegisterResponse @guard(with: ["api"])

# PUBLIC — no @guard
login(input: LoginInput!): JSON
```

When a client calls a protected mutation:

1. The `@guard` directive intercepts the request.
2. It reads the `Authorization: Bearer <token>` header.
3. The JWT library decodes the token and verifies:
    - **Signature:** Was the token signed with the correct `JWT_SECRET`?
    - **Expiry:** Has the token expired?
    - **User:** Does the user encoded in the token still exist?
4. If ALL checks pass → the resolver runs normally.
5. If ANY check fails → a `401 Unauthenticated` error is returned.

### Common Beginner Mistakes

| Mistake                                  | What Happens                 | How to Fix                                                          |
| ---------------------------------------- | ---------------------------- | ------------------------------------------------------------------- |
| Forgetting the `Authorization` header    | `401 Unauthenticated` error  | Add `Authorization: Bearer <your-token>` to the headers             |
| Using `Token` instead of `Bearer`        | `401 Unauthenticated` error  | Always use the format: `Bearer <token>` (with a space after Bearer) |
| Using an expired token                   | `Token has expired` error    | Call `refreshToken` before the token expires, or log in again       |
| Sending `GET` instead of `POST`          | `405 Method Not Allowed`     | GraphQL always uses `POST` requests                                 |
| Wrong `Content-Type` header              | Request body is not parsed   | Always set `Content-Type: application/json`                         |
| Missing `password_confirmation`          | Validation error             | Include `password_confirmation` that matches `password`             |
| Trying to register without admin role    | `Unauthorized` error         | Only users with the `admin` role can register new users             |
| Not changing password on first login     | Limited access               | Call `changePassword` with the temporary token first                |
| Copying the token with extra spaces      | `Invalid token` error        | Copy the token value exactly, with no leading or trailing spaces    |
| Using the old token after `refreshToken` | `Token has been blacklisted` | Always use the NEW token returned by `refreshToken`                 |

---

## 9. How to Recreate This Module from Scratch

Follow these steps to build the entire Authentication module from zero.

### Step 1: Prerequisites

Make sure you have the following installed:

- **PHP 8.2+** — the programming language
- **Composer** — PHP package manager
- **PostgreSQL 15+** — the database
- **Laravel 12** — the framework (installed via Composer)

### Step 2: Create a Fresh Laravel Project

```bash
composer create-project laravel/laravel my-project
cd my-project
```

### Step 3: Install Required Packages

```bash
composer require nuwave/lighthouse:^6.66
composer require php-open-source-saver/jwt-auth:^2.8
composer require spatie/laravel-permission:^6.25
composer require mll-lab/graphql-php-scalars:^6.4
```

**What each package does:**

| Package                          | Purpose                                             |
| -------------------------------- | --------------------------------------------------- |
| `nuwave/lighthouse`              | Turns GraphQL schema files into a working API       |
| `php-open-source-saver/jwt-auth` | Handles JWT token creation, validation, and refresh |
| `spatie/laravel-permission`      | Manages user roles ("admin", "user")                |
| `mll-lab/graphql-php-scalars`    | Provides extra GraphQL data types                   |

### Step 4: Publish Package Configurations

```bash
php artisan vendor:publish --tag=lighthouse-config
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### Step 5: Generate JWT Secret

```bash
php artisan jwt:secret
```

### Step 6: Configure the `src/` Namespace

Open `composer.json` and add `Src\\` to the autoload PSR-4 section:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Src\\": "src/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

Then run:

```bash
composer dump-autoload
```

### Step 7: Create the Folder Structure

```bash
mkdir -p src/Modules/Authentication/Domain
mkdir -p src/Modules/Authentication/Application/Services
mkdir -p src/Modules/Authentication/Application/Exceptions
mkdir -p src/Modules/Authentication/Infrastructure/Repositories
mkdir -p src/Modules/Authentication/Infrastructure/Database/migrations
mkdir -p src/Modules/Authentication/GraphQL/Resolvers
mkdir -p src/Modules/Authentication/GraphQL/Scalars
```

> **Windows users:** Replace `mkdir -p` with individual `mkdir` commands without the `-p` flag, or create the folders manually in your file explorer.

### Step 8: Configure the `.env` File

Open `.env` and set these values:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=postgres
DB_PASSWORD=your_password

JWT_SECRET=<auto-generated-in-step-5>
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 9: Configure `config/auth.php`

Update the guards and providers:

```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Src\Modules\Authentication\Domain\Authentication::class,
    ],
],
```

### Step 10: Configure `config/lighthouse.php`

Set the namespaces so Lighthouse knows where to find resolvers:

```php
'namespaces' => [
    'models' => ['Src\\Modules\\Authentication\\Domain', 'App\\Models'],
    'queries' => ['Src\\Modules\\Authentication\\GraphQL\\Resolvers'],
    'mutations' => ['Src\\Modules\\Authentication\\GraphQL\\Resolvers'],
],
```

Add the custom error handler to the error_handlers array:

```php
'error_handlers' => [
    \Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
    \App\GraphQL\ErrorHandlers\SanitizedValidationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
],
```

### Step 11: Update Database Migrations

**a) Modify `database/migrations/0001_01_01_000000_create_users_table.php`:**

- Change the `id` column from auto-increment to UUID: `$table->uuid('id')->primary();`
- Add these columns to the `users` table:
    - `$table->boolean('two_factor_enabled')->default(false);`
    - `$table->string('otp_code')->nullable();`
    - `$table->timestamp('otp_expires_at')->nullable();`
    - `$table->boolean('is_first_login')->default(true);`

**b) Create a new migration for password resets:**

```bash
php artisan make:migration create_password_resets_table
```

Add these columns: `id`, `user_id` (UUID, foreign key to users), `token`, `expires_at`, `created_at`.

**c) Update the Spatie Permission migration:**
Change `$table->unsignedBigInteger(...)` to `$table->uuid(...)` in both the `model_has_permissions` and `model_has_roles` tables (because our user IDs are UUIDs, not integers).

### Step 12: Create the Module Files

Create each file with the code shown in [Section 4: Code Explanation](#4-code-explanation-beginner-friendly). Create them in this order:

1. **`src/Modules/Authentication/Domain/Authentication.php`** — The User model
2. **`src/Modules/Authentication/Application/Exceptions/AuthenticationException.php`** — Auth error class
3. **`src/Modules/Authentication/Application/Exceptions/BusinessLogicException.php`** — Business error class
4. **`src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`** — Database operations
5. **`src/Modules/Authentication/Application/Services/AuthenticationService.php`** — Business logic
6. **`src/Modules/Authentication/GraphQL/Resolvers/AuthResolver.php`** — GraphQL resolver
7. **`src/Modules/Authentication/GraphQL/Scalars/JSON.php`** — Custom JSON scalar
8. **`src/Modules/Authentication/AuthenticationServiceProvider.php`** — Service provider

### Step 13: Create GraphQL Schema Files

1. **`src/Modules/Authentication/GraphQL/inputs.graphql`** — Input type definitions
2. **`src/Modules/Authentication/GraphQL/types.graphql`** — Output type definitions
3. **`src/Modules/Authentication/GraphQL/queries.graphql`** — Query definitions
4. **`src/Modules/Authentication/GraphQL/mutations.graphql`** — Mutation definitions

5. **`graphql/schema.graphql`** — Root schema that imports the module files:

```graphql
scalar JSON
    @scalar(class: "Src\\Modules\\Authentication\\GraphQL\\Scalars\\JSON")

#import ../src/Modules/Authentication/GraphQL/inputs.graphql
#import ../src/Modules/Authentication/GraphQL/types.graphql
#import ../src/Modules/Authentication/GraphQL/queries.graphql
#import ../src/Modules/Authentication/GraphQL/mutations.graphql
```

### Step 14: Create Email Templates

Create three Mailable classes in `app/Mail/`:

- `OtpMail.php` — sends OTP codes
- `TempPasswordMail.php` — sends temporary passwords
- `PasswordResetMail.php` — sends password reset tokens

Create three Blade templates in `resources/views/emails/`:

- `otp.blade.php`
- `temp-password.blade.php`
- `password-reset.blade.php`

### Step 15: Create the Error Handler

Create `app/GraphQL/ErrorHandlers/SanitizedValidationErrorHandler.php` to clean up error responses. This strips internal details (file paths, line numbers) from errors sent to the client.

### Step 16: Register the Service Provider

Open `bootstrap/providers.php` and add the module's service provider:

```php
<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
];
```

### Step 17: Run Migrations and Seed Roles

```bash
# Create the PostgreSQL database first (via psql or pgAdmin)
# Then run migrations:
php artisan migrate

# Create the role seeder (database/seeders/RoleSeeder.php):
# Seed roles:
php artisan db:seed
```

### Step 18: Create the First Admin User

```bash
php artisan tinker
```

```php
use Src\Modules\Authentication\Domain\Authentication;

$admin = Authentication::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => 'password',
    'is_first_login' => false,
]);

$admin->assignRole('admin');
```

### Step 19: Start the Server

```bash
php artisan serve
```

The API is now available at: `http://localhost:8000/graphql`

### Step 20: Test with Postman

Follow the [GraphQL API & Postman Guide](DOCUMENTATION_GraphQL_API.md) to test each endpoint. The guide includes a full end-to-end testing walkthrough (Section 6) covering login, register, change password, profile, registration requests, and logout.

---

## 10. Glossary

| Term                 | Definition                                                                                                                                |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| **API**              | Application Programming Interface — a way for programs to talk to each other.                                                             |
| **Authentication**   | The process of proving who you are (login).                                                                                               |
| **Authorization**    | The process of checking what you're allowed to do (roles/permissions).                                                                    |
| **Bearer Token**     | The format for sending JWT tokens in headers: `Authorization: Bearer <token>`.                                                            |
| **Blade**            | Laravel's template engine for creating HTML views (used for email templates).                                                             |
| **CRUD**             | Create, Read, Update, Delete — the four basic database operations.                                                                        |
| **Domain**           | The core business concept (in this case, "a user who can authenticate").                                                                  |
| **Eloquent**         | Laravel's ORM (Object-Relational Mapping) — lets you interact with the database using PHP classes instead of SQL.                         |
| **GraphQL**          | A query language for APIs. Unlike REST (which has many endpoints), GraphQL has one endpoint where you specify exactly what data you need. |
| **Guard**            | A way Laravel determines how users are authenticated (sessions for web, JWT for API).                                                     |
| **Hash**             | A one-way mathematical function that converts data into a fixed-length string. Used for passwords so the original can't be recovered.     |
| **JWT**              | JSON Web Token — a self-contained token that carries user data and has a cryptographic signature.                                         |
| **Lighthouse**       | A Laravel package that turns `.graphql` schema files into a working API.                                                                  |
| **Middleware**       | Code that runs before/after a request, used for authentication checks, logging, etc.                                                      |
| **Migration**        | A PHP file that defines database table structure changes. Like version control for your database.                                         |
| **Model**            | A PHP class that represents a database table. Each instance is one row.                                                                   |
| **Mutation**         | A GraphQL operation that **changes** data (create, update, delete).                                                                       |
| **OTP**              | One-Time Password — a temporary code sent via email for two-factor authentication.                                                        |
| **PSR-4**            | A PHP standard for autoloading classes based on their namespace and file path.                                                            |
| **Query**            | A GraphQL operation that **reads** data.                                                                                                  |
| **Repository**       | A class that handles all database operations, keeping them separate from business logic.                                                  |
| **Resolver**         | A function that handles a specific GraphQL query or mutation.                                                                             |
| **Role**             | A label assigned to users (like "admin" or "user") that determines what they can do.                                                      |
| **Scalar**           | A basic data type in GraphQL (String, Int, Boolean, etc.). Custom scalars like JSON extend this.                                          |
| **Seeder**           | A PHP file that inserts initial data into the database (like creating roles).                                                             |
| **Service**          | A class that contains business logic — the rules and decisions of the application.                                                        |
| **Service Provider** | A class that tells Laravel how to create and configure module components.                                                                 |
| **Singleton**        | A design pattern where only one instance of a class exists in the entire application.                                                     |
| **UUID**             | Universally Unique Identifier — a 128-bit value like `550e8400-e29b-41d4-a716-446655440000`. Harder to guess than sequential IDs.         |
| **2FA**              | Two-Factor Authentication — requires two forms of proof (password + OTP) to log in.                                                       |

---

_This documentation was generated based on the actual source code of the Authentication module. Last updated: April 15, 2026._

---

## 11. User Registration Request Flow (New Feature)

### Feature Overview

This feature allows **anyone** (even without an account) to submit a **request** to join the system. It does **NOT** create a user account — it only:

1. Records the request in the database.
2. Sends an email notification to all administrators.
3. Returns a success message to the person who requested access.

Think of it like walking up to the security desk of an office building and saying: _"I'd like to work here."_ The guard writes down your name and email, then sends a message to the office manager. You don't get a badge — you just get told: _"We've noted your request. Someone will review it."_

**This feature intentionally does NOT:**

- Create a user account
- Issue a JWT token
- Grant any access
- Bypass the admin-only registration rule

The existing admin-only registration remains unchanged. This is purely a **notification and tracking system**.

---

### Step-by-Step Backend Flow

Here is exactly what happens when someone submits a registration request:

```
┌───────────────────────────────────────────────────────────────────────────┐
│  1. Client sends GraphQL mutation requestUserRegistration(input: {...})   │
│     with username and email. NO authentication needed.                    │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  2. GraphQL validates input via @rules directives:                        │
│     - username: required, min 3 chars, max 255 chars                      │
│     - email: required, valid email format, max 255 chars                  │
│     If invalid → returns a validation error immediately.                  │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  3. AuthResolver.requestUserRegistration() receives the request           │
│     and calls AuthenticationService.requestUserRegistration()              │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  4. Service performs business logic checks:                                │
│     a. Check if a PENDING request already exists for this email           │
│        → If yes: throw BusinessLogicException (duplicate)                 │
│     b. Check if a USER ACCOUNT already exists for this email              │
│        → If yes: throw BusinessLogicException (already registered)        │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  5. Repository saves the request to user_registration_requests table      │
│     with status = 'pending'                                               │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  6. Service finds ALL users with the 'admin' role                         │
│     and sends each one an email notification with:                        │
│     - Username                                                            │
│     - Email                                                               │
│     - Timestamp                                                           │
│     - Message: "A user has requested registration approval"               │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  7. Returns { success: true, message: "..." } to the client               │
│     NO user account is created. NO token is issued.                       │
└───────────────────────────────────────────────────────────────────────────┘
```

---

### Architecture Diagram

```
Client (anyone)          AuthResolver                  AuthenticationService             AuthenticationRepository          Database
  │                          │                                │                                  │                           │
  │  POST /graphql           │                                │                                  │                           │
  │  mutation                │                                │                                  │                           │
  │  requestUserRegistration │                                │                                  │                           │
  │─────────────────────────>│                                │                                  │                           │
  │                          │                                │                                  │                           │
  │                   @rules validation                       │                                  │                           │
  │                   (username min:3, email)                  │                                  │                           │
  │                          │                                │                                  │                           │
  │                          │  requestUserRegistration()     │                                  │                           │
  │                          │───────────────────────────────>│                                  │                           │
  │                          │                                │                                  │                           │
  │                          │                                │  findPendingRegistrationReq...   │                           │
  │                          │                                │─────────────────────────────────>│  SELECT * FROM             │
  │                          │                                │                                  │  user_registration_requests│
  │                          │                                │                                  │─────────────────────────->│
  │                          │                                │                                  │  null (no duplicate)       │
  │                          │                                │                                  │<─────────────────────────-│
  │                          │                                │                                  │                           │
  │                          │                                │  findUserByEmail()               │                           │
  │                          │                                │─────────────────────────────────>│  SELECT * FROM users       │
  │                          │                                │                                  │─────────────────────────->│
  │                          │                                │                                  │  null (no existing user)   │
  │                          │                                │                                  │<─────────────────────────-│
  │                          │                                │                                  │                           │
  │                          │                                │  createRegistrationRequest()     │                           │
  │                          │                                │─────────────────────────────────>│  INSERT INTO               │
  │                          │                                │                                  │  user_registration_requests│
  │                          │                                │                                  │─────────────────────────->│
  │                          │                                │                                  │                           │
  │                          │                                │  Send email to ALL admins         │                           │
  │                          │                                │  (RegistrationRequestMail)       │                           │
  │                          │                                │                                  │                           │
  │                          │  { success, message }          │                                  │                           │
  │                          │<───────────────────────────────│                                  │                           │
  │  { success, message }   │                                │                                  │                           │
  │<─────────────────────────│                                │                                  │                           │
```

---

### GraphQL Mutation Documentation

#### requestUserRegistration

**Mutation:**

```graphql
mutation RequestUserRegistration($input: RequestUserRegistrationInput!) {
    requestUserRegistration(input: $input) {
        success
        message
    }
}
```

**Variables:**

```json
{
    "input": {
        "username": "johndoe",
        "email": "johndoe@example.com"
    }
}
```

**Required Headers:**

| Key            | Value              |
| -------------- | ------------------ |
| `Content-Type` | `application/json` |

> **No `Authorization` header needed.** This mutation is public — anyone can call it.

**Response — Success:**

```json
{
    "data": {
        "requestUserRegistration": {
            "success": true,
            "message": "Your registration request has been submitted. An administrator will review it shortly."
        }
    }
}
```

**Error Response — Duplicate Request (same email already pending):**

```json
{
    "errors": [
        {
            "message": "A registration request with this email is already pending.",
            "extensions": {
                "category": "business"
            }
        }
    ],
    "data": {
        "requestUserRegistration": null
    }
}
```

**Error Response — Email Already Has an Account:**

```json
{
    "errors": [
        {
            "message": "This email is already associated with an existing account.",
            "extensions": {
                "category": "business"
            }
        }
    ],
    "data": {
        "requestUserRegistration": null
    }
}
```

**Error Response — Validation Failed:**

```json
{
    "errors": [
        {
            "message": "Validation failed.",
            "extensions": {
                "category": "validation",
                "validation": {
                    "username": [
                        "The username field must be at least 3 characters."
                    ],
                    "email": ["The email field must be a valid email address."]
                }
            }
        }
    ],
    "data": {
        "requestUserRegistration": null
    }
}
```

---

### Postman Setup for this Mutation

> See [DOCUMENTATION_GraphQL_API.md — Section 4.10: requestUserRegistration](DOCUMENTATION_GraphQL_API.md#410-requestuserregistration) for full Postman setup instructions.

---

### Files Created / Modified

| File                                                                                                       | Action       | Purpose                                                                                                                           |
| ---------------------------------------------------------------------------------------------------------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------- |
| `Infrastructure/Database/migrations/2026_04_15_000001_create_user_registration_requests_table.php`         | **Created**  | Migration to create the `user_registration_requests` database table                                                               |
| `Infrastructure/Database/migrations/2026_04_15_000002_add_user_id_to_user_registration_requests_table.php` | **Created**  | Migration to add `user_id` (nullable UUID, foreign key) to the `user_registration_requests` table                                 |
| `app/Mail/RegistrationRequestMail.php`                                                                     | **Created**  | Mailable class that formats the admin notification email                                                                          |
| `resources/views/emails/registration-request.blade.php`                                                    | **Created**  | HTML template for the admin notification email                                                                                    |
| `Infrastructure/Repositories/AuthenticationRepository.php`                                                 | **Modified** | Added `findPendingRegistrationRequestByEmail()`, `createRegistrationRequest()`, and `markRegistrationRequestAsCreated()` methods  |
| `Application/Services/AuthenticationService.php`                                                           | **Modified** | Added `requestUserRegistration()` and `markRegistrationRequestAsCreated()` methods; integrated auto-linking into `registerUser()` |
| `GraphQL/Resolvers/AuthResolver.php`                                                                       | **Modified** | Added `requestUserRegistration()` resolver method                                                                                 |
| `GraphQL/inputs.graphql`                                                                                   | **Modified** | Added `RequestUserRegistrationInput` input type                                                                                   |
| `GraphQL/types.graphql`                                                                                    | **Modified** | Added `RequestUserRegistrationResponse` type                                                                                      |
| `GraphQL/mutations.graphql`                                                                                | **Modified** | Added `requestUserRegistration` mutation definition                                                                               |

---

### How This Feature Integrates into the Existing Module

This feature follows the **exact same architectural pattern** as every other feature in the module:

```
GraphQL Schema (inputs.graphql, mutations.graphql, types.graphql)
        │
        ▼
AuthResolver.requestUserRegistration()       ← Thin resolver, no logic
        │
        ▼
AuthenticationService.requestUserRegistration()  ← All business logic here
        │
        ├── AuthenticationRepository.findPendingRegistrationRequestByEmail()
        ├── AuthenticationRepository.findUserByEmail()
        ├── AuthenticationRepository.createRegistrationRequest()
        └── Mail::to(admin)->send(RegistrationRequestMail)
```

**Key integration points:**

1. **Same service class** — `requestUserRegistration()` is added as a new method on `AuthenticationService`, alongside `loginUser()`, `registerUser()`, etc.
2. **Same repository class** — Two new database methods are added to `AuthenticationRepository`.
3. **Same resolver class** — The new resolver method follows the exact same pattern: receive args, call service, return result.
4. **Same GraphQL schema structure** — New input, type, and mutation are added to the existing `.graphql` files.
5. **Same error handling** — Uses `BusinessLogicException` for business rule violations, which produces clean error responses through the existing `SanitizedValidationErrorHandler`.

---

### Database Table: `user_registration_requests`

The migration creates this table (two migrations combined):

| Column       | Type                          | Description                                                                                              |
| ------------ | ----------------------------- | -------------------------------------------------------------------------------------------------------- |
| `id`         | UUID (primary key)            | Unique identifier for each request                                                                       |
| `username`   | string                        | The requested username                                                                                   |
| `email`      | string                        | The requester's email address                                                                            |
| `status`     | string (default: `'pending'`) | Status of the request: `pending` (awaiting admin action) or `created` (admin created the user)           |
| `user_id`    | UUID (nullable, foreign key)  | References `users.id` — linked when an admin creates a user account for this request. ON DELETE SET NULL |
| `created_at` | timestamp                     | When the request was submitted                                                                           |
| `updated_at` | timestamp                     | When the request was last modified                                                                       |

**Status Lifecycle:**

```
pending  ──(admin creates user with matching email)──>  created
```

- `pending` — The default status when a registration request is first submitted.
- `created` — Set automatically when an admin creates a user account whose email matches a pending request. The `user_id` column is also populated with the new user's UUID.

**Migrations:**

1. `2026_04_15_000001_create_user_registration_requests_table.php` — Creates the base table with `id`, `username`, `email`, `status`, and timestamps.
2. `2026_04_15_000002_add_user_id_to_user_registration_requests_table.php` — Adds the `user_id` column (nullable UUID, foreign key to `users.id`, ON DELETE SET NULL).

To run both migrations:

```bash
php artisan migrate
```

---

### Security Explanation

**Why doesn't this feature automatically create a user?**

This is a deliberate security design. Allowing public user creation (self-registration) would mean:

- Anyone on the internet could create accounts.
- Attackers could create thousands of fake accounts.
- There would be no control over who gains access to the system.

By keeping registration **admin-only**, the system ensures that every user account is reviewed and approved by a real administrator.

**What about abuse of the request endpoint?**

The mutation is public, so someone could potentially submit many fake requests. The current protections against this are:

1. **Duplicate email check** — The same email cannot be submitted if a pending request already exists.
2. **Existing account check** — Emails already registered are rejected.
3. **Input validation** — Malformed usernames (too short) and invalid emails are rejected before processing.

For production environments, you may also want to add:

- **Rate limiting** — Restrict how many requests a single IP can make per minute.
- **CAPTCHA** — Ensure a real human is making the request.

**What data is exposed?**

The error messages intentionally reveal whether an email already has a pending request or an existing account. If this is a security concern (email enumeration), you can modify the service to always return the same generic success message regardless of the outcome — similar to how `forgotPassword` works.

---

### Admin Creation Integration (Registration Request Lifecycle)

#### What Is This?

When an admin creates a new user account (via the `register` mutation), the system automatically checks if there is a **pending registration request** with a matching email. If one exists, it:

1. Updates the request's `status` from `pending` to `created`.
2. Links the newly created user account by storing the user's UUID in the `user_id` column.

This happens **silently** inside the existing `registerUser()` flow. The admin does not need to do anything extra — the system handles it automatically.

Think of it like this: someone walked up to the security desk and said _"I'd like to work here."_ The guard wrote down their name. Later, the office manager says _"Hire this person."_ The system automatically goes back to the guard's notebook and writes _"Done — this person now has badge #12345."_

---

#### Updated End-to-End Flow

Here is the complete lifecycle from request submission to user creation:

```
┌───────────────────────────────────────────────────────────────────────────┐
│  PHASE 1: Registration Request (public, no auth needed)                   │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  1. Anyone submits requestUserRegistration(username, email)               │
│     → Validated, duplicate-checked, stored as 'pending'                   │
│     → Email sent to all admins                                            │
│     → Response: "Your request has been submitted"                         │
└───────────────────────────────────────────────────────────────────────────┘

                        ⏳ Time passes... Admin reviews the request

┌───────────────────────────────────────────────────────────────────────────┐
│  PHASE 2: Admin Creates User (authenticated, admin-only)                  │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  2. Admin calls register(name, email, ...) to create the user account     │
│     → User record created in `users` table                                │
│     → Role 'user' assigned                                                │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  3. System automatically calls markRegistrationRequestAsCreated()         │
│     → Searches user_registration_requests for a PENDING request           │
│       with the SAME email                                                 │
│     → If found: updates status to 'created', sets user_id                 │
│     → If NOT found: nothing happens (the admin created a user who         │
│       never submitted a request — that's perfectly fine)                   │
│     → If error: logs a warning, does NOT interrupt user creation          │
└───────────────────────────────────┬───────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────────┐
│  4. Temporary password email sent to the new user                         │
│     → Response: "User created successfully"                               │
└───────────────────────────────────────────────────────────────────────────┘
```

---

#### Updated Architecture Diagram

```
Admin (authenticated)    AuthResolver                  AuthenticationService             AuthenticationRepository          Database
  │                          │                                │                                  │                           │
  │  POST /graphql           │                                │                                  │                           │
  │  mutation register(...)  │                                │                                  │                           │
  │─────────────────────────>│                                │                                  │                           │
  │                          │  registerUser()                │                                  │                           │
  │                          │───────────────────────────────>│                                  │                           │
  │                          │                                │                                  │                           │
  │                          │                                │  createUser()                    │                           │
  │                          │                                │─────────────────────────────────>│  INSERT INTO users         │
  │                          │                                │                                  │─────────────────────────->│
  │                          │                                │                                  │  new User (with UUID)      │
  │                          │                                │                                  │<─────────────────────────-│
  │                          │                                │                                  │                           │
  │                          │                                │  $user->assignRole('user')       │                           │
  │                          │                                │                                  │                           │
  │                          │                                │  markRegistrationRequestAs...     │                           │
  │                          │                                │─────────────────────────────────>│  UPDATE                    │
  │                          │                                │                                  │  user_registration_requests│
  │                          │                                │                                  │  SET status='created',     │
  │                          │                                │                                  │      user_id=<uuid>        │
  │                          │                                │                                  │  WHERE email=<email>       │
  │                          │                                │                                  │    AND status='pending'    │
  │                          │                                │                                  │─────────────────────────->│
  │                          │                                │                                  │  affected rows (0 or 1)    │
  │                          │                                │                                  │<─────────────────────────-│
  │                          │                                │                                  │                           │
  │                          │                                │  Mail::send(TempPasswordMail)    │                           │
  │                          │                                │                                  │                           │
  │                          │  { user, message }             │                                  │                           │
  │                          │<───────────────────────────────│                                  │                           │
  │  { user, message }      │                                │                                  │                           │
  │<─────────────────────────│                                │                                  │                           │
```

---

#### Code: How It Works

##### 1. Integration Point in `registerUser()` (AuthenticationService)

The call is placed **after** the user is created and assigned a role, but **before** the temporary password email is sent:

```php
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

    // ✅ NEW: If a pending registration request exists for this email,
    //         update its status to 'created' and link the user_id.
    $this->markRegistrationRequestAsCreated($user->email, $user->id);

    Mail::to($user->email)->send(new TempPasswordMail($tempPassword, $user->name));

    return [
        'user' => $user,
        'message' => 'User created successfully. Temporary password sent to ' . $user->email,
    ];
}
```

##### 2. Service Method: `markRegistrationRequestAsCreated()` (AuthenticationService)

This method wraps the repository call in a `try/catch` so that a failure here **never** interrupts the user creation flow:

```php
public function markRegistrationRequestAsCreated(string $email, string $userId): void
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
```

**Why `try/catch`?** Creating the user account is the primary operation. Updating the registration request is a **secondary, non-critical** step. If the database has an issue updating the request (e.g., table doesn't exist yet, constraint violation), the user should still be created successfully. The warning is logged so developers can investigate later.

##### 3. Repository Method: `markRegistrationRequestAsCreated()` (AuthenticationRepository)

This method performs the actual database update:

```php
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
```

**How this query works:**

1. Searches the `user_registration_requests` table for a row where `email` matches AND `status` is `pending`.
2. If found, updates `status` to `created`, sets `user_id` to the new user's UUID, and updates `updated_at`.
3. Returns `true` if a row was updated, `false` if no matching pending request existed.

**Why `where('status', 'pending')`?** This ensures only pending requests are affected. If a request was already marked as `created` (or any other future status), it won't be modified again.

##### 4. Migration: Adding `user_id` Column

```php
// 2026_04_15_000002_add_user_id_to_user_registration_requests_table.php

Schema::table('user_registration_requests', function (Blueprint $table) {
    $table->uuid('user_id')->nullable()->after('status');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
});
```

**Why nullable?** Not every registration request will have a corresponding user. A request stays in `pending` status (with `user_id = null`) until an admin creates the account.

**Why ON DELETE SET NULL?** If the linked user account is ever deleted, the registration request record remains intact — only the `user_id` link is cleared. This preserves the historical record of the request.

---

#### Data Flow Explanation

**Before admin action:**

```
user_registration_requests table:
┌──────────┬──────────┬──────────────────────┬─────────┬─────────┬────────────┐
│ id       │ username │ email                │ status  │ user_id │ created_at │
├──────────┼──────────┼──────────────────────┼─────────┼─────────┼────────────┤
│ uuid-abc │ johndoe  │ johndoe@example.com  │ pending │ NULL    │ 2026-04-15 │
└──────────┴──────────┴──────────────────────┴─────────┴─────────┴────────────┘
```

**During admin action** (admin calls `register` mutation with email `johndoe@example.com`):

1. User record created in `users` table → gets UUID `uuid-xyz`.
2. Role `user` assigned.
3. `markRegistrationRequestAsCreated('johndoe@example.com', 'uuid-xyz')` called.
4. Repository executes: `UPDATE user_registration_requests SET status='created', user_id='uuid-xyz' WHERE email='johndoe@example.com' AND status='pending'`.

**After admin action:**

```
user_registration_requests table:
┌──────────┬──────────┬──────────────────────┬─────────┬──────────┬────────────┐
│ id       │ username │ email                │ status  │ user_id  │ created_at │
├──────────┼──────────┼──────────────────────┼─────────┼──────────┼────────────┤
│ uuid-abc │ johndoe  │ johndoe@example.com  │ created │ uuid-xyz │ 2026-04-15 │
└──────────┴──────────┴──────────────────────┴─────────┴──────────┴────────────┘

users table:
┌──────────┬──────────────────────┬─────────────┐
│ id       │ email                │ name        │
├──────────┼──────────────────────┼─────────────┤
│ uuid-xyz │ johndoe@example.com  │ John Doe    │
└──────────┴──────────────────────┴─────────────┘
```

The two tables are now linked: `user_registration_requests.user_id` → `users.id`.

---

#### Step-by-Step Explanation: What Happens When Admin Creates a User

1. **Admin calls `register` mutation** with the new user's name, email, and optional settings.
2. **Service verifies the caller is an admin** — if not, throws `AuthenticationException`.
3. **Temporary password generated** — a random 16-character string.
4. **User record inserted** into `users` table with hashed password and `is_first_login = true`.
5. **Role `user` assigned** to the new account via Spatie Permission.
6. **`markRegistrationRequestAsCreated()` called** with the new user's email and UUID:
    - The service method wraps the call in `try/catch`.
    - The repository searches for a pending request with the matching email.
    - **If a matching pending request exists:** status updated to `created`, `user_id` set to the new user's UUID.
    - **If NO matching request exists:** nothing happens — this is normal. The admin might be creating a user who never submitted a request.
    - **If an error occurs:** a warning is logged with the email, user ID, and error message. The user creation continues uninterrupted.
7. **Temporary password email sent** to the new user via `TempPasswordMail`.
8. **Success response returned** to the admin.

**Key design decision:** Step 6 is wrapped in `try/catch` because it is a **non-critical secondary operation**. The primary goal of `registerUser()` is to create the user account and send the password. Updating the registration request's status is a "nice-to-have" — if it fails, the user is still created successfully, and the failure is logged for investigation.

---

> **Continued in [DOCUMENTATION_PART_2.md](DOCUMENTATION_PART_2.md)** — Contains the detailed **Code Walkthrough** (Section 12) covering: how email matching works line-by-line, how requests are updated, how the listing query works end-to-end, and why the system never breaks if no registration request exists.
