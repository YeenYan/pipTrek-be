# Authentication Module — Documentation (Part 2)

> **Continued from [DOCUMENTATION.md](DOCUMENTATION.md)**
> This file extends the main documentation with a detailed Code Walkthrough explaining **how user registration requests work internally**, line by line.

---

## 12. Code Walkthrough — User Registration Requests

This section explains the **internal mechanics** of the User Registration Request feature in a very beginner-friendly way. We assume **zero** prior knowledge. Every piece of code shown here is the **complete, unabridged** source — nothing is summarized or shortened.

---

### 12.1 How Email Matching Works

#### The Problem

When an admin creates a new user, the system needs to check: _"Did this person previously submit a registration request?"_ If yes, that old request should be updated to reflect that an account was created.

The system uses **email** as the identifier to connect a registration request to a newly created user. Here is why:

- Email is **unique** — no two users can share the same email address.
- Email is the **common field** that exists in both the `user_registration_requests` table and the `users` table.
- When someone submits a request, they provide their email. When an admin creates an account, they also provide the email. The email is the bridge between these two events.

Think of it like a library's waiting list. You write your name and email on a "request for membership" form. Later, the librarian creates your membership card using your email. The library then matches the waiting list entry to your new card because the **email is the same on both**.

#### Where the Matching Happens — The Repository Layer

The actual database query that finds the matching request lives in the **repository**. This is the file that talks directly to the database.

**File:** `src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`

Here is the **complete method** that does the matching and updating:

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

**Line-by-line explanation:**

1. **`public function markRegistrationRequestAsCreated(string $email, string $userId): bool`**
    - This method accepts two things: the **email** of the new user (a text string) and the **user_id** (the UUID of the newly created user account).
    - It returns a **boolean** (`true` or `false`) — `true` if a matching request was found and updated, `false` if no matching request existed.

2. **`$affected = DB::table('user_registration_requests')`**
    - `DB::table(...)` tells Laravel: _"I want to work with the `user_registration_requests` table in the database."_
    - This does NOT use the Eloquent ORM (model classes). It uses Laravel's **Query Builder** directly, because the `user_registration_requests` table does not have a dedicated model class.

3. **`->where('email', $email)`**
    - This adds a filter: _"Only look at rows where the `email` column matches the `$email` value I passed in."_
    - In SQL terms, this becomes: `WHERE email = 'john@example.com'`

4. **`->where('status', 'pending')`**
    - This adds a second filter: _"AND the `status` column must be `'pending'`."_
    - This is critical. We only want to update requests that are **still waiting**. If a request was already marked as `'created'`, we don't touch it again.
    - In SQL terms, the combined WHERE clause becomes: `WHERE email = 'john@example.com' AND status = 'pending'`

5. **`->update([...])`**
    - This tells the database: _"For all rows that match both WHERE conditions, change these columns."_
    - The columns being changed are:
        - `'status' => 'created'` — changes the status from `pending` to `created`
        - `'user_id' => $userId` — stores the UUID of the newly created user account
        - `'updated_at' => Carbon::now()` — records the exact timestamp of when this update happened
    - The `->update()` method returns an **integer** — the number of rows that were changed.

6. **`return $affected > 0;`**
    - If `$affected` is `1` (or more), it means a matching pending request was found and updated. Return `true`.
    - If `$affected` is `0`, it means no matching pending request existed. Return `false`.
    - The calling code (the service layer) doesn't actually check this return value — it's there for potential future use or testing.

#### What the SQL Query Looks Like

When this PHP code runs, Laravel translates it into this SQL:

```sql
UPDATE user_registration_requests
SET status = 'created',
    user_id = '550e8400-e29b-41d4-a716-446655440000',
    updated_at = '2026-04-15 14:30:00'
WHERE email = 'john@example.com'
  AND status = 'pending';
```

#### Why Only `'pending'` Requests Match

The `->where('status', 'pending')` filter is intentional. Consider these scenarios:

