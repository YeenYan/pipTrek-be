# 01 — Overview

## What Does This Module Do?

The Authentication module is the **security gatekeeper** of the application. It controls:

- **Who are you?** — Login (proving your identity with email + password)
- **Can you come in?** — JWT token validation (checking your access pass)
- **What can you do?** — Roles (admin vs. regular user)
- **Can I join?** — Registration requests (public users asking for access)

## Why Does This Module Exist?

Without authentication, anyone could access any data. This module ensures:

1. Only **admins** can create user accounts (no self-registration).
2. Every user must **prove their identity** (email + password) before accessing data.
3. Optional **two-factor authentication** (2FA) adds extra security.
4. **Password reset** flow lets users recover access.
5. **Registration requests** let the public ask for access without creating accounts.

## Architecture Pattern

The module follows a **Domain-Driven Modular Monolith** architecture with four layers:

```
GraphQL Layer (Resolvers + Schema)  →  Receives requests, validates input
        ↓
Application Layer (Services)        →  Business logic, rules, decisions
        ↓
Infrastructure Layer (Repository)   →  Database operations
        ↓
Domain Layer (Model)                →  Data structure definition
```

Each layer has a single responsibility. No layer skips another — the resolver never talks to the database directly.

## Key Design Decisions

| Decision                       | Reason                                                       |
| ------------------------------ | ------------------------------------------------------------ |
| Admin-only registration        | Prevents unauthorized account creation                       |
| JWT (not sessions)             | Stateless API authentication for mobile/SPA clients          |
| UUID primary keys              | Harder to guess than sequential integers                     |
| Hashed OTP codes               | Even if the database is breached, OTPs can't be read         |
| Service layer pattern          | Business logic is testable and reusable, not tied to GraphQL |
| Separate registration requests | Public users can request access without creating accounts    |
| try/catch on request linking   | Registration request updates never interrupt user creation   |

## High-Level Flow

```
1. Public user submits a registration request
   → Stored as "pending" in database
   → Email notification sent to all admins

2. Admin creates a user account
   → System checks for matching pending registration request
   → If found: status updated to "created", user_id linked
   → Temporary password emailed to new user

3. User logs in with temporary password
   → System says: "Change your password first!"
   → User sets a new password → gets a JWT token

4. User makes API requests
   → Includes JWT token in every request
   → System validates token → allows or denies access

5. Token expires (60 minutes)
   → User can refresh the token (within 14 days)
   → Or log in again
```
