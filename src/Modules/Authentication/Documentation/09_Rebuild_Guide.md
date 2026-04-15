# 09 — Rebuild Guide

Step-by-step instructions to recreate the Authentication module from scratch in a new Laravel 12 project.

---

## Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL 15+
- Node.js (for Vite)

---

## Step 1 — Create Laravel Project

```bash
composer create-project laravel/laravel pipTrek-be
cd pipTrek-be
```

---

## Step 2 — Configure Database

Edit `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=piptrek
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

---

## Step 3 — Install Dependencies

```bash
composer require php-open-source-saver/jwt-auth:^2.8
composer require nuwave/lighthouse:^6.66
composer require spatie/laravel-permission:^6.25
```

Publish configs:

```bash
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Generate JWT secret:

```bash
php artisan jwt:secret
```

---

## Step 4 — Create Module Directory Structure

```bash
mkdir -p src/Modules/Authentication/Application/Services
mkdir -p src/Modules/Authentication/Application/Exceptions
mkdir -p src/Modules/Authentication/Domain
mkdir -p src/Modules/Authentication/GraphQL/Resolvers
mkdir -p src/Modules/Authentication/GraphQL/Scalars
mkdir -p src/Modules/Authentication/Infrastructure/Repositories
mkdir -p src/Modules/Authentication/Infrastructure/Database/migrations
```

---

## Step 5 — Configure PSR-4 Autoloading

In `composer.json`, add under `"autoload" > "psr-4"`:

```json
"Src\\": "src/"
```

Run:

```bash
composer dump-autoload
```

---

## Step 6 — Create Migration Files

### 6a. Modify `database/migrations/0001_01_01_000000_create_users_table.php`

Change the `users` table to use UUID primary key and add auth columns:

- `$table->uuid('id')->primary();` (replace `$table->id()`)
- `$table->boolean('two_factor_enabled')->default(false)->after('password');`
- `$table->string('otp_code')->nullable()->after('two_factor_enabled');`
- `$table->timestamp('otp_expires_at')->nullable()->after('otp_code');`
- `$table->boolean('is_first_login')->default(true)->after('otp_expires_at');`

### 6b. Keep `database/migrations/2026_04_10_025657_create_password_resets_table.php`

Basic table with `id` and `timestamps`.

### 6c. Create `src/Modules/Authentication/Infrastructure/Database/migrations/2026_04_15_000001_create_user_registration_requests_table.php`

Fields:

- `$table->uuid('id')->primary();`
- `$table->string('username');`
- `$table->string('email');`
- `$table->string('status')->default('pending');`
- `$table->timestamps();`

### 6d. Create `src/Modules/Authentication/Infrastructure/Database/migrations/2026_04_15_000002_add_user_id_to_user_registration_requests_table.php`

Fields:

- `$table->uuid('user_id')->nullable()->after('status');`
- `$table->foreign('user_id')->references('id')->on('users')->onDelete('set null');`

See [Migrations.md](08_Full_Source_Code/Migrations.md) for full code.

---

## Step 7 — Create Domain Model

Create `src/Modules/Authentication/Domain/Authentication.php`:

- Extends `Authenticatable`, implements `JWTSubject`
- Uses `HasFactory`, `HasUuids`, `Notifiable`, `HasRoles`
- `$table = 'users'`, `$guard_name = 'api'`
- Fillable: `name`, `email`, `password`, `two_factor_enabled`, `otp_code`, `otp_expires_at`, `is_first_login`
- Casts: `password` → hashed, `two_factor_enabled` → boolean, etc.

See [Domain.md](08_Full_Source_Code/Domain.md) for full code.

---

## Step 8 — Create Exception Classes

Create both in `src/Modules/Authentication/Application/Exceptions/`:

1. **AuthenticationException** — `implements ClientAware`, category `'authentication'`
2. **BusinessLogicException** — `implements ClientAware`, category `'business'`

See [Exceptions.md](08_Full_Source_Code/Exceptions.md) for full code.

---

## Step 9 — Create Repository

Create `src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`:

17 methods covering:

- User CRUD (`createUser`, `findUserByEmail`, `findUserById`)
- OTP operations (`storeOtp`, `verifyOtp`, `clearOtp`)
- Password reset operations (`createPasswordResetToken`, `findValidPasswordReset`, `updatePassword`, `deletePasswordResetTokens`)
- Login state (`updateLastLogin`, `updateFirstLoginFlag`)
- Registration requests (`findPendingRegistrationRequestByEmail`, `createRegistrationRequest`, `markRegistrationRequestAsCreated`, `getAllRegistrationRequests`)

See [Repositories.md](08_Full_Source_Code/Repositories.md) for full code.

---

## Step 10 — Create Services

### 10a. `UserRegistrationRequestService.php`

Create `src/Modules/Authentication/Application/Services/UserRegistrationRequestService.php`:

- Injects `AuthenticationRepository`
- `markAsCreatedByEmail()` — try/catch wrapper, logs on failure
- `getAllRequests()` — delegates to repository with optional status filter

### 10b. `AuthenticationService.php`

Create `src/Modules/Authentication/Application/Services/AuthenticationService.php`:

- Injects `AuthenticationRepository` and `UserRegistrationRequestService`
- All business logic: login, register, OTP, password reset, change password, JWT tokens, registration requests

See [Services.md](08_Full_Source_Code/Services.md) for full code.

---

## Step 11 — Create GraphQL Schema Files

### 11a. `src/Modules/Authentication/GraphQL/Scalars/JSON.php`

Custom scalar for flexible JSON responses.

### 11b. `src/Modules/Authentication/GraphQL/inputs.graphql`