| Scenario                                            | What Happens                                                                                                                    |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| A pending request with matching email exists        | Status changes to `created`, user_id is set. `$affected = 1`.                                                                   |
| No request with this email exists at all            | Nothing is updated. `$affected = 0`.                                                                                            |
| A request exists but status is already `created`    | Nothing is updated (it's not `pending`). `$affected = 0`.                                                                       |
| Multiple pending requests with the same email exist | All of them get updated. `$affected = 2+`. (This shouldn't normally happen due to validation, but the query handles it safely.) |

---

### 12.2 How the Request Is Updated (Service Layer)

The repository method above is called from the **service layer**, which wraps it in protective logic. There are two service files involved:

#### Step 1: `UserRegistrationRequestService` — The Dedicated Service

This is a small, focused service that handles ONLY registration request operations.

**File:** `src/Modules/Authentication/Application/Services/UserRegistrationRequestService.php`

**Complete source code:**

```php
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
```

**Line-by-line explanation of `markAsCreatedByEmail()`:**

1. **`public function markAsCreatedByEmail(string $email, string $userId): void`**
    - Accepts the email and user ID. Returns nothing (`void`) — this is a "fire and forget" operation.

2. **`try {`**
    - Opens a try/catch block. This is the **most important design decision** in this method. It means: _"Try to do the following, but if ANYTHING goes wrong, don't crash — handle it gracefully."_

3. **`$this->repository->markRegistrationRequestAsCreated($email, $userId);`**
    - Calls the repository method we explained above. This is where the actual database UPDATE happens.

4. **`} catch (\Throwable $e) {`**
    - `\Throwable` catches **every possible error** in PHP — exceptions, type errors, database connection failures, anything. This is the widest possible safety net.

5. **`Log::warning('Failed to update registration request status.', [...])`**
    - If something goes wrong, instead of crashing, the system writes a **warning** to the log file (`storage/logs/laravel.log`). The log entry includes the email, user ID, and the error message so a developer can investigate later.
    - This uses Laravel's logging facade. The `warning` level means: _"Something unexpected happened, but it's not critical."_

6. The method does NOT re-throw the exception. It **swallows** it. This is intentional — see section 12.4 for why.

#### Step 2: `AuthenticationService.registerUser()` — Where It's Called From

The `markAsCreatedByEmail()` method is called during admin user creation. Here is the **complete `registerUser()` method** which is the entry point:

**File:** `src/Modules/Authentication/Application/Services/AuthenticationService.php`

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

    $this->registrationRequestService->markAsCreatedByEmail($user->email, $user->id);

    Mail::to($user->email)->send(new TempPasswordMail($tempPassword, $user->name));

    return [
        'user' => $user,
        'message' => 'User created successfully. Temporary password sent to ' . $user->email,
    ];
}
```

**Step-by-step walkthrough of what happens when an admin creates a user:**

1. **`$admin = $this->getAuthenticatedUser();`**
    - Reads the JWT token from the request, decodes it, and finds the logged-in user. If the token is invalid or missing, this throws an `AuthenticationException` and stops here.

2. **`if (!$admin->hasRole('admin')) { throw ... }`**
    - Checks if the logged-in user has the `admin` role (using Spatie Permission). If not, throws an error. Only admins can create users.

3. **`$tempPassword = Str::random(16);`**
    - Generates a random 16-character string like `aB3xK9mPq2wN7vLe`. This will be the new user's first password.

4. **`$user = $this->repository->createUser([...]);`**
    - Inserts a new row into the `users` table. The password is automatically hashed by the `Authentication` model's `'password' => 'hashed'` cast. The `is_first_login` flag is set to `true` so the user must change their password on first login.

5. **`$user->assignRole('user');`**
    - Assigns the `user` role to the new account using Spatie Permission. This creates a row in the `model_has_roles` table.

6. **`$this->registrationRequestService->markAsCreatedByEmail($user->email, $user->id);`**
    - **This is where the registration request linking happens.** The system passes the new user's email and UUID to the `UserRegistrationRequestService`.
    - Inside that service, the `try/catch` wraps the repository call.
    - If a pending request with that email exists → it gets updated to `created` with the `user_id`.
    - If no pending request exists → nothing happens, no error.
    - If the database is unreachable → a warning is logged, but the user creation continues.

7. **`Mail::to($user->email)->send(new TempPasswordMail(...));`**
    - Sends an email to the new user with their temporary password.

8. **`return ['user' => $user, 'message' => '...'];`**
    - Returns the new user object and a success message back to the admin.

#### The Full Data Flow Diagram

```
Admin sends "register" mutation
         │
         ▼
AuthResolver.register()
         │ extracts input data
         ▼
AuthenticationService.registerUser()
         │
         ├── 1. Verify JWT token → Get admin user
         ├── 2. Check admin role → Throw if not admin
         ├── 3. Generate temp password (Str::random(16))
         ├── 4. Insert new user into 'users' table
         ├── 5. Assign 'user' role
         ├── 6. Call registrationRequestService->markAsCreatedByEmail()
         │         │
         │         ▼
         │   UserRegistrationRequestService.markAsCreatedByEmail()
         │         │
         │         ├── try {
         │         │     repository->markRegistrationRequestAsCreated()
         │         │         │
         │         │         ▼
         │         │   AuthenticationRepository.markRegistrationRequestAsCreated()
         │         │         │
         │         │         ▼
         │         │   SQL: UPDATE user_registration_requests
         │         │        SET status='created', user_id='...'
         │         │        WHERE email='...' AND status='pending'
         │         │
         │         └── } catch (\Throwable) {
         │               Log::warning(...)  ← log it, don't crash
         │             }
         │
         ├── 7. Send temp password email
         └── 8. Return success response
```

#### How the Dependency Injection Chain Works

Before any of this code runs, Laravel needs to know how to create these objects. That's configured in the **Service Provider**.

**File:** `src/Modules/Authentication/AuthenticationServiceProvider.php`

**Complete source code:**

```php
<?php

namespace Src\Modules\Authentication;

use Src\Modules\Authentication\Application\Services\AuthenticationService;
use Src\Modules\Authentication\Application\Services\UserRegistrationRequestService;
use Src\Modules\Authentication\Infrastructure\Repositories\AuthenticationRepository;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthenticationRepository::class, function ($app) {
            return new AuthenticationRepository();
        });

        $this->app->singleton(UserRegistrationRequestService::class, function ($app) {
            return new UserRegistrationRequestService(
                $app->make(AuthenticationRepository::class)
            );
        });

        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService(
                $app->make(AuthenticationRepository::class),
                $app->make(UserRegistrationRequestService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Infrastructure/Database/migrations');
    }
}
```

**What this file does, in plain English:**

Think of this file as a **recipe book** that tells Laravel how to assemble the module's components.

1. **`$this->app->singleton(AuthenticationRepository::class, ...)`**
    - _"When anyone asks for an `AuthenticationRepository`, create ONE instance and reuse it everywhere."_
    - The repository has no dependencies — it just does `new AuthenticationRepository()`.

2. **`$this->app->singleton(UserRegistrationRequestService::class, ...)`**
    - _"When anyone asks for a `UserRegistrationRequestService`, create ONE instance. It needs an `AuthenticationRepository`, so grab the one we already created."_
    - `$app->make(AuthenticationRepository::class)` fetches the singleton created in step 1.

3. **`$this->app->singleton(AuthenticationService::class, ...)`**
    - _"When anyone asks for an `AuthenticationService`, create ONE instance. It needs both the repository AND the registration request service."_
    - Both dependencies are fetched from the container using `$app->make(...)`.

4. **`$this->loadMigrationsFrom(...)`**
    - _"When `php artisan migrate` runs, also look for migration files in our module's `Infrastructure/Database/migrations/` folder."_

**The creation order:**

```
AuthenticationRepository (no dependencies)
         │
         ▼
