# 05 — User Registration Request

## What Is This Feature?

This feature has two parts:

1. **Public Registration Request** — Anyone (without an account) can submit a request to join the system. No user account is created. Admins are notified via email.
2. **Admin Creation Integration** — When an admin creates a user, the system automatically checks for a matching pending registration request and updates its status.

---

## Status Lifecycle

```
pending  ──(admin creates user with matching email)──>  created
```

| Status    | Meaning                                                                  |
| --------- | ------------------------------------------------------------------------ |
| `pending` | Default. The request was submitted and is awaiting admin review.         |
| `created` | An admin has created a user account for this email. `user_id` is linked. |

---

## Database Table: `user_registration_requests`

| Column       | Type                          | Description                                             |
| ------------ | ----------------------------- | ------------------------------------------------------- |
| `id`         | UUID (primary key)            | Unique identifier                                       |
| `username`   | string                        | The requested username                                  |
| `email`      | string                        | The requester's email address                           |
| `status`     | string (default: `'pending'`) | `pending` or `created`                                  |
| `user_id`    | UUID (nullable, FK → users)   | Linked when admin creates the user. ON DELETE SET NULL. |
| `created_at` | timestamp                     | When the request was submitted                          |
| `updated_at` | timestamp                     | When the request was last modified                      |

---

## Part 1: Public Registration Request

### What Happens

1. Anyone sends the `requestUserRegistration` mutation with a username and email.
2. GraphQL validates input via `@rules` (username min 3 chars, valid email).
3. Service checks for a pending request with the same email → rejects duplicates.
4. Service checks for an existing user account with the same email → rejects.
5. Repository stores the request with `status = pending`.
6. Service sends an email notification to ALL admin users.
7. Returns `{ success: true, message: "..." }`.

### What Does NOT Happen

- No user account is created.
- No JWT token is issued.
- No access is granted.

---

## Part 2: Admin Creation Integration

### What Happens When Admin Creates a User

```
┌─────────────────────────────────────────────────┐
│  1. Admin calls register(name, email, ...)      │
│     → User created in users table               │
│     → Role 'user' assigned                      │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│  2. AuthenticationService calls                  │
│     registrationRequestService                   │
│       .markAsCreatedByEmail(email, userId)       │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│  3. UserRegistrationRequestService              │
│     wraps the call in try/catch                  │
│     → Calls repository                           │
│     → If error: logs warning, does NOT interrupt │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│  4. Repository runs:                             │
│     UPDATE user_registration_requests            │
│     SET status='created', user_id=<uuid>         │
│     WHERE email=<email> AND status='pending'     │
│                                                  │
│     If no matching row: 0 rows affected (OK)     │
│     If match found: 1 row updated                │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│  5. Temporary password email sent to new user    │
│     (existing behavior — unchanged)              │
└─────────────────────────────────────────────────┘
```

### Why try/catch?

Creating the user account is the **primary** operation. Updating the registration request is **secondary**. If it fails (table missing, constraint error, etc.), the user creation should still succeed. The failure is logged as a warning for investigation.

### What If No Matching Request Exists?

Nothing happens. The `UPDATE` query matches 0 rows. No error is thrown. The admin may be creating a user who never submitted a request — that is perfectly normal.

---

## Data Flow Example

**Before admin action:**

```
user_registration_requests:
┌──────────┬──────────┬──────────────────────┬─────────┬─────────┬────────────┐
│ id       │ username │ email                │ status  │ user_id │ created_at │
├──────────┼──────────┼──────────────────────┼─────────┼─────────┼────────────┤
│ uuid-abc │ johndoe  │ johndoe@example.com  │ pending │ NULL    │ 2026-04-15 │
└──────────┴──────────┴──────────────────────┴─────────┴─────────┴────────────┘
```

**Admin calls `register` with email `johndoe@example.com`:**

1. User created in `users` → UUID `uuid-xyz`.
2. `markAsCreatedByEmail('johndoe@example.com', 'uuid-xyz')` called.
3. Repository: `UPDATE ... SET status='created', user_id='uuid-xyz' WHERE email='johndoe@example.com' AND status='pending'`.

**After admin action:**

```
user_registration_requests:
┌──────────┬──────────┬──────────────────────┬─────────┬──────────┬────────────┐
│ id       │ username │ email                │ status  │ user_id  │ created_at │
├──────────┼──────────┼──────────────────────┼─────────┼──────────┼────────────┤
│ uuid-abc │ johndoe  │ johndoe@example.com  │ created │ uuid-xyz │ 2026-04-15 │
└──────────┴──────────┴──────────────────────┴─────────┴──────────┴────────────┘

users:
┌──────────┬──────────────────────┬──────────┐
│ id       │ email                │ name     │
├──────────┼──────────────────────┼──────────┤
│ uuid-xyz │ johndoe@example.com  │ John Doe │
└──────────┴──────────────────────┴──────────┘
```

---

## Code Walkthrough

### How email is matched

The `markRegistrationRequestAsCreated()` method in the repository uses a `WHERE email = ? AND status = 'pending'` clause. It only matches requests that are still pending.

### How request is updated

A single `UPDATE` query sets `status = 'created'`, `user_id = <new user UUID>`, and `updated_at = now()`.

### How listing query works

The `getAllRegistrationRequests()` method queries `user_registration_requests` with `ORDER BY created_at DESC`. If a `status` filter is provided, it adds `WHERE status = ?`.

### Why system does not break if no request exists

The `UPDATE` query simply affects 0 rows and returns `false`. The `try/catch` in `UserRegistrationRequestService` ensures even unexpected errors (missing table, constraint violation) are caught and logged, never interrupting the user creation flow.

---

## Files Involved

| File                                 | Role                                                                                                                                                                                                  |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `UserRegistrationRequestService.php` | `markAsCreatedByEmail()` — wraps in try/catch. `getAllRequests()` — lists requests.                                                                                                                   |
| `AuthenticationService.php`          | `registerUser()` — calls `markAsCreatedByEmail()` after user creation. `requestUserRegistration()` — handles the public request flow.                                                                 |
| `AuthenticationRepository.php`       | `markRegistrationRequestAsCreated()` — UPDATE query. `findPendingRegistrationRequestByEmail()` — SELECT pending. `createRegistrationRequest()` — INSERT. `getAllRegistrationRequests()` — SELECT all. |
| `AuthResolver.php`                   | `requestUserRegistration()` — delegates to auth service. `userRegistrationRequests()` — delegates to registration request service.                                                                    |
| `RegistrationRequestMail.php`        | Mailable for admin notification emails.                                                                                                                                                               |
| `registration-request.blade.php`     | HTML template for the admin notification email.                                                                                                                                                       |
| Migration `000001`                   | Creates the `user_registration_requests` table.                                                                                                                                                       |
| Migration `000002`                   | Adds `user_id` column with foreign key.                                                                                                                                                               |