8 input types: `LoginInput`, `RegisterInput`, `ChangePasswordInput`, `VerifyOtpInput`, `ResendOtpInput`, `ForgotPasswordInput`, `ResetPasswordInput`, `RequestUserRegistrationInput`

### 11c. `src/Modules/Authentication/GraphQL/types.graphql`

6 types: `User`, `RegisterResponse`, `ChangePasswordResponse`, `OtpVerificationResponse`, `RequestUserRegistrationResponse`, `UserRegistrationRequest`

### 11d. `src/Modules/Authentication/GraphQL/queries.graphql`

2 queries: `me`, `userRegistrationRequests`

### 11e. `src/Modules/Authentication/GraphQL/mutations.graphql`

10 mutations: `login`, `register`, `changePassword`, `verifyOtp`, `resendOtp`, `forgotPassword`, `resetPassword`, `refreshToken`, `logout`, `requestUserRegistration`

### 11f. `graphql/schema.graphql`

Root schema file that imports all module schemas:

```graphql
scalar JSON
    @scalar(class: "Src\\Modules\\Authentication\\GraphQL\\Scalars\\JSON")

#import ../src/Modules/Authentication/GraphQL/inputs.graphql
#import ../src/Modules/Authentication/GraphQL/types.graphql
#import ../src/Modules/Authentication/GraphQL/queries.graphql
#import ../src/Modules/Authentication/GraphQL/mutations.graphql
```

See [GraphQL_Schema.md](08_Full_Source_Code/GraphQL_Schema.md) for full code.

---

## Step 12 — Create Resolver

Create `src/Modules/Authentication/GraphQL/Resolvers/AuthResolver.php`:

- Injects `AuthenticationService` and `UserRegistrationRequestService`
- Thin pass-through methods for all 12 GraphQL operations

See [Resolvers.md](08_Full_Source_Code/Resolvers.md) for full code.

---

## Step 13 — Create Mail Classes

Create 4 Mailable classes in `app/Mail/`:

1. `OtpMail.php` — sends OTP code
2. `TempPasswordMail.php` — sends temporary password
3. `PasswordResetMail.php` — sends password reset token
4. `RegistrationRequestMail.php` — notifies admins of new registration requests

See [Mail_Classes.md](08_Full_Source_Code/Mail_Classes.md) for full code.

---

## Step 14 — Create Blade Templates

Create 4 templates in `resources/views/emails/`:

1. `otp.blade.php`
2. `temp-password.blade.php`
3. `password-reset.blade.php`
4. `registration-request.blade.php`

See [Blade_Templates.md](08_Full_Source_Code/Blade_Templates.md) for full code.

---

## Step 15 — Create Error Handler

Create `app/GraphQL/ErrorHandlers/SanitizedValidationErrorHandler.php`:

- Handles `ValidationException` — strips `input.` prefix
- Handles `ClientAware` exceptions — returns category
- Short-circuits the error pipeline

Register in `config/lighthouse.php`:

```php
'error_handlers' => [
    \App\GraphQL\ErrorHandlers\SanitizedValidationErrorHandler::class,
],
```

See [External_Config.md](08_Full_Source_Code/External_Config.md) for full code.

---

## Step 16 — Create Service Provider

Create `src/Modules/Authentication/AuthenticationServiceProvider.php`:

- Registers 3 singletons: `AuthenticationRepository`, `UserRegistrationRequestService`, `AuthenticationService`
- Loads module migrations from `Infrastructure/Database/migrations`

See [ServiceProvider.md](08_Full_Source_Code/ServiceProvider.md) for full code.

---

## Step 17 — Register Service Provider

Edit `bootstrap/providers.php`:

```php
<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
];
```

---

## Step 18 — Configure Auth Guard

Edit `config/auth.php`:

- Add `api` guard with `jwt` driver
- Change user provider model to `Src\Modules\Authentication\Domain\Authentication::class`

See [External_Config.md](08_Full_Source_Code/External_Config.md) for full code.

---

## Step 19 — Create Role Seeder

Create `database/seeders/RoleSeeder.php`:

```php
use Spatie\Permission\Models\Role;

Role::create(['name' => 'admin', 'guard_name' => 'api']);
Role::create(['name' => 'user', 'guard_name' => 'api']);
```

---

## Step 20 — Run Migrations and Seed

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

---

## Step 21 — Create Admin User (Tinker)

```bash
php artisan tinker
```

```php
use Src\Modules\Authentication\Domain\Authentication;

$admin = Authentication::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => 'admin123',
    'is_first_login' => false,
]);
$admin->assignRole('admin');
```

---

## Step 22 — Start Server and Test

```bash
php artisan serve
```

Open `http://localhost:8000/graphql` and test with the Login mutation.

See [07_Postman_Setup.md](07_Postman_Setup.md) for complete testing guide.

---

## Verification Checklist

- [ ] All migrations run without errors
- [ ] Roles `admin` and `user` exist (check `roles` table)
- [ ] Admin user can login and get JWT token
- [ ] Admin can create new users (`register` mutation)
- [ ] New users receive temp password email
- [ ] First-time login detected, password change required
- [ ] 2FA flow works (OTP send → verify)
- [ ] Password reset flow works (forgot → reset)
- [ ] Token refresh works
- [ ] Logout invalidates token
- [ ] Public registration request submits and notifies admins
- [ ] Admin creating user with matching email auto-links request (pending → created)
- [ ] `userRegistrationRequests` query returns all/filtered requests
- [ ] Validation errors return clean category `'validation'`
- [ ] Auth errors return category `'authentication'`
- [ ] Business errors return category `'business'`