UserRegistrationRequestService (depends on Repository)
         │
         ▼
AuthenticationService (depends on Repository + UserRegistrationRequestService)
         │
         ▼
AuthResolver (depends on AuthenticationService + UserRegistrationRequestService)
```

The resolver (`AuthResolver`) is NOT registered in the service provider — Lighthouse (the GraphQL library) creates it automatically using Laravel's container, which knows how to resolve its constructor dependencies because we registered the services above.

---

### 12.3 How the Listing Query Works

This section explains how the `userRegistrationRequests` GraphQL query works — from the moment a client sends the request to the moment the data comes back.

#### The GraphQL Schema Definition

**File:** `src/Modules/Authentication/GraphQL/queries.graphql`

**Complete source code:**

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

    """
    List all user registration requests.
    Optionally filter by status ('pending' or 'created').
    Requires a valid JWT token in the Authorization header.
    """
    userRegistrationRequests(
        "Filter by status: 'pending' or 'created'. Omit to return all."
        status: String
    ): [UserRegistrationRequest!]!
        @guard(with: ["api"])
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@userRegistrationRequests"
        )
}
```

**Line-by-line explanation of the `userRegistrationRequests` query:**

1. **`userRegistrationRequests(`** — The name of the query. This is what clients use in their GraphQL request.

