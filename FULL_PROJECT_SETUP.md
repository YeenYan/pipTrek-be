# Full Project Setup Guide — From Scratch

A complete, beginner-friendly guide to understanding and recreating this backend authentication system from scratch.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Prerequisites & Environment Setup](#prerequisites--environment-setup)
4. [Creating the Laravel Project](#creating-the-laravel-project)
5. [Installing Dependencies](#installing-dependencies)
6. [Environment Configuration](#environment-configuration)
7. [Database Setup](#database-setup)
8. [Project Structure Explained](#project-structure-explained)
9. [Building the Authentication Module](#building-the-authentication-module)
    - [Domain Layer](#step-1-domain-layer)
    - [Infrastructure Layer (Repository & Migrations)](#step-2-infrastructure-layer)
    - [Application Layer (Service & Exceptions)](#step-3-application-layer)
    - [GraphQL Layer (Schema & Resolvers)](#step-4-graphql-layer)
    - [Mail System](#step-5-mail-system)
    - [Error Handling](#step-6-error-handling)
    - [Service Provider](#step-7-service-provider)
10. [Key Features Explained](#key-features-explained)
11. [Running the Project](#running-the-project)
12. [Testing the API](#testing-the-api)
13. [Troubleshooting](#troubleshooting)
14. [Useful Commands Summary](#useful-commands-summary)
15. [Tips for Beginners](#tips-for-beginners)
16. [Docker Setup](#docker-setup)
    - [Prerequisites](#docker-prerequisites)
    - [Quick Start](#docker-quick-start)
    - [Files Overview](#docker-files-overview)
    - [Common Docker Commands](#common-docker-commands)
    - [Switching Databases](#switching-databases-in-docker)
    - [Production Considerations](#production-considerations)
17. [Summary](#summary)

---

## Project Overview

This project is a **backend authentication API** built with **Laravel 12** and **GraphQL**. It provides a complete user management and authentication system with the following capabilities:

- **Admin-only user registration** — Only administrators can create user accounts.
- **Temporary password system** — New users receive an auto-generated password via email.
- **First-time login password change** — Users must change their temporary password before accessing the system.
- **Two-Factor Authentication (2FA)** — Optional OTP-based verification sent via email.
- **Password reset** — Token-based password recovery flow.
- **JWT token authentication** — Stateless API authentication using JSON Web Tokens.
- **Role-based access control** — Users and admins have different permissions.
- **Structured error handling** — Clean, predictable error responses for frontend consumption.

### Architecture Pattern

The project uses a **Domain-Driven Modular Monolith** architecture. Instead of scattering code across Laravel's default folders, related code is grouped into self-contained modules:

```
GraphQL Request → Resolver (presentation) → Service (business logic) → Repository (data access) → Database
```

This means:

- **Resolvers** are thin — they only receive input and call the service.
- **Services** contain all business rules (validation, token generation, email sending).
- **Repositories** handle database queries — the service never touches the database directly.

---

## Technology Stack

| Technology                         | Version | Purpose                                              |
| ---------------------------------- | ------- | ---------------------------------------------------- |
| **PHP**                            | 8.2+    | Programming language                                 |
| **Laravel**                        | 12.x    | PHP web framework                                    |
| **Composer**                       | 2.x     | PHP package manager                                  |
| **PostgreSQL**                     | 15+     | Relational database                                  |
| **nuwave/lighthouse**              | 6.66+   | GraphQL server for Laravel                           |
| **php-open-source-saver/jwt-auth** | 2.8+    | JWT authentication (fork of tymon/jwt-auth)          |
| **spatie/laravel-permission**      | 6.25+   | Role-based access control                            |
| **mll-lab/graphql-php-scalars**    | 6.4+    | Additional GraphQL scalar types                      |
| **Node.js** (optional)             | 18+     | For frontend asset compilation (Vite + Tailwind CSS) |

### What is each technology?

- **PHP** is a server-side programming language. Laravel is written in PHP.
- **Laravel** is a PHP framework that provides tools for routing, database, authentication, email, and more.
- **Composer** is the package manager for PHP — like `npm` for JavaScript. It downloads and manages PHP libraries.
- **PostgreSQL** is a powerful relational database. The project stores users, roles, tokens, etc. in PostgreSQL tables.
- **GraphQL** is an API query language (alternative to REST). Instead of many endpoints (`/api/users`, `/api/login`), there is one endpoint (`/graphql`) where clients specify exactly what data they need.
- **Lighthouse** is a Laravel package that turns your GraphQL schema files into a working API.
- **JWT (JSON Web Token)** is a standard for stateless authentication. After login, the server gives the client a token. The client sends this token with every request to prove their identity.
- **Spatie Permission** is a Laravel package for managing user roles (e.g., "admin", "user") and permissions.

---

## Prerequisites & Environment Setup

### Step 1: Install PHP 8.2+

**Windows:**

1. Download PHP from [https://windows.php.net/download](https://windows.php.net/download) (choose the "VS16 x64 Thread Safe" ZIP).
2. Extract to `C:\php`.
3. Add `C:\php` to your system PATH environment variable.
4. Copy `php.ini-development` to `php.ini`.
5. Open `php.ini` and enable these extensions by removing the `;` at the start of each line:
    ```ini
    extension=curl
    extension=fileinfo
    extension=mbstring
    extension=openssl
    extension=pdo_pgsql
    extension=pgsql
    ```
6. Verify installation:
    ```bash
    php -v
    ```
    You should see something like `PHP 8.2.x`.

### Step 2: Install Composer

1. Download the installer from [https://getcomposer.org/download](https://getcomposer.org/download).
2. Run the installer (Windows) or follow the CLI instructions (macOS/Linux).
3. Verify:
    ```bash
    composer --version
    ```

### Step 3: Install PostgreSQL 15+

1. Download from [https://www.postgresql.org/download](https://www.postgresql.org/download).
2. Run the installer. During installation:
    - Set a password for the `postgres` user (remember this — you'll need it later).
    - Keep the default port `5432`.
3. Verify by opening a terminal:
    ```bash
    psql --version
    ```

### Step 4: Install Node.js (Optional)

Only needed if you want to compile frontend assets (CSS/JS).

1. Download from [https://nodejs.org](https://nodejs.org) (LTS version).
2. Verify:
    ```bash
    node -v
    npm -v
    ```

### Step 5: Install a Code Editor

We recommend **Visual Studio Code** — [https://code.visualstudio.com](https://code.visualstudio.com).

### Step 6: Install an API Testing Tool

Install **Postman** — [https://www.postman.com/downloads](https://www.postman.com/downloads). You'll use this to send GraphQL requests to test the API.

---

## Creating the Laravel Project

### Step 1: Create a New Laravel Project

Open your terminal and run:

```bash
composer create-project laravel/laravel authTest
cd authTest
```

This creates a fresh Laravel 12 project in a folder called `authTest`.

### Step 2: Set Up the `src/` Namespace

This project uses a custom `src/` directory for modules. Laravel doesn't include this by default, so you need to tell Composer about it.

Open `composer.json` and find the `"autoload"` section. Add the `Src\\` namespace:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Src\\": "src/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
},
```

Then run:

```bash
composer dump-autoload
```

This tells PHP where to find classes in the `src/` folder.

### Step 3: Create the Module Directory Structure

Create the full folder structure for the Authentication module:

```bash
mkdir src\Modules\Authentication\Domain
mkdir src\Modules\Authentication\Application\Services
mkdir src\Modules\Authentication\Application\Exceptions
mkdir src\Modules\Authentication\Infrastructure\Repositories
mkdir src\Modules\Authentication\Infrastructure\Database\migrations
mkdir src\Modules\Authentication\GraphQL\Resolvers
mkdir src\Modules\Authentication\GraphQL\Scalars
```

> **Note:** On Windows, use `mkdir` without `-p` and create each folder individually, or use your file explorer.

---

## Installing Dependencies

### Step 1: Install PHP Packages

```bash
composer require nuwave/lighthouse:^6.66
composer require php-open-source-saver/jwt-auth:^2.8
composer require spatie/laravel-permission:^6.25
composer require mll-lab/graphql-php-scalars:^6.4
```

**What each package does:**

| Package           | Command                                           | Purpose                                |
| ----------------- | ------------------------------------------------- | -------------------------------------- |
| Lighthouse        | `composer require nuwave/lighthouse`              | Adds GraphQL API support to Laravel    |
| JWT Auth          | `composer require php-open-source-saver/jwt-auth` | Enables JWT token-based authentication |
| Spatie Permission | `composer require spatie/laravel-permission`      | Adds roles and permissions system      |
| GraphQL Scalars   | `composer require mll-lab/graphql-php-scalars`    | Provides additional GraphQL data types |

### Step 2: Publish Package Configurations

After installing packages, publish their configuration files:

```bash
# Publish Lighthouse config (creates config/lighthouse.php)
php artisan vendor:publish --tag=lighthouse-config

# Publish JWT config (creates config/jwt.php)
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"

# Publish Spatie Permission config and migration
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### Step 3: Generate JWT Secret Key

```bash
php artisan jwt:secret
```

This adds a `JWT_SECRET` value to your `.env` file. This secret key is used to sign and verify JWT tokens.

### Step 4: Install Node.js Packages (Optional)

```bash
npm install
```

---

## Environment Configuration

### Step 1: Configure the `.env` File

Open the `.env` file in the project root and update these values:

```env
# Application Settings
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Password Hashing Rounds (12 is secure for production)
BCRYPT_ROUNDS=12

# Database Configuration (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=authTest
DB_USERNAME=postgres
DB_PASSWORD=admin

# JWT Configuration
JWT_SECRET=<auto-generated by php artisan jwt:secret>
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# Mail Configuration (use "log" for development — emails go to storage/logs/laravel.log)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**What these settings mean:**

| Variable          | Value      | Meaning                                                                                         |
| ----------------- | ---------- | ----------------------------------------------------------------------------------------------- |
| `DB_CONNECTION`   | `pgsql`    | Use PostgreSQL as the database                                                                  |
| `DB_DATABASE`     | `authTest` | Name of the database to create                                                                  |
| `DB_USERNAME`     | `postgres` | PostgreSQL username                                                                             |
| `DB_PASSWORD`     | `admin`    | PostgreSQL password (use what you set during installation)                                      |
| `JWT_TTL`         | `60`       | JWT tokens expire after 60 minutes                                                              |
| `JWT_REFRESH_TTL` | `20160`    | Refresh tokens last 14 days (20160 minutes)                                                     |
| `JWT_ALGO`        | `HS256`    | Algorithm used to sign JWT tokens                                                               |
| `MAIL_MAILER`     | `log`      | In development, emails are written to `storage/logs/laravel.log` instead of being actually sent |

### Step 2: Configure the Auth Guard

Open `config/auth.php` and configure the API guard to use JWT:

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

**Key point:** The `model` in the `users` provider points to the module's domain entity (`Authentication::class`), not the default `App\Models\User`. This is because the authentication module manages its own user model.

### Step 3: Configure Lighthouse (GraphQL Server)

Open `config/lighthouse.php` and set these key values:

```php
'route' => [
    'uri' => '/graphql',
    'name' => 'graphql',
    'prefix' => '',
    'middleware' => [
        \Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,
    ],
],

'guard' => ['api'],

'schema_path' => base_path('graphql/schema.graphql'),

'namespaces' => [
    'models' => ['Src\\Modules\\Authentication\\Domain', 'App\\Models'],
    'queries' => ['Src\\Modules\\Authentication\\GraphQL\\Resolvers'],
    'mutations' => ['Src\\Modules\\Authentication\\GraphQL\\Resolvers'],
    'subscriptions' => ['App\\GraphQL\\Subscriptions'],
    'types' => ['App\\GraphQL\\Types'],
    'interfaces' => ['App\\GraphQL\\Interfaces'],
    'unions' => ['App\\GraphQL\\Unions'],
    'scalars' => ['App\\GraphQL\\Scalars'],
    'directives' => ['App\\GraphQL\\Directives'],
    'validators' => ['App\\GraphQL\\Validators'],
],

'error_handlers' => [
    \Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
    \App\GraphQL\ErrorHandlers\SanitizedValidationErrorHandler::class,
    \Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
],
```

**Key point:** The `namespaces` section tells Lighthouse where to find resolver classes. The `error_handlers` array controls how errors are formatted — our custom `SanitizedValidationErrorHandler` is included to produce clean error responses.

---

## Database Setup

### Step 1: Create the PostgreSQL Database

Open a terminal and connect to PostgreSQL:

```bash
psql -U postgres
```

Enter your password, then create the database:

```sql
CREATE DATABASE "authTest";
```

Type `\q` to exit.

### Step 2: Edit Migrations

Open `database\migrations\0001_01_01_000000_create_users_table.php`

Update columns to include the following fields
`two_factor_enabled`
`otp_code`
`otp_expires_at`
`is_first_login`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) { 
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->string('otp_code')->nullable()->after('two_factor_enabled');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->boolean('is_first_login')->default(true)->after('otp_expires_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'otp_code', 'otp_expires_at', 'is_first_login']);
        });
    }
};
```

### Step 3: Create Additional Migration

```bash
php artisan make:migration create_password_resets_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};

```

### Step 4: Update User Model

Auto-generating UUIDs when inserting User

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### Step 5: Update migrations -> create_permission_tables.php

Find this `$tableNames['model_has_permissions']`

```php
$table->unsignedBigInteger($columnNames['model_morph_key']);
//-> into
$table->uuid($columnNames['model_morph_key']);
```

Find this `$tableNames['model_has_roles']`

```php
$table->unsignedBigInteger($columnNames['model_morph_key']);
//-> into
$table->uuid($columnNames['model_morph_key']);
```

### Step 6: Run Migrations

Migrations are PHP files that create and modify database tables. Run them all:

Add Additional Migration, go to this section Search: Module Migrations

```bash
php artisan migrate
```

This creates the following tables:

### Database Tables Overview

| Table                                                                                      | Source                                                         | Purpose                                                           |
| ------------------------------------------------------------------------------------------ | -------------------------------------------------------------- | ----------------------------------------------------------------- |
| `users`                                                                                    | `database/migrations/0001_01_01_000000_create_users_table.php` | Stores user accounts                                              |
| `password_reset_tokens`                                                                    | Same migration                                                 | Laravel's default password reset (unused — we use a custom table) |
| `sessions`                                                                                 | Same migration                                                 | Stores session data                                               |
| `cache`                                                                                    | `database/migrations/0001_01_01_000001_create_cache_table.php` | Application cache storage                                         |
| `jobs`, `job_batches`, `failed_jobs`                                                       | `database/migrations/0001_01_01_000002_create_jobs_table.php`  | Queue system tables                                               |
| `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` | `database/migrations/..._create_permission_tables.php`         | Spatie Permission role/permission system                          |
| `password_resets`                                                                          | `src/.../migrations/..._create_password_resets_table.php`      | Custom password reset tokens with expiry                          |

The module's own migrations also run automatically (loaded by the `AuthenticationServiceProvider`), adding:

| Column Added         | Table   | Migration                                      | Purpose                                       |
| -------------------- | ------- | ---------------------------------------------- | --------------------------------------------- |
| `two_factor_enabled` | `users` | `..._add_two_factor_fields_to_users_table.php` | Boolean flag to enable/disable 2FA            |
| `otp_code`           | `users` | Same                                           | Stores hashed OTP code                        |
| `otp_expires_at`     | `users` | Same                                           | OTP expiration timestamp                      |
| `is_first_login`     | `users` | `..._add_is_first_login_to_users_table.php`    | Tracks if user must change temporary password |

### Final `users` Table Structure

| Column               | Type                     | Description                                       |
| -------------------- | ------------------------ | ------------------------------------------------- |
| `id`                 | UUID                     | Primary key (auto-generated UUID, not an integer) |
| `name`               | string                   | User's full name                                  |
| `email`              | string (unique)          | User's email address                              |
| `email_verified_at`  | timestamp (nullable)     | When email was verified                           |
| `password`           | string                   | Bcrypt-hashed password                            |
| `two_factor_enabled` | boolean (default: false) | Whether 2FA is enabled                            |
| `otp_code`           | string (nullable)        | Hashed OTP for 2FA verification                   |
| `otp_expires_at`     | timestamp (nullable)     | When the OTP expires (5-minute window)            |
| `is_first_login`     | boolean (default: true)  | Whether user needs to change temporary password   |
| `remember_token`     | string (nullable)        | Laravel's "remember me" token                     |
| `created_at`         | timestamp                | When the record was created                       |
| `updated_at`         | timestamp                | When the record was last updated                  |

### Spatie Permission — Role Storage

Roles are **not** stored as a column on the users table. Instead, Spatie Permission uses separate tables:

- `roles` — Stores role names (e.g., "admin", "user") with a `guard_name` column.
- `model_has_roles` — Links users to their roles via `model_id` (user UUID) and `role_id`.

```

```

### Step 3: Create and Seed Roles

Create `database/seeders/RolePermissionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('user', 'api');
        Role::findOrCreate('admin', 'api');
    }
}
```

Update `database/seeders/DatabaseSeeder.php` to call it:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
```

```bash
php artisan db:seed
php artisan db:seed --class=RolePermissionSeeder
```

### Step 4: Create the First Admin User

Since registration requires admin access, you must manually create the first admin. The easiest way is with **Laravel Tinker**:

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

> **Note:** Set `is_first_login` to `false` so the admin is not forced to change their password. The password is automatically hashed by the model's `hashed` cast.

---

## Project Structure Explained

```
authTest/
├── app/                          # Laravel's default application folder
│   ├── GraphQL/
│   │   └── ErrorHandlers/
│   │       └── SanitizedValidationErrorHandler.php  # Cleans error responses
│   ├── Http/
│   │   └── Controllers/
│   │       └── Controller.php    # Base controller (unused — we use GraphQL)
│   ├── Mail/
│   │   ├── OtpMail.php           # Email for 2FA OTP codes
│   │   ├── PasswordResetMail.php # Email for password reset tokens
│   │   └── TempPasswordMail.php  # Email for temporary passwords
│   ├── Models/
│   │   └── User.php              # Laravel's default User model (unused)
│   └── Providers/
│       └── AppServiceProvider.php # App-level service provider
│
├── bootstrap/
│   ├── app.php                   # Application bootstrap — middleware & exception config
│   └── providers.php             # Registers service providers (including AuthenticationServiceProvider)
│
├── config/                       # Configuration files
│   ├── auth.php                  # Authentication guards (API + JWT)
│   ├── jwt.php                   # JWT token settings (TTL, algorithm, etc.)
│   ├── lighthouse.php            # GraphQL server configuration
│   ├── permission.php            # Spatie role/permission settings
│   ├── database.php              # Database connection settings
│   ├── mail.php                  # Email driver configuration
│   └── ...                       # Other Laravel config files
│
├── database/
│   ├── factories/
│   │   └── UserFactory.php       # Test data factory for users
│   ├── migrations/               # Database schema definitions
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   └── 2026_04_07_054755_create_permission_tables.php
│   └── seeders/
│       ├── DatabaseSeeder.php          # Main seeder — calls RolePermissionSeeder
│       └── RolePermissionSeeder.php    # Creates "user" and "admin" roles
│
├── graphql/
│   └── schema.graphql            # Root GraphQL schema file (imports module schemas)
│
├── resources/
│   └── views/
│       └── emails/               # Blade email templates
│           ├── otp.blade.php
│           ├── password-reset.blade.php
│           └── temp-password.blade.php
│
├── src/                          # Custom modules directory
│   └── Modules/
│       └── Authentication/       # ← THE MAIN MODULE
│           ├── AuthenticationServiceProvider.php  # Registers module services
│           ├── Domain/
│           │   └── Authentication.php            # User entity (model)
│           ├── Application/
│           │   ├── Services/
│           │   │   └── AuthenticationService.php # ALL business logic
│           │   └── Exceptions/
│           │       ├── AuthenticationException.php
│           │       └── BusinessLogicException.php
│           ├── Infrastructure/
│           │   ├── Repositories/
│           │   │   └── AuthenticationRepository.php  # ALL database queries
│           │   └── Database/
│           │       └── migrations/               # Module-specific migrations
│           └── GraphQL/
│               ├── Resolvers/
│               │   └── AuthResolver.php          # GraphQL resolvers
│               ├── Scalars/
│               │   └── JSON.php                  # Custom JSON scalar type
│               ├── inputs.graphql                # Input type definitions
│               ├── types.graphql                 # Type definitions
│               ├── queries.graphql               # Query definitions
│               └── mutations.graphql             # Mutation definitions
│
├── storage/
│   └── logs/
│       └── laravel.log           # Application log (emails appear here in dev)
│
├── .env                          # Environment variables (DB, JWT, Mail settings)
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dependencies (optional)
└── artisan                       # Laravel CLI tool
```

### How the Layers Interact

```
Client (Postman/Frontend)
    │
    ▼
/graphql endpoint
    │
    ▼
GraphQL Schema (inputs.graphql, mutations.graphql, queries.graphql, types.graphql)
    │  Lighthouse validates input with @rules directives
    │  Lighthouse routes to the resolver specified by @field
    ▼
AuthResolver.php (Presentation Layer)
    │  Thin layer — just calls the service
    ▼
AuthenticationService.php (Business Logic Layer)
    │  Contains ALL rules: password generation, 2FA, token issuance
    │  Throws AuthenticationException / BusinessLogicException on errors
    ▼
AuthenticationRepository.php (Data Access Layer)
    │  Contains ALL database queries
    ▼
PostgreSQL Database
```

---

## Building the Authentication Module

This section walks you through creating each file in the module. Follow the order shown.

### Step 1: Domain Layer

The domain layer defines the **User entity** — the core data model for authentication.

Create `src/Modules/Authentication/Domain/Authentication.php`:

```php
<?php

namespace Src\Modules\Authentication\Domain;

use Database\Factories\UserFactory;
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

**Key concepts for beginners:**

| Code                            | What It Does                                                                  |
| ------------------------------- | ----------------------------------------------------------------------------- |
| `extends Authenticatable`       | Makes this model compatible with Laravel's authentication system              |
| `implements JWTSubject`         | Required by JWT Auth — defines how to create tokens for this user             |
| `use HasUuids`                  | Uses UUID strings instead of auto-incrementing integers for the `id` column   |
| `use HasRoles`                  | Adds role management methods like `assignRole()` and `hasRole()`              |
| `protected $table = 'users'`    | Tells Laravel which database table this model uses                            |
| `protected $guard_name = 'api'` | Tells Spatie Permission to use the `api` guard                                |
| `'password' => 'hashed'`        | Automatically hashes passwords when they are set — you never store plain text |
| `protected $hidden`             | These fields are excluded from JSON responses (never expose passwords!)       |

---

### Step 2: Infrastructure Layer

The infrastructure layer handles **database interactions**.

<!-- #### 2a: Module Migrations

Create migration `src/Modules/Authentication/Infrastructure/Database/migrations/2024_01_01_000001_add_two_factor_fields_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->string('otp_code')->nullable()->after('two_factor_enabled');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'otp_code', 'otp_expires_at']);
        });
    }
};
```

Create migration `src/Modules/Authentication/Infrastructure/Database/migrations/2024_01_01_000002_create_password_resets_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
```

Create migration `src/Modules/Authentication/Infrastructure/Database/migrations/2024_01_01_000003_add_is_first_login_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_first_login')->default(true)->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_first_login');
        });
    }
};
``` -->

#### 2b: Repository

Create `src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`:

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

**Key concepts for beginners:**

- `Hash::make($value)` — Creates a one-way hash. You can never reverse it back to the original value.
- `Hash::check($plain, $hashed)` — Compares a plain text value against a hash. Returns true if they match.
- This is why passwords are secure — even if someone steals the database, they cannot read the passwords.
- OTP codes are also hashed for the same reason.

---

### Step 3: Application Layer

The application layer contains **business logic** and **custom exceptions**.

#### 3a: Custom Exceptions

These exception classes implement `ClientAware` which tells Lighthouse to show the error message to the client instead of hiding it behind "Internal server error".

Create `src/Modules/Authentication/Application/Exceptions/AuthenticationException.php`:

```php
<?php

namespace Src\Modules\Authentication\Application\Exceptions;

use GraphQL\Error\ClientAware;

class AuthenticationException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'authentication';
    }
}
```

Create `src/Modules/Authentication/Application/Exceptions/BusinessLogicException.php`:

```php
<?php

namespace Src\Modules\Authentication\Application\Exceptions;

use GraphQL\Error\ClientAware;

class BusinessLogicException extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'business';
    }
}
```

**Why two exception classes?**

| Exception                 | Category         | Used For                                               |
| ------------------------- | ---------------- | ------------------------------------------------------ |
| `AuthenticationException` | `authentication` | Invalid credentials, unauthorized access, token issues |
| `BusinessLogicException`  | `business`       | User not found, invalid OTP, expired tokens            |

The `category` value appears in the GraphQL error response so the frontend knows what type of error occurred.

#### 3b: Authentication Service

Create `src/Modules/Authentication/Application/Services/AuthenticationService.php`:

```php
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
```

---

### Step 4: GraphQL Layer

#### 4a: Custom JSON Scalar

Create `src/Modules/Authentication/GraphQL/Scalars/JSON.php`:

```php
<?php

namespace Src\Modules\Authentication\GraphQL\Scalars;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

class JSON extends ScalarType
{
    public string $name = 'JSON';
    public ?string $description = 'Arbitrary JSON data as a native object.';

    public function serialize($value): mixed
    {
        return $value;
    }

    public function parseValue($value): mixed
    {
        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null): mixed
    {
        if ($valueNode instanceof StringValueNode) {
            return json_decode($valueNode->value, true);
        }

        return null;
    }
}
```

**Why a custom JSON scalar?** GraphQL requires you to define the structure of every response. For mutations like `login` where the response shape varies (with/without OTP), a `JSON` scalar lets us return flexible data without predefining every field.

#### 4b: GraphQL Schema Files

Create `src/Modules/Authentication/GraphQL/inputs.graphql`:

```graphql
"Input for user login credentials"
input LoginInput {
    "User's email address"
    email: String! @rules(apply: ["required", "email"])

    "User's password"
    password: String! @rules(apply: ["required", "min:6"])
}

"Input for admin-only user creation"
input RegisterInput {
    "User's full name"
    name: String! @rules(apply: ["required", "string", "max:255"])

    "User's email address (must be unique)"
    email: String!
        @rules(
            apply: [
                "required"
                "string"
                "email"
                "max:255"
                "unique:users,email"
            ]
        )

    "Enable two-factor authentication (optional, defaults to false)"
    two_factor_enabled: Boolean @rules(apply: ["sometimes", "boolean"])
}

"Input for first-time login password change"
input ChangePasswordInput {
    "New password (minimum 8 characters)"
    password: String!
        @rules(apply: ["required", "string", "min:8", "confirmed"])

    "Password confirmation (must match password)"
    password_confirmation: String!
        @rules(apply: ["required", "string", "min:8"])
}

"Input for OTP verification"
input VerifyOtpInput {
    "User's email address"
    email: String! @rules(apply: ["required", "email"])

    "6-digit OTP code received via email"
    otp: String! @rules(apply: ["required", "string", "size:6"])
}

"Input for resending OTP to a user"
input ResendOtpInput {
    "User's email address"
    email: String! @rules(apply: ["required", "email"])
}

"Input for requesting a password reset"
input ForgotPasswordInput {
    "User's email address"
    email: String! @rules(apply: ["required", "email"])
}

"Input for resetting a password with a valid token"
input ResetPasswordInput {
    "Password reset token received via email"
    token: String! @rules(apply: ["required", "string"])

    "New password (minimum 8 characters)"
    password: String!
        @rules(apply: ["required", "string", "min:8", "confirmed"])

    "Password confirmation (must match password)"
    password_confirmation: String!
        @rules(apply: ["required", "string", "min:8"])
}
```

**Key concept: `@rules` directive.** This is a Lighthouse feature. Instead of writing validation logic in PHP, you define rules right in the GraphQL schema. Lighthouse validates the input before the resolver ever runs. If validation fails, it returns a structured error response automatically.

Create `src/Modules/Authentication/GraphQL/types.graphql`:

```graphql
"""
User Type Definition
Represents the authenticated user entity with all profile fields.
"""
type User {
    id: ID!
    name: String!
    email: String!
    two_factor_enabled: Boolean!
    is_first_login: Boolean!
    created_at: String!
    updated_at: String!
}

"""
Register Response
Returned after an admin successfully creates a new user.
"""
type RegisterResponse {
    user: User!
    message: String!
}

"""
Change Password Response
Returned after a first-time user successfully changes their password.
"""
type ChangePasswordResponse {
    user: User!
    token: String!
    message: String!
}

"""
OTP Verification Response
Returned after successful OTP verification.
"""
type OtpVerificationResponse {
    user: User!
    token: String!
    message: String!
}
```

Create `src/Modules/Authentication/GraphQL/queries.graphql`:

```graphql
type Query {
    """
    Get the currently authenticated user's profile.
    Requires a valid JWT token in the Authorization header.
    """
    me: User!
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@me"
        )
}
```

Create `src/Modules/Authentication/GraphQL/mutations.graphql`:

```graphql
type Mutation {
    "Login a user"
    login(input: LoginInput!): JSON
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@login"
        )

    "Register a new user (Admin only)"
    register(input: RegisterInput!): RegisterResponse
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@register"
        )

    "Change password for first-time login users"
    changePassword(input: ChangePasswordInput!): ChangePasswordResponse
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@changePassword"
        )

    "Verify OTP code sent to user's email"
    verifyOtp(input: VerifyOtpInput!): OtpVerificationResponse
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@verifyOtp"
        )

    "Resend OTP to user's email"
    resendOtp(input: ResendOtpInput!): JSON
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@resendOtp"
        )

    "Request a password reset link via email"
    forgotPassword(input: ForgotPasswordInput!): JSON
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@forgotPassword"
        )

    "Reset password using a valid reset token"
    resetPassword(input: ResetPasswordInput!): JSON
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@resetPassword"
        )

    "Refresh the current JWT token"
    refreshToken: JSON
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@refreshToken"
        )

    "Logout the current user by invalidating their JWT token"
    logout: JSON
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@logout"
        )
}
```

**Key directives explained:**

| Directive                 | Purpose                                                            |
| ------------------------- | ------------------------------------------------------------------ |
| `@guard(with: ["api"])`   | Requires a valid JWT token — unauthenticated requests are rejected |
| `@field(resolver: "...")` | Maps this mutation/query to a specific PHP method                  |
| `@rules(apply: [...])`    | Validates input before the resolver runs                           |

#### 4c: Root Schema

Create `graphql/schema.graphql`:

```graphql
scalar JSON
    @scalar(class: "Src\\Modules\\Authentication\\GraphQL\\Scalars\\JSON")

#import ../src/Modules/Authentication/GraphQL/inputs.graphql
#import ../src/Modules/Authentication/GraphQL/types.graphql
#import ../src/Modules/Authentication/GraphQL/queries.graphql
#import ../src/Modules/Authentication/GraphQL/mutations.graphql
```

This root schema file imports all the module's schema files. `#import` is a Lighthouse feature — it stitches multiple `.graphql` files together.

#### 4d: Resolver

Create `src/Modules/Authentication/GraphQL/Resolvers/AuthResolver.php`:

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

**Key point:** The resolver is intentionally thin. Every method does the same thing: extract the input from `$args` and delegate to the service. No business logic here.

---

### Step 5: Mail System

#### 5a: Mailable Classes

Create `app/Mail/OtpMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Authentication OTP Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: ['otp' => $this->otp, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

Create `app/Mail/PasswordResetMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Password Reset Request');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: ['token' => $this->token, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

Create `app/Mail/TempPasswordMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TempPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tempPassword,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Temporary Password');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temp-password',
            with: ['tempPassword' => $this->tempPassword, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

#### 5b: Email Templates

Create `resources/views/emails/otp.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>OTP Verification</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .otp-code {
                font-size: 32px;
                font-weight: bold;
                color: #2d3748;
                letter-spacing: 8px;
                text-align: center;
                padding: 20px;
                background: #f7fafc;
                border-radius: 8px;
                margin: 20px 0;
            }
            .warning {
                color: #e53e3e;
                font-size: 14px;
                margin-top: 20px;
            }
            h2 {
                color: #2d3748;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Hello, {{ $userName }}!</h2>
            <p>
                You have requested to verify your identity. Please use the
                following OTP code to complete your authentication:
            </p>

            <div class="otp-code">{{ $otp }}</div>

            <p>
                This code is valid for <strong>5 minutes</strong>. Do not share
                this code with anyone.
            </p>

            <p class="warning">
                If you did not request this code, please ignore this email and
                ensure your account is secure.
            </p>

            <p>Thank you,<br />{{ config('app.name') }} Team</p>
        </div>
    </body>
</html>
```

Create `resources/views/emails/password-reset.blade.php`:

```html
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello {{ $userName }},</p>
        <p>
            You have requested to reset your password. Use the token below to
            reset your password:
        </p>
        <p
            style="font-size: 18px; font-weight: bold; background-color: #f4f4f4; padding: 10px; display: inline-block;"
        >
            {{ $token }}
        </p>
        <p>This token is valid for <strong>60 minutes</strong>.</p>
        <p>
            If you did not request a password reset, please ignore this email.
        </p>
        <br />
        <p>Regards,<br />{{ config('app.name') }}</p>
    </body>
</html>
```

Create `resources/views/emails/temp-password.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Your Temporary Password</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .password-code {
                font-size: 24px;
                font-weight: bold;
                color: #2d3748;
                letter-spacing: 4px;
                text-align: center;
                padding: 20px;
                background: #f7fafc;
                border-radius: 8px;
                margin: 20px 0;
            }
            .warning {
                color: #e53e3e;
                font-size: 14px;
                margin-top: 20px;
            }
            h2 {
                color: #2d3748;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Welcome, {{ $userName }}!</h2>
            <p>
                Your account has been created by an administrator. Please use
                the following temporary password to log in:
            </p>

            <div class="password-code">{{ $tempPassword }}</div>

            <p>
                After logging in, you will be required to
                <strong>change your password immediately</strong>.
            </p>

            <p class="warning">
                Do not share this password with anyone. This is a one-time
                temporary password.
            </p>

            <p>Thank you,<br />{{ config('app.name') }} Team</p>
        </div>
    </body>
</html>
```

---

### Step 6: Error Handling

Create `app/GraphQL/ErrorHandlers/SanitizedValidationErrorHandler.php`:

```php
<?php

namespace App\GraphQL\ErrorHandlers;

use Closure;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;

/**
 * Cleans up GraphQL error responses for frontend consumption.
 *
 * Handles:
 * - Lighthouse ValidationException: strips 'input.' prefix, returns field-level errors
 * - ClientAware exceptions: returns the message with the exception's category
 * - Returns clean responses without file paths, line numbers, or stack traces
 * - Short-circuits the error pipeline (bypasses the debug formatter)
 */
class SanitizedValidationErrorHandler implements ErrorHandler
{
    public function __invoke(?Error $error, Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $previous = $error->getPrevious();

        // Handle Lighthouse validation errors (field-level)
        if ($previous instanceof ValidationException) {
            $extensions = $previous->getExtensions();
            $validation = $extensions['validation'] ?? [];

            $cleaned = [];
            foreach ($validation as $field => $messages) {
                $cleanField = preg_replace('/^input\./', '', $field);
                $cleanMessages = array_map(
                    fn (string $msg) => str_replace('input.', '', $msg),
                    $messages,
                );
                $cleaned[$cleanField] = $cleanMessages;
            }

            return [
                'message' => 'Validation failed.',
                'extensions' => [
                    'category' => 'validation',
                    'validation' => $cleaned,
                ],
            ];
        }

        // Handle ClientAware exceptions (authentication, business logic)
        if ($previous instanceof ClientAware && $previous->isClientSafe()) {

            $category = method_exists($previous, 'getCategory')
                ? $previous->getCategory()
                : 'authentication';

            return [
                'message' => $previous->getMessage(),
                'extensions' => [
                    'category' => $category,
                ],
        ];
}

        return $next($error);
    }
}

```

**What this does:** When Lighthouse encounters an error, it passes it through a chain of error handlers. This handler:

1. If it's a **validation error** (from `@rules`): strips the `input.` prefix from field names and returns a clean `validation` category response.
2. If it's a **client-safe exception** (our custom `AuthenticationException` or `BusinessLogicException`): returns the error message with the appropriate category.
3. For anything else: passes to the next handler (which will mask it as "Internal server error" in production).

---

### Step 7: Service Provider

Create `src/Modules/Authentication/AuthenticationServiceProvider.php`:

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

**What this does:**

- `register()` — Tells Laravel how to create the service and repository. Using `singleton` means only one instance is created per request.
- `boot()` — Tells Laravel to look for migration files in the module's own `migrations/` folder (not just `database/migrations/`).

Register this provider in `bootstrap/providers.php`:

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

## Key Features Explained

### 1. Admin-Only User Registration

**How it works:** When the `register` mutation is called, the service first checks if the currently authenticated user has the `admin` role. Non-admins get an `AuthenticationException`.

**Flow:**

```
Admin sends register mutation with JWT token
    → AuthResolver.register()
    → AuthenticationService.registerUser()
        → Checks admin role
        → Generates 16-character temporary password
        → Creates user with is_first_login = true
        → Assigns "user" role
        → Emails temporary password
    → Returns user object + confirmation message
```

### 2. Temporary Password System

When an admin creates a user, the service uses `Str::random(16)` to generate a secure 16-character random string. This password is:

- Stored in the database as a bcrypt hash (never plain text).
- Sent to the user's email via `TempPasswordMail`.
- In development, since `MAIL_MAILER=log`, the email contents (including the password) appear in `storage/logs/laravel.log`.

### 3. First-Time Login Password Change

**Flow:**

```
User logs in with temporary password
    → AuthenticationService.loginUser()
        → Credentials validated ✓
        → is_first_login is true
        → Returns temporary JWT token with first_login claim
        → Response includes is_first_login: true

User calls changePassword mutation with temporary token
    → AuthenticationService.changePassword()
        → Verifies is_first_login is true
        → Updates password
        → Sets is_first_login to false
        → Issues full JWT token
        → User can now access all features
```

### 4. Structured Error Handling

The API returns three categories of errors:

**Validation errors** (input fails `@rules`):

```json
{
    "errors": [
        {
            "message": "Validation failed.",
            "extensions": {
                "category": "validation",
                "validation": {
                    "email": ["The email field is required."],
                    "password": [
                        "The password field must be at least 8 characters."
                    ]
                }
            }
        }
    ]
}
```

**Authentication errors** (invalid credentials, unauthorized):

```json
{
    "errors": [
        {
            "message": "Invalid credentials. Please check your email and password.",
            "extensions": {
                "category": "authentication"
            }
        }
    ]
}
```

**Business logic errors** (expired OTP, user not found):

```json
{
    "errors": [
        {
            "message": "Invalid or expired OTP. Please request a new one.",
            "extensions": {
                "category": "business"
            }
        }
    ]
}
```

### 5. Two-Factor Authentication (2FA)

When a user has `two_factor_enabled: true`, login requires an additional OTP step:

1. User logs in → receives temporary token + OTP sent to email.
2. User submits OTP via `verifyOtp` mutation.
3. If OTP is valid and not expired (5-minute window) → receives full JWT token.

OTP codes are:

- 6 digits, zero-padded (e.g., `003421`).
- Generated using `random_int()` for cryptographic security.
- Stored hashed with `Hash::make()` — even database admins cannot read them.
- Single-use — cleared after verification.

### 6. Password Reset

1. User requests reset via `forgotPassword` (provides email).
2. System generates a 64-character random token, hashes it, stores it with 60-minute expiry.
3. Raw token is emailed to the user.
4. User calls `resetPassword` with the token + new password.
5. System finds the matching hash, updates the password, deletes all tokens for that user.

The `forgotPassword` response is always the same whether the email exists or not — this prevents attackers from discovering valid email addresses.

---

## Running the Project

### Start the Development Server

```bash
php artisan serve
```

The server starts at `http://127.0.0.1:8000`.

The GraphQL endpoint is: `http://127.0.0.1:8000/graphql`

### Compile Frontend Assets (Optional)

```bash
npm run dev    # Watch mode (auto-recompiles on changes)
npm run build  # Production build
```

---

## Testing the API

### Using Postman

1. Open Postman.
2. Create a new **POST** request.
3. Set the URL to: `http://127.0.0.1:8000/graphql`
4. Go to the **Body** tab → select **GraphQL**.
5. Paste your query/mutation in the query field and variables in the variables field.

### Example: Admin Login

```graphql
mutation Login($input: LoginInput!) {
    login(input: $input)
}
```

Variables:

```json
{
    "input": {
        "email": "admin@example.com",
        "password": "your-secure-password"
    }
}
```

Copy the `token` from the response.

### Example: Create a User (Admin Only)

Add header: `Authorization: Bearer <YOUR_ADMIN_TOKEN>`

```graphql
mutation Register($input: RegisterInput!) {
    register(input: $input) {
        user {
            id
            name
            email
            is_first_login
        }
        message
    }
}
```

Variables:

```json
{
    "input": {
        "name": "New User",
        "email": "newuser@example.com"
    }
}
```

### Example: First-Time Login + Change Password

**Step 1:** Login with temporary password (from `storage/logs/laravel.log`):

```graphql
mutation Login($input: LoginInput!) {
    login(input: $input)
}
```

Variables:

```json
{
    "input": {
        "email": "newuser@example.com",
        "password": "<TEMP_PASSWORD_FROM_LOG>"
    }
}
```

**Step 2:** Change password using the temporary token:

Add header: `Authorization: Bearer <TEMP_TOKEN_FROM_STEP_1>`

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

Variables:

```json
{
    "input": {
        "password": "mynewsecurepassword",
        "password_confirmation": "mynewsecurepassword"
    }
}
```

### Example: Get Current User

Add header: `Authorization: Bearer <YOUR_TOKEN>`

```graphql
query Me {
    me {
        id
        name
        email
        two_factor_enabled
        is_first_login
    }
}
```

### Login (with 2FA)

**Operation:**

```graphql
mutation Login($input: LoginInput!) {
    login(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "jane@example.com",
        "password": "password123"
    }
}
```

**Expected Response:**

```json
{
    "data": {
        "login": {
            "user": {
                "id": 2,
                "name": "Jane Doe",
                "email": "jane@example.com"
            },
            "token": "eyJ0eXAi... (temporary token with otp_pending claim)",
            "requires_otp": true,
            "message": "OTP has been sent to your email. Please verify to complete login."
        }
    }
}
```

> **Note:** Since `MAIL_MAILER=log`, the OTP is written to `storage/logs/laravel.log`. In production, configure SMTP to send real emails.

### Resend OTP

If the OTP expires or the user didn't receive it, resend a new one:

**Operation:**

```graphql
mutation ResendOtp($input: ResendOtpInput!) {
    resendOtp(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "jane@example.com"
    }
}
```

**Expected Response:**

```json
{
    "data": {
        "resendOtp": {
            "message": "OTP has been resent to your email."
        }
    }
}
```

### Verify OTP

Check the OTP from `storage/logs/laravel.log` (look for the 6-digit code in the email HTML).

Replace `"123456"` with the actual OTP from the log.

**Operation:**

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

**GraphQL Variables:**

```json
{
    "input": {
        "email": "jane@example.com",
        "otp": "123456"
    }
}
```

**Expected Response:**

```json
{
    "data": {
        "verifyOtp": {
            "user": {
                "id": "2",
                "name": "Jane Doe",
                "email": "jane@example.com"
            },
            "token": "eyJ0eXAi... (full access token)",
            "message": "OTP verified successfully. Login complete."
        }
    }
}
```

### 10. Get Authenticated User (Me Query)

**Authorization:** Bearer Token → `<YOUR_JWT_TOKEN>`

**Operation:**

```graphql
query Me {
    me {
        id
        name
        email
        two_factor_enabled
        created_at
        updated_at
    }
}
```

**GraphQL Variables:**

```json
{}
```

**Expected Response:**

```json
{
    "data": {
        "me": {
            "id": "1",
            "name": "John Doe",
            "email": "john@example.com",
            "two_factor_enabled": false,
            "created_at": "2026-04-07T14:00:00.000000Z",
            "updated_at": "2026-04-07T14:00:00.000000Z"
        }
    }
}
```

### 11. Refresh Token

**Authorization:** Bearer Token → `<YOUR_JWT_TOKEN>`

**Operation:**

```graphql
mutation RefreshToken {
    refreshToken
}
```

**GraphQL Variables:**

```json
{}
```

**Expected Response:**

```json
{
    "data": {
        "refreshToken": {
            "token": "eyJ0eXAi... (new token)",
            "message": "Token refreshed successfully."
        }
    }
}
```

### 12. Forgot Password

Request a password reset token. The token is sent to the user's email.

**Operation:**

```graphql
mutation ForgotPassword($input: ForgotPasswordInput!) {
    forgotPassword(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "john@example.com"
    }
}
```

**Expected Response:**

```json
{
    "data": {
        "forgotPassword": {
            "message": "If this email exists, a password reset link has been sent."
        }
    }
}
```

> **Note:** The response is always the same whether the email exists or not (prevents email enumeration). Since `MAIL_MAILER=log`, the reset token is written to `storage/logs/laravel.log`. Look for the "Password Reset Request" email to find the 64-character token.

### 13. Reset Password

Reset the user's password using the token received via email.

**Operation:**

```graphql
mutation ResetPassword($input: ResetPasswordInput!) {
    resetPassword(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "token": "<TOKEN_FROM_EMAIL_LOG>",
        "password": "newpassword123",
        "password_confirmation": "newpassword123"
    }
}
```

**Expected Response:**

```json
{
    "data": {
        "resetPassword": {
            "message": "Password has been reset successfully."
        }
    }
}
```

> **Note:** After resetting, the user can log in with the new password. The reset token is single-use and expires after 60 minutes.

### 14. Logout

**Authorization:** Bearer Token → `<YOUR_JWT_TOKEN>`

**Operation:**

```graphql
mutation Logout {
    logout
}
```

**GraphQL Variables:**

```json
{}
```

**Expected Response:**

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

## JWT Token Usage

All protected operations require the JWT token in the `Authorization` header:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Token Details

- **TTL (Time To Live):** 60 minutes (configurable via `JWT_TTL` in `.env`)
- **Refresh TTL:** 20160 minutes / 14 days (configurable via `JWT_REFRESH_TTL` in `.env`)
- **Algorithm:** HS256
- **Custom Claims:** `email`, `roles`

---

## Authentication Flow

```
┌─────────────┐     ┌──────────────┐     ┌───────────────────┐     ┌────────────┐
│   Postman    │────→│   /graphql   │────→│   AuthResolver    │────→│  Database   │
│   Client     │     │   Endpoint   │     │  (Thin Resolver)  │     │ PostgreSQL  │
└─────────────┘     └──────────────┘     └───────────────────┘     └────────────┘
                                                  │
                                                  ▼
                                          ┌───────────────────┐
                                          │  Authentication   │
                                          │    Service        │
                                          │  (Business Logic) │
                                          └───────────────────┘
                                                  │
                                                  ▼
                                          ┌───────────────────┐
                                          │  Authentication   │
                                          │   Repository      │
                                          │  (Data Access)    │
                                          └───────────────────┘
```

### Admin-Only Registration Flow

```
1. Admin logs in and obtains JWT token
2. Admin submits name + email via RegisterInput
3. → register mutation → AuthResolver → AuthenticationService
4. → Service verifies admin role (hasRole('admin'))
5. → Temporary password generated (16-char random string)
6. → User created with is_first_login = true
7. → Default "user" role assigned
8. → Temporary password sent to user's email
9. → Response: user object + confirmation message
```

### First-Time Login & Password Change Flow

```
1. User receives temporary password via email
2. User submits email + temporary password via LoginInput
3. → login mutation → AuthResolver → AuthenticationService
4. → Credentials validated against database
5. → is_first_login = true detected
6. → Temporary JWT token issued (with first_login claim)
7. → Response: user + temp token + is_first_login: true
8. User submits new password via ChangePasswordInput
9. → changePassword mutation → AuthResolver → AuthenticationService
10. → Password updated, is_first_login set to false
11. → Full access JWT token issued
12. → User can now access all protected resources
```

### Login with 2FA Flow

```
1. User submits email + password via LoginInput
2. → login mutation → AuthResolver → AuthenticationService
3. → Credentials validated against database
4. → 2FA enabled? YES:
   a. Generate 6-digit OTP
   b. Hash and store OTP (5-min expiry)
   c. Send OTP via email
   d. Return JSON with temporary JWT token + requires_otp: true
5. (Optional) User requests resendOtp if OTP expired
   a. → resendOtp mutation → AuthResolver → AuthenticationService
   b. → New OTP generated and sent
6. User submits OTP via VerifyOtpInput:
   a. → verifyOtp mutation → AuthResolver → AuthenticationService
   b. → OTP verified (timing-safe Hash::check)
   c. → OTP cleared from database
   d. → Full JWT token issued
7. User accesses protected resources with full JWT token
```

---

## Troubleshooting

### Common Issues and Solutions

| Problem                                  | Cause                                              | Solution                                                                              |
| ---------------------------------------- | -------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `SQLSTATE[08006] Connection refused`     | PostgreSQL not running or wrong credentials        | Start PostgreSQL service and verify `DB_*` values in `.env`                           |
| `Base table or view not found`           | Migrations haven't been run                        | Run `php artisan migrate`                                                             |
| `Role [user] does not exist`             | Seeder hasn't been run                             | Run `php artisan db:seed`                                                             |
| `Class "Src\..." not found`              | Autoload not updated after adding `src/` namespace | Run `composer dump-autoload`                                                          |
| `JWT secret not set`                     | JWT secret key not generated                       | Run `php artisan jwt:secret`                                                          |
| `Unauthenticated` on protected mutations | Missing or expired JWT token                       | Add `Authorization: Bearer <token>` header                                            |
| `Internal server error` on login         | Exception not using `ClientAware`                  | Ensure custom exceptions implement `ClientAware` interface                            |
| `Column not found: is_first_login`       | Module migration not run                           | Run `php artisan migrate` (module migrations are auto-loaded by the service provider) |
| Emails not appearing                     | `MAIL_MAILER` not set to `log`                     | Set `MAIL_MAILER=log` in `.env` and check `storage/logs/laravel.log`                  |
| `permission_tables already exist`        | Migrations run twice                               | Run `php artisan migrate:fresh` to reset (WARNING: deletes all data)                  |

### Clearing Caches

If configuration changes don't seem to take effect:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Checking Email Logs

In development, all emails are written to the log file instead of being sent:

```bash
# View the last 50 lines of the log (look for email content)
# Windows:
Get-Content storage/logs/laravel.log -Tail 50

# macOS/Linux:
tail -50 storage/logs/laravel.log
```

---

## Useful Commands Summary

### Artisan Commands

| Command                               | Purpose                                                        |
| ------------------------------------- | -------------------------------------------------------------- |
| `php artisan serve`                   | Start the development server                                   |
| `php artisan migrate`                 | Run database migrations                                        |
| `php artisan migrate:fresh`           | Drop all tables and re-run migrations (WARNING: destroys data) |
| `php artisan migrate:rollback`        | Undo the last batch of migrations                              |
| `php artisan db:seed`                 | Run seeders (creates default roles)                            |
| `php artisan tinker`                  | Open interactive PHP shell (for testing code)                  |
| `php artisan jwt:secret`              | Generate JWT secret key                                        |
| `php artisan config:clear`            | Clear cached configuration                                     |
| `php artisan cache:clear`             | Clear application cache                                        |
| `php artisan route:list`              | List all registered routes                                     |
| `php artisan make:migration <name>`   | Create a new migration file                                    |
| `php artisan make:mail <name>`        | Create a new Mailable class                                    |
| `php artisan test`                    | Run unit and feature tests                                     |
| `php artisan lighthouse:print-schema` | Print the full compiled GraphQL schema                         |

### Composer Commands

| Command                      | Purpose                                                    |
| ---------------------------- | ---------------------------------------------------------- |
| `composer install`           | Install all PHP dependencies                               |
| `composer require <package>` | Add a new PHP package                                      |
| `composer dump-autoload`     | Regenerate the autoloader (needed after adding namespaces) |
| `composer update`            | Update all packages to latest compatible versions          |

### NPM Commands (Optional)

| Command         | Purpose                               |
| --------------- | ------------------------------------- |
| `npm install`   | Install Node.js dependencies          |
| `npm run dev`   | Start Vite dev server with hot reload |
| `npm run build` | Build production frontend assets      |

---

## Tips for Beginners

### 1. How to Add a New Feature/Module

Follow this pattern when adding new functionality:

1. **Create the domain model** in `src/Modules/YourModule/Domain/`.
2. **Create the repository** in `src/Modules/YourModule/Infrastructure/Repositories/`.
3. **Create the service** in `src/Modules/YourModule/Application/Services/`.
4. **Create the GraphQL files** (inputs, types, queries, mutations) in `src/Modules/YourModule/GraphQL/`.
5. **Create the resolver** in `src/Modules/YourModule/GraphQL/Resolvers/`.
6. **Create a service provider** to register your module's dependencies.
7. **Import your schema files** in `graphql/schema.graphql`.
8. **Register your service provider** in `bootstrap/providers.php`.

### 2. GraphQL Best Practices

- **Use Input Types** — Always wrap mutation arguments in an `input` type (e.g., `LoginInput`) rather than listing individual arguments.
- **Use `@rules` for validation** — Let Lighthouse handle input validation in the schema instead of writing manual validation in PHP.
- **Use `@guard` for authentication** — Add `@guard(with: ["api"])` to any query/mutation that requires a logged-in user.
- **Keep resolvers thin** — Resolvers should only call the service and return the result. Never put business logic in a resolver.
- **Use typed responses** — Define return types (like `RegisterResponse`) for predictable responses. Use `JSON` scalar only when the response shape varies.

### 3. Security Best Practices

- **Never store plain-text passwords** — The `'password' => 'hashed'` cast handles this automatically.
- **Never expose sensitive data** — The `$hidden` property on the model prevents passwords and OTP codes from appearing in responses.
- **Use client-safe exceptions** — Only throw exceptions that implement `ClientAware` if you want the message to reach the frontend.
- **Prevent email enumeration** — The `forgotPassword` method returns the same response whether the email exists or not.
- **Hash tokens and OTP codes** — Store them with `Hash::make()` and verify with `Hash::check()`.

### 4. Debugging Tips

- **Check the log file** — `storage/logs/laravel.log` contains error details and email contents.
- **Use Tinker** — `php artisan tinker` lets you test code interactively (query the database, test methods, etc.).
- **Check GraphQL errors** — If you get "Internal server error", your exception likely doesn't implement `ClientAware`. Check the log for the real error.
- **Print the schema** — Run `php artisan lighthouse:print-schema` to see the full compiled GraphQL schema and verify your imports are correct.

---

## Docker Setup

This section explains how to run the entire project inside Docker containers. With Docker, you do **not** need to install PHP, Composer, or PostgreSQL on your host machine — everything runs inside isolated containers.

### Docker Prerequisites

1. **Install Docker Desktop**: Download from [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop) (Windows/macOS) or install Docker Engine on Linux.
2. Verify installation:
    ```bash
    docker --version
    docker compose version
    ```

### Docker Quick Start

```bash
# 1. Clone the project and navigate into it
git clone <your-repo-url>
cd authTest

# 2. Update .env file to have 2 setup of DB for Docker and local
# LOCAL_SETUP
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=authTest
# DB_USERNAME=postgres
# DB_PASSWORD=admin

# DOCKER_SETUP
# DB_CONNECTION=pgsql
# DB_HOST=db
# DB_PORT=5432
# DB_DATABASE=authTest
# DB_USERNAME=postgres
# DB_PASSWORD=admin


# 3. Build and start all containers (app + PostgreSQL)
docker compose up -d --build

# 4. Generate the application key (first time only)
docker compose exec app php artisan key:generate

# 5. Generate the JWT secret (first time only)
docker compose exec app php artisan jwt:secret

# 6. The API is now accessible at:
#    http://localhost:8000/graphql
```

> **Note:** The `docker-compose.yml` sets `RUN_MIGRATIONS=true` and `RUN_SEEDERS=true` by default, so migrations and role seeding run automatically when the container starts.

### Docker Files Overview

The Docker setup consists of four files:

| File                   | Purpose                                                                                            |
| ---------------------- | -------------------------------------------------------------------------------------------------- |
| `Dockerfile`           | Multi-stage build — installs PHP extensions, Composer dependencies, and configures the Laravel app |
| `docker-compose.yml`   | Defines two services (`app` + `db`), networks, volumes, environment variables, and health checks   |
| `docker-entrypoint.sh` | Startup script that waits for the database, runs migrations/seeders, and caches config             |
| `.dockerignore`        | Excludes unnecessary files (node_modules, .git, tests, docs) from the Docker build context         |

#### Dockerfile Explained

The Dockerfile uses a **multi-stage build** for smaller image size:

- **Stage 1 (`vendor`)**: Uses the official `composer:2` image to install PHP dependencies. This stage is discarded after — only the `vendor/` folder is kept.
- **Stage 2 (`app`)**: Uses `php:8.2-cli` with the following extensions installed:
    - `pdo` + `pdo_pgsql` + `pgsql` — PostgreSQL database driver
    - `zip` — Required by Composer
    - `intl` — Internationalization support
    - `mbstring` — Multibyte string support
    - `bcmath` — Arbitrary precision math (used by some packages)
    - `opcache` — PHP bytecode caching for performance

#### docker-compose.yml Explained

**Services:**

| Service | Image                   | Port   | Purpose                             |
| ------- | ----------------------- | ------ | ----------------------------------- |
| `app`   | Built from `Dockerfile` | `8000` | Laravel backend (PHP artisan serve) |
| `db`    | `postgres:16-alpine`    | `5432` | PostgreSQL database                 |

**Volumes:**

| Volume            | Purpose                                                              |
| ----------------- | -------------------------------------------------------------------- |
| `.:/var/www/html` | Bind mount — live code editing (changes on host appear in container) |
| `db-data`         | Named volume — persists database data across container restarts      |
| `app-storage`     | Named volume — persists Laravel storage (logs, cache, sessions)      |

**Environment variables** are all configurable via your `.env` file or by passing them directly. The compose file uses `${VAR:-default}` syntax so it works out of the box without any `.env` file.

#### docker-entrypoint.sh Explained

This script runs _before_ the main application starts:

1. **Waits for the database** — Retries up to 30 times (2-second intervals) until PostgreSQL is accepting connections.
2. **Caches config** — In production/staging, runs `config:cache` and `route:cache` for performance.
3. **Runs migrations** — If `RUN_MIGRATIONS=true` (default), runs `php artisan migrate --force`.
4. **Runs seeders** — If `RUN_SEEDERS=true` (default), runs `php artisan db:seed --force`.
5. **Starts the app** — Executes the CMD from the Dockerfile (`php artisan serve`).

### Common Docker Commands

#### Starting and Stopping

```bash
# Start all services in the background
docker compose up -d

# Start and rebuild images
docker compose up -d --build

# Stop all services
docker compose down

# Stop and remove volumes (WARNING: deletes database data)
docker compose down -v

# Restart a specific service
docker compose restart app
```

#### Running Artisan Commands

```bash
# Run any artisan command
docker compose exec app php artisan <command>

# Examples:
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback
docker compose exec app php artisan db:seed
docker compose exec app php artisan tinker
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan config:clear
docker compose exec app php artisan lighthouse:print-schema
```

#### Viewing Logs

```bash
# View app container logs (stdout)
docker compose logs -f app

# View database container logs
docker compose logs -f db

# View Laravel log file (includes email content when MAIL_MAILER=log)
docker compose exec app cat storage/logs/laravel.log

# Follow Laravel log in real time
docker compose exec app tail -f storage/logs/laravel.log
```

#### Shell Access

```bash
# Open a shell inside the app container
docker compose exec app sh

# Connect to PostgreSQL inside the db container
docker compose exec db psql -U postgres -d authTest
```

#### Creating the First Admin User (via Docker)

```bash
docker compose exec app php artisan tinker
```

Then in the Tinker REPL:

```php
use Src\Modules\Authentication\Domain\Authentication;

$admin = Authentication::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => 'your-secure-password',
    'is_first_login' => false,
]);

$admin->assignRole('admin');
```

### Switching Databases in Docker

The Docker setup uses PostgreSQL by default. To switch to MySQL, update your `.env` or pass environment variables:

**Step 1:** Update `docker-compose.yml` — replace the `db` service:

```yaml
# ---------------------------------------------------------------------------
# PostgreSQL Database
# ---------------------------------------------------------------------------
db:
    image: postgres:16-alpine
    container_name: authtest-db
    restart: unless-stopped
    ports:
        - "${DB_EXTERNAL_PORT:-5432}:5432"
    environment:
        POSTGRES_DB: "${DB_DATABASE:-authTest}"
        POSTGRES_USER: "${DB_USERNAME:-postgres}"
        POSTGRES_PASSWORD: "${DB_PASSWORD:-admin}"
    volumes:
        - db-data:/var/lib/postgresql/data
    healthcheck:
        test:
            [
                "CMD-SHELL",
                "pg_isready -U ${DB_USERNAME:-postgres} -d ${DB_DATABASE:-authTest}",
            ]
        interval: 5s
        timeout: 5s
        retries: 10
        start_period: 10s
    networks:
        - authtest-network
```

**Step 2:** Update environment variables:

```bash
DB_CONNECTION=mysql
DB_PORT=3306
```

**Step 3:** Ensure the Dockerfile installs `pdo_mysql` — add it to the `docker-php-ext-install` line in the Dockerfile.

### Production Considerations

For production deployments, consider these enhancements:

1. **Use PHP-FPM + Nginx** instead of `php artisan serve`:
    - Replace the base image with `php:8.2-fpm`.
    - Add an Nginx service to `docker-compose.yml`.
    - `php artisan serve` is a development server — it handles one request at a time.

2. **Remove bind mounts** — In production, do not mount source code. The code should be baked into the image.

3. **Set `APP_ENV=production`** and `APP_DEBUG=false`.

4. **Use a real mail driver** — Replace `MAIL_MAILER=log` with `smtp`, `ses`, or `mailgun`.

5. **Use Docker secrets** or a vault for sensitive values (`APP_KEY`, `JWT_SECRET`, `DB_PASSWORD`).

6. **Add a queue worker** service if you use queued jobs:

    ```yaml
    queue:
        build: .
        command: php artisan queue:work --sleep=3 --tries=3
        depends_on:
            - app
    ```

7. **Set resource limits** on containers:
    ```yaml
    deploy:
        resources:
            limits:
                memory: 512M
                cpus: "0.5"
    ```

---

## Summary

This project is a **Laravel 12 GraphQL API** implementing a secure authentication system with:

- **Admin-only registration** — No public sign-up. Admins create users and temporary passwords are emailed automatically.
- **First-time login enforcement** — Users must change their temporary password before accessing the system.
- **Two-Factor Authentication** — Optional email-based OTP verification.
- **JWT token authentication** — Stateless API authentication with 60-minute token expiry.
- **Role-based access control** — Spatie Permission manages admin vs. user roles.
- **Structured error handling** — Three error categories (validation, authentication, business) with clean, frontend-friendly responses.
- **Domain-Driven Modular Monolith** — Code is organized into self-contained modules following the Resolver → Service → Repository → Database pattern.

**To get started:**

1. Install PHP 8.2+, Composer, and PostgreSQL.
2. Clone the project and run `composer install`.
3. Configure `.env` with your database credentials.
4. Run `php artisan jwt:secret` to generate a JWT key.
5. Run `php artisan migrate` to create database tables.
6. Run `php artisan db:seed` to create default roles.
7. Create an admin user via `php artisan tinker`.
8. Run `php artisan serve` and test with Postman at `http://127.0.0.1:8000/graphql`.

Follow this guide step by step, and you'll have a fully working authentication backend.
