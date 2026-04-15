# Authentication Module — GraphQL API & Postman Guide

The **single, complete reference** for every GraphQL operation in the Authentication module, with step-by-step Postman setup instructions.

---

## Table of Contents

1. [Endpoint & General Setup](#1-endpoint--general-setup)
2. [Postman Environment Setup](#2-postman-environment-setup)
3. [Authentication Flow Overview](#3-authentication-flow-overview)
4. [Mutations](#4-mutations)
    - 4.1 [login](#41-login)
    - 4.2 [register (Admin Only)](#42-register-admin-only)
    - 4.3 [changePassword](#43-changepassword)
    - 4.4 [verifyOtp](#44-verifyotp)
    - 4.5 [resendOtp](#45-resendotp)
    - 4.6 [forgotPassword](#46-forgotpassword)
    - 4.7 [resetPassword](#47-resetpassword)
    - 4.8 [refreshToken](#48-refreshtoken)
    - 4.9 [logout](#49-logout)
    - 4.10 [requestUserRegistration](#410-requestuserregistration)
5. [Queries](#5-queries)
    - 5.1 [me](#51-me)
    - 5.2 [userRegistrationRequests](#52-userregistrationrequests)
6. [Postman Step-by-Step Guide](#6-postman-step-by-step-guide)
7. [Quick Reference Table](#7-quick-reference-table)

---

## 1. Endpoint & General Setup

All GraphQL requests use a **single endpoint**:

```
POST http://localhost:8000/graphql
```

### Required Header (All Requests)

| Key            | Value              |
| -------------- | ------------------ |
| `Content-Type` | `application/json` |

### Additional Header (Protected Operations)

| Key             | Value                |
| --------------- | -------------------- |
| `Authorization` | `Bearer <jwt-token>` |

Protected operations are marked with `@guard(with: ["api"])` in the schema and require a valid JWT token obtained from `login`, `verifyOtp`, or `changePassword`.

---

## 2. Postman Environment Setup

### Step 1: Create a Collection

1. Open Postman.
2. Click **"New"** → **"Collection"**.
3. Name it: `pipTrek Authentication`.
4. Click **"Create"**.

### Step 2: Create Environment Variables

1. Click the **gear icon** (top-right) → **"Add"**.
2. Name it: `pipTrek Local`.
3. Add these variables:

| Variable      | Initial Value                   | Description             |
| ------------- | ------------------------------- | ----------------------- |
| `graphql_url` | `http://localhost:8000/graphql` | The GraphQL endpoint    |
| `auth_token`  | _(leave empty)_                 | Auto-filled after login |

4. Click **"Save"**.
5. Select `pipTrek Local` from the environment dropdown (top-right).

### Step 3: Auto-Save Token Script

To avoid manually copying the JWT token every time you log in, add a **"Tests"** script to your Login request (or at the collection level):

**In Postman:** Go to the **"Tests"** tab of your Login request and paste:

```javascript
// Auto-save the JWT token after a successful login
const response = pm.response.json();

if (response.data && response.data.login && response.data.login.token) {
    pm.environment.set("auth_token", response.data.login.token);
    console.log("✅ Token saved to environment variable 'auth_token'");
}
```

After this, every successful login will automatically save the token to `{{auth_token}}`, and all other requests can use it in their Authorization header.

---

## 3. Authentication Flow Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Normal Login Flow (no 2FA, not first login)                            │
│  login → token returned → use token for protected operations            │
├─────────────────────────────────────────────────────────────────────────┤
│  First-Time Login Flow                                                  │
│  login (is_first_login: true) → changePassword → full token returned    │
├─────────────────────────────────────────────────────────────────────────┤
│  Two-Factor Authentication Flow                                         │
│  login (requires_otp: true) → verifyOtp → full token returned           │
├─────────────────────────────────────────────────────────────────────────┤
│  Password Reset Flow                                                    │
│  forgotPassword → (email with token) → resetPassword → login again      │
├─────────────────────────────────────────────────────────────────────────┤
│  Registration Request Flow (Public)                                     │
│  requestUserRegistration → admin reviews → admin calls register          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Mutations

---

### 4.1 login

**Auth:** Public (no token required)

**GraphQL:**

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
        "password": "your-password"
    }
}
```

**Response — Normal Login:**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "uuid-here",
                "name": "John Doe",
                "email": "user@example.com",
                "two_factor_enabled": false,
                "is_first_login": false
            },
            "token": "eyJhbGciOiJIUzI1NiIs...",
            "requires_otp": false,
            "is_first_login": false,
            "message": "Login successful."
        }
    }
}
```

**Response — First-Time Login (temporary token):**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "...",
                "name": "...",
                "email": "..."
            },
            "token": "eyJ...(temporary)...",
            "requires_otp": false,
            "is_first_login": true,
            "message": "First-time login detected. Please change your password."
        }
    }
}
```

> Use the temporary token to call `changePassword`. After changing the password, a full token is returned.

**Response — 2FA Enabled (temporary token):**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "...",
                "name": "...",
                "email": "..."
            },
            "token": "eyJ...(temporary)...",
            "requires_otp": true,
            "is_first_login": false,
            "message": "OTP has been sent to your email. Please verify to complete login."
        }
    }
}
```

> An OTP code is emailed. Use `verifyOtp` with the email and OTP to get a full token.

**Response — First-Time Login + 2FA (both):**

```json
{
    "data": {
        "login": {
            "user": {
                "id": "...",
                "name": "...",
                "email": "..."
            },
            "token": null,
            "requires_otp": true,
            "is_first_login": true,
            "message": "OTP has been sent to your email. After verification, you will need to change your password."
        }
    }
}
```

**Error — Invalid Credentials:**

```json
{
    "errors": [
        {
            "message": "Invalid credentials. Please check your email and password.",
            "extensions": { "category": "authentication" }
        }
    ],
    "data": { "login": null }
}
```

#### Postman Setup

1. Create a new request in your collection. Name: `Login`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. In the **QUERY** panel, paste the mutation above.
5. In the **VARIABLES** panel, paste the variables JSON.
6. Go to the **Tests** tab and paste the auto-save token script from [Section 2](#step-3-auto-save-token-script).
7. Click **Send**.

---

### 4.2 register (Admin Only)

**Auth:** `Bearer <admin-token>` — only users with the `admin` role can call this.

**GraphQL:**

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

**Response — Success:**

```json
{
    "data": {
        "register": {
            "user": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
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

> A temporary password is emailed to the new user. Check `storage/logs/laravel.log` if using `MAIL_MAILER=log`.

**Error — Not Admin:**

```json
{
    "errors": [
        {
            "message": "Unauthorized. Only administrators can create new users.",
            "extensions": { "category": "authentication" }
        }
    ],
    "data": { "register": null }
}
```

**Error — Email Already Taken:**

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
    "data": { "register": null }
}
```

> If a pending registration request exists for the same email, the system automatically updates it to `created` status and links the new user's ID.

#### Postman Setup

1. Create a new request. Name: `Register (Admin)`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. In the **QUERY** panel, paste the mutation above.
6. In the **VARIABLES** panel, paste the variables JSON.
7. Click **Send**.

---

### 4.3 changePassword

**Auth:** `Bearer <temporary-token>` — the token from a first-time login.

**GraphQL:**

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
        "password": "MyNewSecurePass123",
        "password_confirmation": "MyNewSecurePass123"
    }
}
```

**Response — Success:**

```json
{
    "data": {
        "changePassword": {
            "user": {
                "id": "...",
                "name": "Jane Smith",
                "email": "jane@example.com",
                "is_first_login": false
            },
            "token": "eyJ...(full token)...",
            "message": "Password changed successfully. Welcome!"
        }
    }
}
```

> After success, `is_first_login` becomes `false` and a full-access token is returned. Save this token for subsequent requests.

#### Postman Setup

1. Create a new request. Name: `Change Password`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
6. Optionally add a **Tests** script to auto-save the new token:

```javascript
const response = pm.response.json();
if (
    response.data &&
    response.data.changePassword &&
    response.data.changePassword.token
) {
    pm.environment.set("auth_token", response.data.changePassword.token);
}
```

7. Click **Send**.

---

### 4.4 verifyOtp

**Auth:** Public (no token required)

**GraphQL:**

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
        "email": "user@example.com",
        "otp": "483921"
    }
}
```

> The OTP code is sent via email. Check `storage/logs/laravel.log` if using `MAIL_MAILER=log`.

**Response — Success:**

```json
{
    "data": {
        "verifyOtp": {
            "user": {
                "id": "...",
                "name": "...",
                "email": "user@example.com"
            },
            "token": "eyJ...(full token)...",
            "message": "OTP verified successfully. Login complete."
        }
    }
}
```

**Error — Invalid or Expired OTP:**

```json
{
    "errors": [
        {
            "message": "Invalid or expired OTP. Please request a new one.",
            "extensions": { "category": "business" }
        }
    ],
    "data": { "verifyOtp": null }
}
```

#### Postman Setup

1. Create a new request. Name: `Verify OTP`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
5. Add a **Tests** script to auto-save the token:

```javascript
const response = pm.response.json();
if (response.data && response.data.verifyOtp && response.data.verifyOtp.token) {
    pm.environment.set("auth_token", response.data.verifyOtp.token);
}
```

6. Click **Send**.

---

### 4.5 resendOtp

**Auth:** Public (no token required)

**GraphQL:**

```graphql
mutation ResendOtp($input: ResendOtpInput!) {
    resendOtp(input: $input)
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

#### Postman Setup

1. Create a new request. Name: `Resend OTP`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
5. Click **Send**.

---

### 4.6 forgotPassword

**Auth:** Public (no token required)

**GraphQL:**

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

**Response (always the same, for security):**

```json
{
    "data": {
        "forgotPassword": {
            "message": "If this email exists, a password reset link has been sent."
        }
    }
}
```

> The response is intentionally identical whether the email exists or not. This prevents email enumeration attacks.

#### Postman Setup

1. Create a new request. Name: `Forgot Password`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
5. Click **Send**.
6. Check `storage/logs/laravel.log` for the reset token (if using `MAIL_MAILER=log`).

---

### 4.7 resetPassword

**Auth:** Public (no token required)

**GraphQL:**

```graphql
mutation ResetPassword($input: ResetPasswordInput!) {
    resetPassword(input: $input)
}
```

**Variables:**

```json
{
    "input": {
        "token": "a1b2c3d4e5f6...",
        "password": "NewPassword123",
        "password_confirmation": "NewPassword123"
    }
}
```

> The `token` comes from the password reset email sent by `forgotPassword`.

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

**Error — Invalid or Expired Token:**

```json
{
    "errors": [
        {
            "message": "Invalid or expired reset token.",
            "extensions": { "category": "business" }
        }
    ],
    "data": { "resetPassword": null }
}
```

#### Postman Setup

1. Create a new request. Name: `Reset Password`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
5. Click **Send**.

---

### 4.8 refreshToken

**Auth:** `Bearer <current-token>`

**GraphQL:**

```graphql
mutation RefreshToken {
    refreshToken
}
```

**Variables:** _(none)_

**Response — Success:**

```json
{
    "data": {
        "refreshToken": {
            "token": "eyJ...(new token)...",
            "message": "Token refreshed successfully."
        }
    }
}
```

> The old token is invalidated and a new one is returned. Save the new token for subsequent requests.

**Error — Token Expired or Invalid:**

```json
{
    "errors": [
        {
            "message": "Unauthenticated.",
            "extensions": { "category": "authentication" }
        }
    ],
    "data": { "refreshToken": null }
}
```

#### Postman Setup

1. Create a new request. Name: `Refresh Token`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. Paste the mutation in **QUERY**. No variables needed.
6. Add a **Tests** script to auto-save the new token:

```javascript
const response = pm.response.json();
if (
    response.data &&
    response.data.refreshToken &&
    response.data.refreshToken.token
) {
    pm.environment.set("auth_token", response.data.refreshToken.token);
}
```

7. Click **Send**.

---

### 4.9 logout

**Auth:** `Bearer <current-token>`

**GraphQL:**

```graphql
mutation Logout {
    logout
}
```

**Variables:** _(none)_

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

> After logout, the token is invalidated. Any subsequent request using the same token will return `401 Unauthenticated`.

#### Postman Setup

1. Create a new request. Name: `Logout`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. Paste the mutation in **QUERY**. No variables needed.
6. Click **Send**.

---

### 4.10 requestUserRegistration

**Auth:** Public (no token required) — anyone can submit a registration request.

**GraphQL:**

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

> This does **NOT** create a user account. It records a pending request and emails all administrators.

**Error — Duplicate Pending Request:**

```json
{
    "errors": [
        {
            "message": "A registration request with this email is already pending.",
            "extensions": { "category": "business" }
        }
    ],
    "data": { "requestUserRegistration": null }
}
```

**Error — Email Already Has an Account:**

```json
{
    "errors": [
        {
            "message": "This email is already associated with an existing account.",
            "extensions": { "category": "business" }
        }
    ],
    "data": { "requestUserRegistration": null }
}
```

**Error — Validation Failed:**

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
    "data": { "requestUserRegistration": null }
}
```

#### Postman Setup

1. Create a new request. Name: `Request User Registration`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Body** → select **GraphQL**.
4. Paste the mutation in **QUERY** and the variables in **VARIABLES**.
5. Click **Send**.
6. Check `storage/logs/laravel.log` (if using `MAIL_MAILER=log`) to see the admin notification email.

---

## 5. Queries

---

### 5.1 me

**Auth:** `Bearer <token>` — any authenticated user.

**GraphQL:**

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

**Variables:** _(none)_

**Response — Success:**

```json
{
    "data": {
        "me": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "name": "John Doe",
            "email": "john@example.com",
            "two_factor_enabled": false,
            "is_first_login": false,
            "created_at": "2026-04-10T12:00:00.000000Z",
            "updated_at": "2026-04-10T12:00:00.000000Z"
        }
    }
}
```

**Error — Not Authenticated:**

```json
{
    "errors": [
        {
            "message": "Unauthenticated.",
            "extensions": { "category": "authentication" }
        }
    ],
    "data": { "me": null }
}
```

#### Postman Setup

1. Create a new request. Name: `Me (Current User)`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. Paste the query in **QUERY**. No variables needed.
6. Click **Send**.

---

### 5.2 userRegistrationRequests

**Auth:** `Bearer <admin-token>` — only admins can list registration requests.

**GraphQL:**

```graphql
query UserRegistrationRequests($status: String) {
    userRegistrationRequests(status: $status) {
        id
        username
        email
        status
        user_id
        created_at
        updated_at
    }
}
```

**Variables — Get all requests:**

```json
{}
```

**Variables — Filter by status:**

```json
{
    "status": "pending"
}
```

> Valid status values: `"pending"` (awaiting admin action), `"created"` (admin has created the user account).

**Response — Success:**

```json
{
    "data": {
        "userRegistrationRequests": [
            {
                "id": "uuid-abc",
                "username": "johndoe",
                "email": "johndoe@example.com",
                "status": "pending",
                "user_id": null,
                "created_at": "2026-04-15T10:30:00.000000Z",
                "updated_at": "2026-04-15T10:30:00.000000Z"
            },
            {
                "id": "uuid-def",
                "username": "janesmith",
                "email": "jane@example.com",
                "status": "created",
                "user_id": "uuid-xyz",
                "created_at": "2026-04-14T08:00:00.000000Z",
                "updated_at": "2026-04-15T12:00:00.000000Z"
            }
        ]
    }
}
```

**Error — Not Admin:**

```json
{
    "errors": [
        {
            "message": "Unauthorized. Only administrators can view registration requests.",
            "extensions": { "category": "authentication" }
        }
    ],
    "data": { "userRegistrationRequests": null }
}
```

#### Postman Setup

1. Create a new request. Name: `List Registration Requests (Admin)`.
2. Method: **POST** | URL: `{{graphql_url}}`.
3. Go to **Headers** and add: `Authorization` → `Bearer {{auth_token}}`.
4. Go to **Body** → select **GraphQL**.
5. Paste the query in **QUERY** and optionally the status filter in **VARIABLES**.
6. Click **Send**.

---

## 6. Postman Step-by-Step Guide

This section walks through a **complete testing flow** from login to logout.

### Step 1: Login as Admin

1. Open the `Login` request.
2. Set variables:

```json
{
    "input": {
        "email": "admin@example.com",
        "password": "password"
    }
}
```

3. Click **Send**.
4. The auto-save script stores the token in `{{auth_token}}`.

### Step 2: Register a New User (Admin)

1. Open the `Register (Admin)` request.
2. The `Authorization` header already uses `{{auth_token}}`.
3. Set variables:

```json
{
    "input": {
        "name": "Jane Smith",
        "email": "jane@example.com",
        "two_factor_enabled": false
    }
}
```

4. Click **Send**.
5. Check `storage/logs/laravel.log` for the temporary password.

### Step 3: Login as the New User

1. Open the `Login` request.
2. Set variables to the new user's email and the temporary password from the log.
3. Click **Send**.
4. The response should have `"is_first_login": true`.

### Step 4: Change Password (First Login)

1. Open the `Change Password` request.
2. The `Authorization` header uses `{{auth_token}}` (the temporary token from Step 3).
3. Set variables:

```json
{
    "input": {
        "password": "MyNewSecurePass123",
        "password_confirmation": "MyNewSecurePass123"
    }
}
```

4. Click **Send**.
5. A full token is returned — save it (or let the Tests script auto-save it).

### Step 5: Login Again with New Password

1. Open the `Login` request.
2. Use the new user's email and the password you just set.
3. Click **Send**.
4. The response should have `"is_first_login": false` and a full token.

### Step 6: Get Current User Profile

1. Open the `Me (Current User)` request.
2. Click **Send**.
3. The response shows the authenticated user's profile.

### Step 7: Test Registration Request (Public)

1. Open the `Request User Registration` request.
2. Set variables:

```json
{
    "input": {
        "username": "newperson",
        "email": "newperson@example.com"
    }
}
```

3. Click **Send** (no token needed).
4. Check `storage/logs/laravel.log` for the admin notification email.

### Step 8: View Registration Requests (Admin)

1. Login as admin again (Step 1).
2. Open the `List Registration Requests (Admin)` request.
3. Click **Send**.
4. You should see the request from Step 7 with `status: "pending"`.

### Step 9: Logout

1. Open the `Logout` request.
2. Click **Send**.
3. Try calling `Me (Current User)` again — should get `401 Unauthenticated`.

---

## 7. Quick Reference Table

| #   | Operation                  | Type     | Auth Required         | Returns Token |
| --- | -------------------------- | -------- | --------------------- | ------------- |
| 1   | `login`                    | Mutation | No                    | Yes           |
| 2   | `register`                 | Mutation | Yes (Admin)           | No            |
| 3   | `changePassword`           | Mutation | Yes (Temporary token) | Yes           |
| 4   | `verifyOtp`                | Mutation | No                    | Yes           |
| 5   | `resendOtp`                | Mutation | No                    | No            |
| 6   | `forgotPassword`           | Mutation | No                    | No            |
| 7   | `resetPassword`            | Mutation | No                    | No            |
| 8   | `refreshToken`             | Mutation | Yes                   | Yes           |
| 9   | `logout`                   | Mutation | Yes                   | No            |
| 10  | `requestUserRegistration`  | Mutation | No                    | No            |
| 11  | `me`                       | Query    | Yes                   | No            |
| 12  | `userRegistrationRequests` | Query    | Yes (Admin)           | No            |

---

_This is the single, authoritative reference for all GraphQL operations and Postman setup in the Authentication module._
_For architecture, code explanations, and business logic details, see [DOCUMENTATION.md](DOCUMENTATION.md)._
_For the code walkthrough of registration requests, see [DOCUMENTATION_PART_2.md](DOCUMENTATION_PART_2.md)._