2. **`status: String`** — An **optional** argument. The client can pass `"pending"` or `"created"` to filter results. If omitted, all requests are returned. It's `String` (not `String!`) so it can be `null`.

3. **`): [UserRegistrationRequest!]!`** — The return type. Reading from inside out:
    - `UserRegistrationRequest!` — each item in the list is a non-null `UserRegistrationRequest` object.
    - `[...]!` — the list itself is non-null (it can be empty `[]`, but never `null`).

4. **`@guard(with: ["api"])`** — A Lighthouse directive that requires a valid JWT token. If no token (or an invalid token) is provided, the request is rejected with a `401 Unauthenticated` error **before** the resolver even runs.

5. **`@field(resolver: "...AuthResolver@userRegistrationRequests")`** — Tells Lighthouse: _"When this query is called, run the `userRegistrationRequests` method on the `AuthResolver` class."_

#### The Resolver Method

**File:** `src/Modules/Authentication/GraphQL/Resolvers/AuthResolver.php`

Here is the **complete file** (all resolver methods included for context):

```php
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

    public function me($_, array $args)
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
```

**Focusing on the `userRegistrationRequests` method:**

```php
public function userRegistrationRequests($_, array $args): array
{
    $status = $args['status'] ?? null;
    return $this->registrationRequestService->getAllRequests($status);
}
```

**Line-by-line explanation:**

1. **`public function userRegistrationRequests($_, array $args): array`**
    - `$_` — The first argument in any Lighthouse resolver is the "root value." For top-level queries, this is always `null`. We use `$_` (underscore) as a convention to indicate "we don't use this."
    - `$args` — An associative array of the arguments passed in the GraphQL query. For example, if the client sends `userRegistrationRequests(status: "pending")`, then `$args` will be `['status' => 'pending']`. If no arguments are passed, `$args` will be `[]`.

2. **`$status = $args['status'] ?? null;`**
    - The `??` operator is PHP's **null coalescing operator**. It means: _"If `$args['status']` exists and is not null, use it. Otherwise, use `null`."_
    - If the client passes `status: "pending"` → `$status` = `"pending"`.
    - If the client passes no status argument → `$status` = `null`.

3. **`return $this->registrationRequestService->getAllRequests($status);`**
    - Calls the `UserRegistrationRequestService` (NOT `AuthenticationService`). Notice this resolver uses the dedicated registration request service directly.
    - Passes the status filter (or null for "all").
    - The service returns an array, which the resolver returns directly to Lighthouse, which serializes it into a JSON GraphQL response.

**Why this resolver uses `$this->registrationRequestService` instead of `$this->authService`:**

The `userRegistrationRequests` query is about **registration requests**, not about authentication (login, tokens, etc.). Following the **Single Responsibility Principle**, registration request operations are handled by `UserRegistrationRequestService`. The resolver is "smart" enough to know which service to call for which operation.

#### The Service Method

**File:** `src/Modules/Authentication/Application/Services/UserRegistrationRequestService.php`

```php
public function getAllRequests(?string $status = null): array
{
    return $this->repository->getAllRegistrationRequests($status);
}
```

**Explanation:**

This is a simple pass-through. The service layer could add business logic here in the future (e.g., permission checks, filtering by date ranges, pagination), but currently it just delegates directly to the repository.

- **`?string $status = null`** — The `?` means the parameter accepts either a `string` or `null`. The `= null` makes it optional; if nothing is passed, it defaults to `null`.
- **Returns:** Whatever the repository returns — an array of `stdClass` objects (one per row in the database).

#### The Repository Method

**File:** `src/Modules/Authentication/Infrastructure/Repositories/AuthenticationRepository.php`

```php
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
```

**Line-by-line explanation:**

1. **`$query = DB::table('user_registration_requests')`**
    - Creates a query builder instance targeting the `user_registration_requests` table.

2. **`->orderBy('created_at', 'desc');`**
    - Sorts results by `created_at` in **descending** order (newest first). This means the most recent request appears at the top of the list.

3. **`if ($status !== null) {`**
    - Checks if a status filter was provided. Uses strict comparison (`!==`) to distinguish between `null` (no filter) and an empty string.

4. **`$query->where('status', $status);`**
    - If a filter was provided, adds a WHERE clause. For example, if `$status` is `"pending"`, the SQL becomes: `WHERE status = 'pending'`.

