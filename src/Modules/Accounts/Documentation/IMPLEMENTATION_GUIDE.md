# Accounts Module — Implementation Guide

> **Target Audience:** Developers onboarding into the PipTrek backend project.
> **Stack:** Laravel 11 · Laravel Lighthouse (GraphQL) · Spatie Permissions · JWT Auth · Modular Architecture
> **Reference Module:** `src/Modules/Authentication/` — study this module first before implementing Accounts.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Database Understanding](#2-database-understanding)
3. [Architecture Breakdown](#3-architecture-breakdown)
4. [Step-by-Step Implementation Guide](#4-step-by-step-implementation-guide)
    - [Step 1: Create the Migration](#step-1-create-the-migration)
    - [Step 2: Create the Domain Model](#step-2-create-the-domain-model)
    - [Step 3: Define Relationships](#step-3-define-relationships)
    - [Step 4: Create the Repository](#step-4-create-the-repository)
    - [Step 5: Create the Service Layer](#step-5-create-the-service-layer)
    - [Step 6: Define GraphQL Types](#step-6-define-graphql-types)
    - [Step 7: Create GraphQL Inputs](#step-7-create-graphql-inputs)
    - [Step 8: Create Queries and Mutations](#step-8-create-queries-and-mutations)
    - [Step 9: Implement the Resolver](#step-9-implement-the-resolver)
    - [Step 10: Create the Service Provider](#step-10-create-the-service-provider)
    - [Step 11: Register the Module](#step-11-register-the-module)
    - [Step 12: Register GraphQL Schema Imports](#step-12-register-graphql-schema-imports)
    - [Step 13: Testing Strategy](#step-13-testing-strategy)
5. [File Structure Guide](#5-file-structure-guide)
6. [Best Practices](#6-best-practices)
7. [Frontend Considerations](#7-frontend-considerations)

---

## 1. Overview

### What This Module Does

The **Accounts** module manages trading accounts belonging to authenticated users. Each account represents a trading configuration — including which broker and platform it is on, whether it is a demo or live (real) account, the leverage applied, the starting capital, and the financial target set by the user.

This module is the core data entity of the PipTrek platform. Every trading-related feature (journaling, analytics, reports, performance tracking) will relate back to an `Account` record.

### Business Context

- A single user can own **multiple accounts** (e.g., one demo account for practice, one real account with live funds).
- Each account belongs to a specific **broker** (e.g., IC Markets, Pepperstone) and optionally a **platform** (e.g., MT4, MT5, cTrader).
- The `account_mode` distinguishes between paper trading (`demo`) and live trading (`real`) — this distinction drives many UI and analytics decisions on the frontend.
- `leverage`, `starting_balance`, and `target_amount` are the financial parameters needed to compute metrics like drawdown, risk-to-reward, and goal progress.
- `is_active` allows soft-toggling an account without deleting it.

---

## 2. Database Understanding

### Table: `accounts`

| Column             | Type               | Nullable | Description                                                                                                                                         |
| ------------------ | ------------------ | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`               | `string` (UUID)    | No       | Primary key. UUID string, consistent with the `users` table pattern in this project.                                                                |
| `user_id`          | `string` (UUID)    | No       | Foreign key referencing `users.id`. Establishes ownership — every account must belong to a registered user.                                         |
| `name`             | `string`           | No       | A human-readable label for the account (e.g., "My ICMarkets Real Account"). Allows users to distinguish between accounts.                           |
| `broker`           | `string`           | No       | The name of the broker (e.g., "IC Markets", "Pepperstone"). Stored as a free-text string — no FK constraint to a brokers table at this stage.       |
| `platform`         | `string`           | Yes      | The trading platform used (e.g., "MT4", "MT5", "cTrader"). Nullable because not all brokers require a platform distinction.                         |
| `account_mode`     | `enum [demo,real]` | No       | Indicates whether this is a demo (paper trading) or real (live funds) account. Must be one of the two allowed values.                               |
| `account_type`     | `string`           | Yes      | The account classification offered by the broker (e.g., "Standard", "ECN", "Raw Spread"). Nullable because not all brokers expose this distinction. |
| `leverage`         | `decimal(8,2)`     | No       | The leverage ratio (e.g., `100.00` for 1:100). Uses precision `[8,2]` — up to 6 digits before the decimal.                                          |
| `starting_balance` | `decimal(15,2)`    | No       | The initial deposit or opening balance of the account. High precision `[15,2]` supports large institutional balances.                               |
| `target_amount`    | `decimal(15,2)`    | No       | The monetary goal the user is targeting (e.g., double the starting balance). Used for progress tracking and analytics.                              |
| `is_active`        | `boolean`          | No       | Soft-toggle flag. `true` means the account is actively tracked; `false` means it is archived/paused. Defaults to `true`.                            |

### Relationships

- **`accounts.user_id` → `users.id`**: A `BelongsTo` relationship. An account is owned by exactly one user. When a user is deleted, cascade behaviour should be defined (typically `onDelete('cascade')` so orphaned accounts are removed).
- **Future relationships** (not in this table, but expected as the platform grows):
    - `accounts` ← `trades` (`HasMany`): Each account will have many trade records.
    - `accounts` ← `journals` (`HasMany`): Each account may have associated journal entries.

### Enum: `account_mode`

The `account_mode` column is a strict enum with only two valid values:

| Value  | Meaning                                         |
| ------ | ----------------------------------------------- |
| `demo` | Paper/simulated trading. No real money at risk. |
| `real` | Live trading with real funds.                   |

This distinction is critical for the frontend — it must clearly differentiate demo vs. real accounts in the UI to avoid confusion.

---

## 3. Architecture Breakdown

This project follows a **modular, layered architecture**. Each module is self-contained and lives under `src/Modules/{ModuleName}/`. The layers within a module are:

```
Domain          → Eloquent model (the data entity)
Infrastructure  → Repository (database access) + Migrations
Application     → Service (business logic) + Exceptions
GraphQL         → Types, Inputs, Queries, Mutations, Resolver
```

Here is where each part of the Accounts module belongs:

### 3.1 Domain Model

**Path:** `src/Modules/Accounts/Domain/Account.php`

The Eloquent model that maps to the `accounts` table. It declares:

- `$fillable` columns
- `$casts` for proper type handling (`boolean`, `decimal`→`float`, `enum`)
- `$hidden` for any fields that should never appear in API responses
- The `BelongsTo` relationship to `Authentication` (the user)
- UUID configuration (`HasUuids` trait, `$incrementing = false`, `$keyType = 'string'`)

### 3.2 Repository

**Path:** `src/Modules/Accounts/Infrastructure/Repositories/AccountRepository.php`

The only class allowed to interact with the database directly. It contains:

- `createAccount(array $data): Account`
- `findAccountById(string $id): ?Account`
- `findAccountsByUserId(string $userId): array`
- `updateAccount(Account $account, array $data): Account`
- `deleteAccount(Account $account): void`
- `findActiveAccountsByUserId(string $userId): array`

The service layer calls the repository — it does **not** call Eloquent directly.

### 3.3 Service

**Path:** `src/Modules/Accounts/Application/Services/AccountService.php`

Contains all business logic:

- Authorization checks (does the authenticated user own this account?)
- Orchestrating repository calls
- Returning structured arrays for the resolver to pass to GraphQL

The service receives the `AccountRepository` via constructor injection (registered in the service provider).

### 3.4 Exception

**Path:** `src/Modules/Accounts/Application/Exceptions/AccountException.php`

A custom exception implementing `GraphQL\Error\ClientAware`. This ensures that business errors (e.g., "Account not found", "Unauthorized") are returned as clean GraphQL errors rather than unhandled 500 responses. Follow the exact same pattern as `AuthenticationException`.

### 3.5 GraphQL Schema Files

**Path:** `src/Modules/Accounts/GraphQL/`

| File                | Purpose                                                                                                                                 |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| `types.graphql`     | Defines the `Account` type and any response wrapper types (e.g., `AccountResponse`, `DeleteAccountResponse`)                            |
| `inputs.graphql`    | Defines input types for mutations (`CreateAccountInput`, `UpdateAccountInput`)                                                          |
| `queries.graphql`   | Defines the `Query` type extension with account queries (`account`, `myAccounts`)                                                       |
| `mutations.graphql` | Defines the `Mutation` type extension with account mutations (`createAccount`, `updateAccount`, `deleteAccount`, `toggleAccountActive`) |

### 3.6 Resolver

**Path:** `src/Modules/Accounts/GraphQL/Resolvers/AccountResolver.php`

A thin class that acts as the bridge between GraphQL and the service layer. Each public method corresponds to one query or mutation field. It:

- Extracts arguments from `$args` (using `$args['input'] ?? $args` pattern)
- Delegates to `AccountService`
- Returns what the service returns

Resolvers must **not** contain business logic.

### 3.7 Service Provider

**Path:** `src/Modules/Accounts/AccountsServiceProvider.php`

Registers all module dependencies into the Laravel IoC container as singletons and loads module migrations. This is the entry point for the module. It must be added to `bootstrap/providers.php`.

### 3.8 Validation

Validation is applied **inline in GraphQL inputs** using Lighthouse's `@rules` directive (see `inputs.graphql` in the Authentication module for reference). This is the established pattern in this project — there are no separate FormRequest classes.

Rules to consider for Accounts:

- `name`: `required|string|max:255`
- `broker`: `required|string|max:255`
- `platform`: `nullable|string|max:100`
- `account_mode`: `required|in:demo,real`
- `account_type`: `nullable|string|max:100`
- `leverage`: `required|numeric|min:1`
- `starting_balance`: `required|numeric|min:0`
- `target_amount`: `required|numeric|min:0`
- `is_active`: `boolean`

### 3.9 DTOs

This project does not currently use formal DTO classes. Data is passed as plain associative arrays between layers. Follow the existing convention — use `array $data` parameters throughout.

---

## 4. Step-by-Step Implementation Guide

### Step 1: Create the Migration

**Location:** `src/Modules/Accounts/Infrastructure/Database/migrations/`

The migration is discovered automatically when the service provider calls `$this->loadMigrationsFrom()`.

**What the migration must do:**

- Create the `accounts` table with all columns as described in the schema.
- Use `$table->uuid('id')->primary()` — consistent with the UUID primary key pattern in this project.
- Use `$table->foreignUuid('user_id')->constrained('users')->onDelete('cascade')` to enforce the FK constraint and clean up orphaned records.
- Cast `account_mode` as an enum with `$table->enum('account_mode', ['demo', 'real'])`.
- Use `$table->decimal('leverage', 8, 2)`, `$table->decimal('starting_balance', 15, 2)`, `$table->decimal('target_amount', 15, 2)` with the correct precision and scale.
- Set `$table->boolean('is_active')->default(true)`.
- Include `$table->timestamps()` for `created_at` and `updated_at`.
- The `down()` method must call `Schema::dropIfExists('accounts')`.

**Migration filename convention:** Use a timestamp prefix, e.g.:
`2026_04_16_000001_create_accounts_table.php`

---

### Step 2: Create the Domain Model

**Location:** `src/Modules/Accounts/Domain/Account.php`

**What the model must include:**

- `namespace Src\Modules\Accounts\Domain;`
- Extend `Illuminate\Database\Eloquent\Model` (not `Authenticatable` — this is not an auth model).
- Use the `HasUuids` and `HasFactory` traits.
- Set `protected $table = 'accounts'`.
- Set `public $incrementing = false` and `protected $keyType = 'string'` for UUID support.
- Define `$fillable` with all writeable columns: `name`, `broker`, `platform`, `account_mode`, `account_type`, `leverage`, `starting_balance`, `target_amount`, `is_active`, `user_id`.
- Define `$casts`:
    - `is_active` → `'boolean'`
    - `leverage` → `'float'` (or `'decimal:2'` for strict formatting)
    - `starting_balance` → `'float'`
    - `target_amount` → `'float'`
- Do **not** add `$hidden` unless there is a field that must never be exposed — all account fields are safe to return.

---

### Step 3: Define Relationships

**In `Account.php` (Domain model):**

- `user()` — A `BelongsTo` relationship pointing to `Src\Modules\Authentication\Domain\Authentication::class`. Use `return $this->belongsTo(Authentication::class, 'user_id')`.

**In `Authentication.php` (Domain model, the existing user model):**

- `accounts()` — A `HasMany` relationship: `return $this->hasMany(\Src\Modules\Accounts\Domain\Account::class, 'user_id')`.
- Add this relationship carefully without breaking existing functionality. The `Authentication` model already uses `HasRoles` — simply add the new method alongside existing ones.

**Eager loading guidance:**

- When returning a list of accounts, avoid lazy loading by using `with('user')` only if the query explicitly needs user data alongside accounts.
- Most account queries will be scoped to the authenticated user (i.e., `WHERE user_id = ?`), so the user relationship is rarely needed in responses — skip eager loading by default unless a specific query requires it.

---

### Step 4: Create the Repository

**Location:** `src/Modules/Accounts/Infrastructure/Repositories/AccountRepository.php`

**Namespace:** `Src\Modules\Accounts\Infrastructure\Repositories`

**Methods to implement:**

| Method Signature                                        | Description                                                                                                                      |
| ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `createAccount(array $data): Account`                   | Inserts a new account row and returns the Eloquent instance.                                                                     |
| `findAccountById(string $id): ?Account`                 | Finds a single account by its UUID. Returns `null` if not found.                                                                 |
| `findAccountsByUserId(string $userId): array`           | Returns all accounts for a given user, ordered by `created_at` descending. Return as `array` (call `->all()` on the collection). |
| `findActiveAccountsByUserId(string $userId): array`     | Same as above but scoped to `is_active = true`.                                                                                  |
| `updateAccount(Account $account, array $data): Account` | Calls `$account->update($data)` and returns the refreshed model (`$account->fresh()`).                                           |
| `deleteAccount(Account $account): void`                 | Calls `$account->delete()`.                                                                                                      |

**Important notes:**

- Always type-hint `Account` from the Domain namespace.
- Never call `Account::create()` directly from the service — always go through the repository.
- When returning lists, use `->get()->all()` to return a plain PHP array (consistent with `AuthenticationRepository::getAllRegistrationRequests()`).

---

### Step 5: Create the Service Layer

**Location:** `src/Modules/Accounts/Application/Services/AccountService.php`

**Namespace:** `Src\Modules\Accounts\Application\Services`

**Constructor:** Inject `AccountRepository` and `AuthenticationService` (to get the authenticated user).

```
AccountService
  └── __construct(AccountRepository $repository, AuthenticationService $authService)
```

**Methods to implement:**

| Method                                          | Description                                                                                                                                     |
| ----------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `createAccount(array $data): array`             | Get the authenticated user, attach `user_id`, delegate to `$repository->createAccount()`. Return `['account' => $account, 'message' => '...']`. |
| `getMyAccounts(): array`                        | Get the authenticated user's ID, delegate to `$repository->findAccountsByUserId()`. Return the accounts array directly.                         |
| `getAccount(string $id): Account`               | Find by ID, verify the authenticated user owns the account (throw `AccountException` if not), return the model.                                 |
| `updateAccount(string $id, array $data): array` | Find account, verify ownership, delegate to `$repository->updateAccount()`. Return updated account + message.                                   |
| `deleteAccount(string $id): array`              | Find account, verify ownership, delegate to `$repository->deleteAccount()`. Return `['message' => '...']`.                                      |
| `toggleAccountActive(string $id): array`        | Find account, verify ownership, flip `is_active`, call update. Return updated account + message.                                                |

**Ownership check pattern** (use this consistently in all methods that operate on a specific account):

```
$user = $this->authService->getAuthenticatedUser();
if ($account->user_id !== $user->id) {
    throw new AccountException('Unauthorized. You do not own this account.');
}
```

---

### Step 6: Define GraphQL Types

**Location:** `src/Modules/Accounts/GraphQL/types.graphql`

**Types to define:**

**`Account` type** — mirrors the database table columns. All decimal fields should be typed as `Float`. The `account_mode` enum should be typed as `String` (Lighthouse enums require additional configuration; using `String` is simpler and consistent with how `status` is handled in `UserRegistrationRequest`).

**Expected fields:**

```
id, user_id, name, broker, platform, account_mode, account_type,
leverage, starting_balance, target_amount, is_active, created_at, updated_at
```

**Response wrapper types** — follow the pattern established in `Authentication/GraphQL/types.graphql`:

| Type Name               | Fields                                  | Used By                                                 |
| ----------------------- | --------------------------------------- | ------------------------------------------------------- |
| `AccountResponse`       | `account: Account!`, `message: String!` | `createAccount`, `updateAccount`, `toggleAccountActive` |
| `DeleteAccountResponse` | `message: String!`                      | `deleteAccount`                                         |

---

### Step 7: Create GraphQL Inputs

**Location:** `src/Modules/Accounts/GraphQL/inputs.graphql`

**Inputs to define:**

**`CreateAccountInput`** — all required fields for creating an account. Apply `@rules` directives inline. Required fields: `name`, `broker`, `account_mode`, `leverage`, `starting_balance`, `target_amount`. Optional fields: `platform`, `account_type`. Do not include `user_id` or `is_active` in the input — `user_id` is derived from the JWT token and `is_active` defaults to `true`.

**`UpdateAccountInput`** — same fields as `CreateAccountInput` but all marked as nullable/optional using `sometimes` rule, because partial updates should be supported. Also include `is_active: Boolean`.

**Rule examples to include:**

- `account_mode`: `@rules(apply: ["required", "in:demo,real"])`
- `leverage`: `@rules(apply: ["required", "numeric", "min:1"])`
- `starting_balance`: `@rules(apply: ["required", "numeric", "min:0"])`
- `target_amount`: `@rules(apply: ["required", "numeric", "min:0"])`

---

### Step 8: Create Queries and Mutations

**Location:**

- `src/Modules/Accounts/GraphQL/queries.graphql`
- `src/Modules/Accounts/GraphQL/mutations.graphql`

**Queries to define:**

| Field Name   | Arguments | Return Type   | Auth     | Resolver Method              |
| ------------ | --------- | ------------- | -------- | ---------------------------- |
| `myAccounts` | _(none)_  | `[Account!]!` | `@guard` | `AccountResolver@myAccounts` |
| `account`    | `id: ID!` | `Account!`    | `@guard` | `AccountResolver@account`    |

**Mutations to define:**

| Field Name            | Arguments                               | Return Type              | Auth     | Resolver Method                       |
| --------------------- | --------------------------------------- | ------------------------ | -------- | ------------------------------------- |
| `createAccount`       | `input: CreateAccountInput!`            | `AccountResponse!`       | `@guard` | `AccountResolver@createAccount`       |
| `updateAccount`       | `id: ID!`, `input: UpdateAccountInput!` | `AccountResponse!`       | `@guard` | `AccountResolver@updateAccount`       |
| `deleteAccount`       | `id: ID!`                               | `DeleteAccountResponse!` | `@guard` | `AccountResolver@deleteAccount`       |
| `toggleAccountActive` | `id: ID!`                               | `AccountResponse!`       | `@guard` | `AccountResolver@toggleAccountActive` |

**All operations require `@guard(with: ["api"])`** because accounts are private user data.

**Resolver path pattern** (follow exactly the Authentication module's convention):

```
"Src\\Modules\\Accounts\\GraphQL\\Resolvers\\AccountResolver@methodName"
```

---

### Step 9: Implement the Resolver

**Location:** `src/Modules/Accounts/GraphQL/Resolvers/AccountResolver.php`

**Namespace:** `Src\Modules\Accounts\GraphQL\Resolvers`

**Constructor:** Inject `AccountService` only. The resolver has no business logic of its own.

**Method signatures:**

```
public function myAccounts($_, array $args): array
public function account($_, array $args): Account
public function createAccount($_, array $args): array
public function updateAccount($_, array $args): array
public function deleteAccount($_, array $args): array
public function toggleAccountActive($_, array $args): array
```

**Argument extraction pattern** (from the existing codebase):

- For mutations with an `input` object: `$args['input'] ?? $args`
- For queries/mutations with a direct `id` argument: `$args['id']`
- For `updateAccount`, pass both: `$this->accountService->updateAccount($args['id'], $args['input'] ?? $args)`

---

### Step 10: Create the Service Provider

**Location:** `src/Modules/Accounts/AccountsServiceProvider.php`

**Namespace:** `Src\Modules\Accounts`

Model this exactly on `src/Modules/Authentication/AuthenticationServiceProvider.php`.

**The `register()` method must bind as singletons:**

1. `AccountRepository` — no dependencies
2. `AccountService` — depends on `AccountRepository` and `AuthenticationService`

**Note on `AuthenticationService` dependency:** Since `AuthenticationService` is already registered as a singleton by `AuthenticationServiceProvider`, you can safely resolve it via `$app->make(AuthenticationService::class)` inside the `AccountService` singleton binding.

**The `boot()` method must call:**

```php
$this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
```

---

### Step 11: Register the Module

**Location:** `bootstrap/providers.php`

Add `Src\Modules\Accounts\AccountsServiceProvider::class` to the providers array. The order matters — `AccountsServiceProvider` must come **after** `AuthenticationServiceProvider` because it depends on `AuthenticationService` being available in the container.

**Expected result:**

```php
return [
    App\Providers\AppServiceProvider::class,
    Src\Modules\Authentication\AuthenticationServiceProvider::class,
    Src\Modules\Accounts\AccountsServiceProvider::class,
];
```

---

### Step 12: Register GraphQL Schema Imports

**Location:** `graphql/schema.graphql`

Add `#import` directives for all four Accounts GraphQL files. Place them after the Authentication imports.

**Expected result:**

```
#import ../src/Modules/Authentication/GraphQL/inputs.graphql
#import ../src/Modules/Authentication/GraphQL/types.graphql
#import ../src/Modules/Authentication/GraphQL/queries.graphql
#import ../src/Modules/Authentication/GraphQL/mutations.graphql

#import ../src/Modules/Accounts/GraphQL/inputs.graphql
#import ../src/Modules/Accounts/GraphQL/types.graphql
#import ../src/Modules/Accounts/GraphQL/queries.graphql
#import ../src/Modules/Accounts/GraphQL/mutations.graphql
```

**Important:** The `types.graphql` import must come before `queries.graphql` and `mutations.graphql`, because queries and mutations reference the types.

---

### Step 13: Testing Strategy

**Test location:** `tests/Feature/` for integration tests, `tests/Unit/` for service/repository unit tests.

#### Unit Tests

**`AccountServiceTest`** — test the service layer in isolation by mocking `AccountRepository` and `AuthenticationService`:

- `test_create_account_attaches_authenticated_user_id` — verify `user_id` is automatically set from the JWT user.
- `test_get_account_throws_exception_when_user_does_not_own_it` — pass an account owned by a different user; assert `AccountException` is thrown.
- `test_delete_account_calls_repository_delete` — mock repository, assert `deleteAccount()` is called once.
- `test_toggle_account_flips_is_active_flag` — provide an active account, call toggle, assert it is now inactive.

**`AccountRepositoryTest`** — test database interactions using a test database (use `RefreshDatabase` trait):

- `test_create_account_persists_to_database`
- `test_find_accounts_by_user_id_returns_only_that_users_accounts`
- `test_find_active_accounts_excludes_inactive`

#### Feature Tests (GraphQL)

Use Lighthouse's built-in test helpers or raw GraphQL query strings via `$this->graphQL(...)`.

- `test_my_accounts_returns_authenticated_users_accounts_only`
- `test_create_account_requires_authentication`
- `test_create_account_with_valid_input_returns_account`
- `test_create_account_validates_account_mode_enum`
- `test_update_account_by_non_owner_returns_error`
- `test_delete_account_removes_record`
- `test_toggle_account_active_changes_is_active`

#### Testing the `account_mode` Enum Validation

Since `account_mode` only accepts `demo` or `real`, include a test that sends an invalid value (e.g., `"live"`) and asserts a validation error is returned in the GraphQL errors array.

---

## 5. File Structure Guide

The following is the complete expected file and folder layout for the Accounts module, modelled directly on the Authentication module structure:

```
src/
└── Modules/
    └── Accounts/
        ├── AccountsServiceProvider.php          # Module entry point — registers bindings & migrations
        │
        ├── Documentation/
        │   └── IMPLEMENTATION_GUIDE.md          # This file
        │
        ├── Domain/
        │   └── Account.php                      # Eloquent model for the accounts table
        │
        ├── Application/
        │   ├── Exceptions/
        │   │   └── AccountException.php         # ClientAware GraphQL exception
        │   └── Services/
        │       └── AccountService.php           # Business logic layer
        │
        ├── GraphQL/
        │   ├── inputs.graphql                   # Input type definitions (CreateAccountInput, etc.)
        │   ├── types.graphql                    # Output type definitions (Account, AccountResponse, etc.)
        │   ├── queries.graphql                  # Query type: myAccounts, account
        │   ├── mutations.graphql                # Mutation type: createAccount, updateAccount, etc.
        │   └── Resolvers/
        │       └── AccountResolver.php          # Resolver — thin bridge to AccountService
        │
        └── Infrastructure/
            ├── Database/
            │   └── migrations/
            │       └── 2026_04_16_000001_create_accounts_table.php
            └── Repositories/
                └── AccountRepository.php        # Database access layer
```

### Where New Files Should NOT Go

- Do **not** put account logic in `app/Http/Controllers/` — this project uses GraphQL resolvers, not REST controllers.
- Do **not** put the Eloquent model in `app/Models/` — domain models live in `src/Modules/{Module}/Domain/`.
- Do **not** put migrations in `database/migrations/` — module migrations live inside the module under `Infrastructure/Database/migrations/` and are auto-loaded by the service provider.
- Do **not** modify `graphql/schema.graphql` for type definitions — only add `#import` lines there. All types live in their module's GraphQL folder.

---

## 6. Best Practices

### Separation of Concerns

| Layer            | Responsibility                                                 | Must NOT                                    |
| ---------------- | -------------------------------------------------------------- | ------------------------------------------- |
| **Resolver**     | Extract args, call service, return result                      | Contain business logic, touch DB directly   |
| **Service**      | Orchestrate business rules, auth checks, coordinate repository | Query DB directly, know about GraphQL       |
| **Repository**   | Execute DB queries, return models or arrays                    | Contain business logic, know about services |
| **Domain Model** | Declare schema, casts, relationships                           | Contain query logic, know about services    |

### Naming Conventions

- Model: singular PascalCase → `Account`
- Repository: model name + `Repository` → `AccountRepository`
- Service: model name + `Service` → `AccountService`
- Resolver: model name + `Resolver` → `AccountResolver`
- ServiceProvider: module name + `ServiceProvider` → `AccountsServiceProvider`
- GraphQL types: PascalCase → `Account`, `AccountResponse`, `CreateAccountInput`
- GraphQL fields: camelCase → `myAccounts`, `createAccount`, `toggleAccountActive`

### UUID Primary Keys

All models in this project use UUIDs as primary keys (`HasUuids` trait). When creating accounts, do **not** manually generate a UUID — the `HasUuids` trait handles this automatically on `Model::create()`.

### Decimal Handling

- Store `leverage`, `starting_balance`, and `target_amount` as `decimal` in the database.
- Cast them to `float` in the model's `$casts` array.
- GraphQL types should declare them as `Float`.
- Never perform financial arithmetic in the service layer using raw floats — if precision math is needed later, use the `brick/math` library already present in this project's vendor directory.

### Authorization Pattern

Always verify ownership in the service layer before performing any write operation:

```
1. Get the authenticated user via AuthenticationService::getAuthenticatedUser()
2. Load the account from the repository
3. Compare account->user_id === user->id
4. Throw AccountException if mismatch
5. Proceed with operation
```

This pattern must be applied to `getAccount`, `updateAccount`, `deleteAccount`, and `toggleAccountActive`.

### Error Handling

- Throw `AccountException` for business logic errors (not found, unauthorized, invalid state).
- Let Lighthouse's error handling surface these as clean GraphQL errors.
- Do **not** return `null` on error — always throw the exception so the client receives a structured error response.

### Avoid N+1 Queries

- If a query returns a list of accounts and needs the owning user's data, use `->with('user')` in the repository method — do not rely on lazy loading.
- The `myAccounts` query is always scoped to the authenticated user, so the `user` relationship is not needed — skip eager loading for this query.

---

## 7. Frontend Considerations

### What the Frontend Needs

The frontend (Vue.js with Vuex/Pinia) requires account data to:

1. **Display a list of accounts** — the `myAccounts` query provides all accounts belonging to the logged-in user.
2. **Distinguish demo vs. real accounts** — use `account_mode` to render different UI badges, colors, or sections.
3. **Track progress toward the target** — `starting_balance` and `target_amount` are the values needed to compute and render a progress bar or percentage.
4. **Filter active/inactive accounts** — use `is_active` to hide or grey out archived accounts.
5. **Show account details** — `broker`, `platform`, `account_type`, and `leverage` are displayed on the account detail screen.

### Expected API Responses

#### `myAccounts` Query

```json
{
    "data": {
        "myAccounts": [
            {
                "id": "uuid-here",
                "name": "My ICM Real Account",
                "broker": "IC Markets",
                "platform": "MT5",
                "account_mode": "real",
                "account_type": "Raw Spread",
                "leverage": 100.0,
                "starting_balance": 5000.0,
                "target_amount": 10000.0,
                "is_active": true,
                "created_at": "2026-04-16T00:00:00.000000Z",
                "updated_at": "2026-04-16T00:00:00.000000Z"
            }
        ]
    }
}
```

#### `createAccount` Mutation

```json
{
    "data": {
        "createAccount": {
            "account": {
                "id": "uuid-here",
                "name": "Demo Practice Account",
                "broker": "Pepperstone",
                "platform": "MT4",
                "account_mode": "demo",
                "account_type": null,
                "leverage": 200.0,
                "starting_balance": 10000.0,
                "target_amount": 15000.0,
                "is_active": true,
                "created_at": "2026-04-16T00:00:00.000000Z",
                "updated_at": "2026-04-16T00:00:00.000000Z"
            },
            "message": "Account created successfully."
        }
    }
}
```

#### `deleteAccount` Mutation

```json
{
    "data": {
        "deleteAccount": {
            "message": "Account deleted successfully."
        }
    }
}
```

#### Error Response (e.g., unauthorized)

```json
{
    "errors": [
        {
            "message": "Unauthorized. You do not own this account.",
            "extensions": {
                "category": "account"
            }
        }
    ],
    "data": {
        "deleteAccount": null
    }
}
```

### Frontend State Management (Pinia/Vuex) Recommendations

- Store `accounts` as an array in a dedicated `accountsStore`.
- After `createAccount`, push the returned account into the store — avoid a full refetch.
- After `deleteAccount`, filter the account out of the store by ID.
- After `toggleAccountActive`, find and update the `is_active` field in the store.
- Use `account_mode` as a filter option in the UI — maintain a `selectedMode` ref (`'all' | 'demo' | 'real'`) and compute filtered accounts from it.
- Store the selected `accountId` in state to enable a "currently viewed account" context for sub-features like trade journaling.

### GraphQL Query Examples for Frontend

**Fetch all accounts:**

```graphql
query MyAccounts {
    myAccounts {
        id
        name
        broker
        platform
        account_mode
        account_type
        leverage
        starting_balance
        target_amount
        is_active
        created_at
    }
}
```

**Create an account:**

```graphql
mutation CreateAccount($input: CreateAccountInput!) {
    createAccount(input: $input) {
        account {
            id
            name
            account_mode
            is_active
        }
        message
    }
}
```

**Toggle active status:**

```graphql
mutation ToggleAccountActive($id: ID!) {
    toggleAccountActive(id: $id) {
        account {
            id
            is_active
        }
        message
    }
}
```

---

_Last updated: April 16, 2026_
_Module: Accounts | Author: Backend Team_
