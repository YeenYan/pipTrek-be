# 06 — GraphQL API

All requests are `POST` to:

```
http://localhost:8000/graphql
```

Header for all requests:

```
Content-Type: application/json
```

Protected operations also require:

```
Authorization: Bearer <jwt-token>
```

---

## Mutations

### 1. login

**Auth:** Public

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

**Response — First-Time Login:**

```json
{
    "data": {
        "login": {
            "user": { "id": "...", "name": "...", "email": "..." },
            "token": "eyJ...(temporary)...",
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
            "user": { "id": "...", "name": "...", "email": "..." },
            "token": "eyJ...(temporary)...",
            "requires_otp": true,
            "is_first_login": false,
            "message": "OTP has been sent to your email. Please verify to complete login."
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

---

### 2. register (Admin Only)

**Auth:** `Bearer <admin-token>`

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

---

### 3. changePassword

**Auth:** `Bearer <temporary-token-from-first-login>`

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

---

### 4. verifyOtp

**Auth:** Public

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

**Response — Success:**

```json
{
    "data": {
        "verifyOtp": {
            "user": { "id": "...", "name": "...", "email": "user@example.com" },
            "token": "eyJ...(full token)...",
            "message": "OTP verified successfully. Login complete."
        }
    }
}
```

**Error — Invalid OTP:**

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

---

### 5. resendOtp

**Auth:** Public

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

**Response:**

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

### 6. forgotPassword

**Auth:** Public

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

**Response (always the same):**

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

### 7. resetPassword

**Auth:** Public

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

**Response:**

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

### 8. refreshToken

**Auth:** `Bearer <current-token>`

```graphql
mutation RefreshToken {
    refreshToken
}
```

**Response:**

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

---

### 9. logout

**Auth:** `Bearer <current-token>`

```graphql
mutation Logout {
    logout
}
```

**Response:**

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

### 10. requestUserRegistration

**Auth:** Public

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
        "username": "john_doe",
        "email": "john@example.com"
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

**Error — Email Already Has Account:**

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

---

## Queries

### 1. me

**Auth:** `Bearer <token>`

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

**Response:**

```json
{
    "data": {
        "me": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "name": "Admin User",
            "email": "admin@example.com",
            "two_factor_enabled": false,
            "is_first_login": false,
            "created_at": "2026-04-10 00:00:00",
            "updated_at": "2026-04-15 12:30:00"
        }
    }
}
```

---

### 2. userRegistrationRequests

**Auth:** `Bearer <token>`

```graphql
query UserRegistrationRequests {
    userRegistrationRequests {
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

**Response — All Requests:**

```json
{
    "data": {
        "userRegistrationRequests": [
            {
                "id": "a1b2c3d4-...",
                "username": "john_doe",
                "email": "john@example.com",
                "status": "created",
                "user_id": "550e8400-...",
                "created_at": "2026-04-15 10:00:00",
                "updated_at": "2026-04-15 14:30:00"
            },
            {
                "id": "e5f6g7h8-...",
                "username": "jane_smith",
                "email": "jane@example.com",
                "status": "pending",
                "user_id": null,
                "created_at": "2026-04-15 12:00:00",
                "updated_at": "2026-04-15 12:00:00"
            }
        ]
    }
}
```

**With Status Filter:**

```graphql
query PendingRequests {
    userRegistrationRequests(status: "pending") {
        id
        username
        email
        status
        created_at
    }
}
```

**Response:**

```json
{
    "data": {
        "userRegistrationRequests": [
            {
                "id": "e5f6g7h8-...",
                "username": "jane_smith",
                "email": "jane@example.com",
                "status": "pending",
                "created_at": "2026-04-15 12:00:00"
            }
        ]
    }
}
```

---

## Error Categories

| Category         | Meaning                                              | Example                                      |
| ---------------- | ---------------------------------------------------- | -------------------------------------------- |
| `authentication` | Auth failure (wrong password, invalid/missing token) | "Invalid credentials."                       |
| `business`       | Business rule violation                              | "A registration request is already pending." |
| `validation`     | Input validation failed                              | "The email field must be a valid email."     |