5. **`return $query->get()->all();`**
    - `->get()` executes the query and returns a `Collection` of `stdClass` objects.
    - `->all()` converts the Collection to a plain PHP array. This is necessary because GraphQL resolvers expect arrays, not Collection objects.

**What the SQL queries look like:**

Without filter (all requests):

```sql
SELECT * FROM user_registration_requests
ORDER BY created_at DESC;
```

With filter (pending only):

```sql
SELECT * FROM user_registration_requests
WHERE status = 'pending'
ORDER BY created_at DESC;
```

#### The Complete Data Flow for a Listing Query

```
Client sends:
    query {
        userRegistrationRequests(status: "pending") {
            id, username, email, status, user_id, created_at
        }
    }
    + Header: Authorization: Bearer <jwt-token>
         │
         ▼
    Lighthouse checks @guard(with: ["api"])
    → Validates JWT token
    → If invalid: returns 401 error, STOPS
    → If valid: continues
         │
         ▼
    AuthResolver.userRegistrationRequests($_, ['status' => 'pending'])
    → $status = $args['status'] ?? null;  // $status = "pending"
    → $this->registrationRequestService->getAllRequests("pending")
         │
         ▼
    UserRegistrationRequestService.getAllRequests("pending")
    → $this->repository->getAllRegistrationRequests("pending")
         │
         ▼
    AuthenticationRepository.getAllRegistrationRequests("pending")
    → $query = DB::table('user_registration_requests')->orderBy('created_at', 'desc')
    → $query->where('status', 'pending')  // because $status !== null
    → $query->get()->all()
         │
         ▼
    SQL executed:
        SELECT * FROM user_registration_requests
        WHERE status = 'pending'
        ORDER BY created_at DESC
         │
         ▼
    Database returns rows → PHP array of stdClass objects
         │
         ▼
    Array travels back up through: Repository → Service → Resolver → Lighthouse
         │
         ▼
    Lighthouse serializes to JSON, selecting only requested fields:
    {
        "data": {
            "userRegistrationRequests": [
                {
                    "id": "abc-123",
                    "username": "john_doe",
                    "email": "john@example.com",
                    "status": "pending",
                    "user_id": null,
                    "created_at": "2026-04-15 10:00:00"
                }
            ]
        }
    }
```

---

### 12.4 Why the System Does NOT Break if No Request Exists

This is the most important design decision in the registration request feature. When an admin creates a user, the system **always** tries to link a registration request — even if no request was ever submitted for that email. Here is how and why it's safe.

#### The Three Safety Layers

**Layer 1: The SQL UPDATE is inherently safe**

```php
// In AuthenticationRepository:
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

An SQL `UPDATE ... WHERE ...` statement is **always safe when no rows match**. If the WHERE clause doesn't find any rows, the database simply does nothing. It doesn't throw an error, it doesn't crash — it returns `0` (zero rows affected). This is standard SQL behavior.

So if an admin creates a user with email `alice@example.com`, but nobody ever submitted a registration request with that email:

- The query runs: `UPDATE ... WHERE email = 'alice@example.com' AND status = 'pending'`
- No rows match → `$affected = 0`
- Method returns `false`
- No crash. No error. No side effects.

**Layer 2: The service catches ALL errors**

```php
// In UserRegistrationRequestService:
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
```

Even if something unexpected happens (database connection drops, table doesn't exist, a bug is introduced), the `try/catch` block catches `\Throwable` — which is the **broadest possible catch** in PHP. It catches:

- `\Exception` — normal exceptions
- `\Error` — PHP engine errors (type errors, etc.)
- Everything that implements `\Throwable`

When caught, instead of crashing, it:

1. Writes a warning to the log file with all the relevant context (email, user ID, error message).
2. Returns silently — the function completes normally.
3. The calling code (`registerUser()`) continues to the next line.

**Layer 3: The calling code doesn't depend on the result**

```php
// In AuthenticationService.registerUser():

// Step 5: Assign role (already done)
$user->assignRole('user');

// Step 6: Try to link registration request (MIGHT find nothing — that's OK)
$this->registrationRequestService->markAsCreatedByEmail($user->email, $user->id);

// Step 7: Send email (runs regardless of Step 6's outcome)
Mail::to($user->email)->send(new TempPasswordMail($tempPassword, $user->name));

// Step 8: Return success (runs regardless of Step 6's outcome)
return [
    'user' => $user,
    'message' => 'User created successfully. Temporary password sent to ' . $user->email,
];
```

Notice that:

- Step 6 (`markAsCreatedByEmail`) returns `void` — the calling code doesn't check any return value.
- Step 7 (sending the email) runs **unconditionally** — it doesn't depend on Step 6.
- Step 8 (returning success) runs **unconditionally** — the admin always gets a success response if the user was created.

The user creation flow treats the registration request link as a **"fire and forget" side effect**. It's nice if it works, but it's not required for the primary operation (creating the user) to succeed.

#### Real-World Scenarios

| Scenario                    | What the admin does                                                                                       | What happens to the registration request                                                         | Admin sees                                  |
| --------------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------- |
| **Normal: request exists**  | Admin creates user with email `john@example.com`                                                          | The pending request for `john@example.com` gets status `created` and user_id linked              | "User created successfully."                |
| **No request exists**       | Admin creates user with email `alice@example.com` (Alice never submitted a request)                       | Nothing happens — the UPDATE affects 0 rows                                                      | "User created successfully." (same message) |
| **Database error**          | Admin creates user, but the `user_registration_requests` table has a problem                              | A warning is logged. User is still created.                                                      | "User created successfully." (same message) |
| **Request already created** | Admin creates user with email `bob@example.com`, but Bob's request was already linked to a different user | Nothing happens — the WHERE clause requires `status = 'pending'`, and Bob's request is `created` | "User created successfully." (same message) |

In every scenario, the admin gets the same success response. The registration request linking is **completely invisible** to the admin — it's an internal housekeeping operation.

#### Why This Design Was Chosen

1. **User creation is the primary goal.** If the registration request update fails, we must NOT fail the user creation. The admin asked to create a user — that must succeed.

2. **Registration requests are optional.** An admin can create users who never submitted a request. The system must handle both cases identically.

3. **Errors are logged, not hidden.** While the error is swallowed (not shown to the admin), it IS written to the log file. A developer can check `storage/logs/laravel.log` to find and fix issues.

4. **The `void` return type signals intent.** By returning `void` instead of `bool`, the method signature tells other developers: _"Don't rely on this method's outcome. It does its best, but you shouldn't make decisions based on whether it succeeded."_

---

### 12.5 Complete File Reference — All Code Involved

For quick reference, here is every file involved in the registration request feature and its role:

| File                                                       | Layer          | Role                                                                                                                                           |
| ---------------------------------------------------------- | -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `GraphQL/queries.graphql`                                  | Presentation   | Defines the `userRegistrationRequests` query + `@guard` + `@field`                                                                             |
| `GraphQL/mutations.graphql`                                | Presentation   | Defines the `requestUserRegistration` mutation + `@field`                                                                                      |
| `GraphQL/types.graphql`                                    | Presentation   | Defines `UserRegistrationRequest` type + `RequestUserRegistrationResponse` type                                                                |
| `GraphQL/inputs.graphql`                                   | Presentation   | Defines `RequestUserRegistrationInput` input type                                                                                              |
| `GraphQL/Resolvers/AuthResolver.php`                       | Presentation   | Thin pass-through: routes queries/mutations to the correct service                                                                             |
| `Application/Services/UserRegistrationRequestService.php`  | Application    | Dedicated service: `markAsCreatedByEmail()` (with try/catch) + `getAllRequests()`                                                              |
| `Application/Services/AuthenticationService.php`           | Application    | `registerUser()` calls `markAsCreatedByEmail()` ; `requestUserRegistration()` handles new requests                                             |
| `Infrastructure/Repositories/AuthenticationRepository.php` | Infrastructure | `markRegistrationRequestAsCreated()`, `findPendingRegistrationRequestByEmail()`, `createRegistrationRequest()`, `getAllRegistrationRequests()` |
| `Infrastructure/Database/migrations/2026_04_15_000001_...` | Infrastructure | Creates the `user_registration_requests` table                                                                                                 |
| `Infrastructure/Database/migrations/2026_04_15_000002_...` | Infrastructure | Adds `user_id` foreign key column                                                                                                              |
| `AuthenticationServiceProvider.php`                        | Bootstrap      | Registers `UserRegistrationRequestService` as a singleton                                                                                      |

Full source code for every file is available in [Documentation/08_Full_Source_Code/](Documentation/08_Full_Source_Code/).

---

_This documentation was generated based on the actual source code of the Authentication module. Last updated: April 15, 2026._
