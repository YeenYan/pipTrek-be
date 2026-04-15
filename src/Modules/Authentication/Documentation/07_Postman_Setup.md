# 07 — Postman Setup Guide

## Environment Setup

1. Open Postman → **Environments** → **+ Create Environment**
2. Name: `pipTrek Local`
3. Add these variables:

| Variable   | Initial Value                   | Current Value                   |
| ---------- | ------------------------------- | ------------------------------- |
| `base_url` | `http://localhost:8000/graphql` | `http://localhost:8000/graphql` |
| `token`    | _(leave blank)_                 | _(auto-set after login)_        |

4. Click **Save**.
5. Select `pipTrek Local` from the Environment dropdown (top-right).

---

## Collection Setup

1. **Collections** → **+ New Collection** → Name: `pipTrek Auth`
2. In the collection's **Variables** tab, add:
    - `graphql_url` = `{{base_url}}`
3. Inside the collection, create requests as shown below.

---

## Headers (All Requests)

| Key          | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |

For protected endpoints, also add:

| Key           | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {{token}} |

---

## Auto-Save Token Script

Add this to the **Tests** (or **Post-response**) tab of Login, VerifyOtp, ChangePassword, and RefreshToken requests:

```javascript
const res = pm.response.json();

// Login — token is nested in login object
if (res.data && res.data.login && res.data.login.token) {
    pm.environment.set("token", res.data.login.token);
}

// VerifyOtp
if (res.data && res.data.verifyOtp && res.data.verifyOtp.token) {
    pm.environment.set("token", res.data.verifyOtp.token);
}

// ChangePassword
if (res.data && res.data.changePassword && res.data.changePassword.token) {
    pm.environment.set("token", res.data.changePassword.token);
}

// RefreshToken
if (res.data && res.data.refreshToken && res.data.refreshToken.token) {
    pm.environment.set("token", res.data.refreshToken.token);
}
```

---

## Request Configurations

All requests use:

- **Method:** `POST`
- **URL:** `{{base_url}}`
- **Body tab:** `GraphQL`

In Postman, select the **Body** tab, then choose **GraphQL** (not raw JSON).

---

### 1. Login

**Auth Header:** None

**Query:**

```graphql
mutation Login($input: LoginInput!) {
    login(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "admin@example.com",
        "password": "password123"
    }
}
```

---

### 2. Register (Admin Only)

**Auth Header:** `Bearer {{token}}`

**Query:**

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

**GraphQL Variables:**

```json
{
    "input": {
        "name": "New User",
        "email": "newuser@example.com",
        "two_factor_enabled": false
    }
}
```

---

### 3. Change Password

**Auth Header:** `Bearer {{token}}` (temporary token from first login)

**Query:**

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

**GraphQL Variables:**

```json
{
    "input": {
        "password": "MyNewSecurePass123",
        "password_confirmation": "MyNewSecurePass123"
    }
}
```

---

### 4. Verify OTP

**Auth Header:** None

**Query:**

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
        "email": "user@example.com",
        "otp": "123456"
    }
}
```

---

### 5. Resend OTP

**Auth Header:** None

**Query:**

```graphql
mutation ResendOtp($input: ResendOtpInput!) {
    resendOtp(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "user@example.com"
    }
}
```

---

### 6. Forgot Password

**Auth Header:** None

**Query:**

```graphql
mutation ForgotPassword($input: ForgotPasswordInput!) {
    forgotPassword(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "email": "user@example.com"
    }
}
```

---

### 7. Reset Password

**Auth Header:** None

**Query:**

```graphql
mutation ResetPassword($input: ResetPasswordInput!) {
    resetPassword(input: $input)
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "token": "paste-token-from-email-here",
        "password": "NewPassword123",
        "password_confirmation": "NewPassword123"
    }
}
```

---

### 8. Refresh Token

**Auth Header:** `Bearer {{token}}`

**Query:**

```graphql
mutation RefreshToken {
    refreshToken
}
```

**GraphQL Variables:**

```json
{}
```

---

### 9. Logout

**Auth Header:** `Bearer {{token}}`

**Query:**

```graphql
mutation Logout {
    logout
}
```

**GraphQL Variables:**

```json
{}
```

---

### 10. Request User Registration

**Auth Header:** None

**Query:**

```graphql
mutation RequestUserRegistration($input: RequestUserRegistrationInput!) {
    requestUserRegistration(input: $input) {
        success
        message
    }
}
```

**GraphQL Variables:**

```json
{
    "input": {
        "username": "john_doe",
        "email": "john@example.com"
    }
}
```

---

### 11. Me (Get Current User)

**Auth Header:** `Bearer {{token}}`

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

**GraphQL Variables:**

```json
{}
```

---

### 12. User Registration Requests (List All)

**Auth Header:** `Bearer {{token}}`

**Query:**

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

**GraphQL Variables:**

```json
{}
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

**GraphQL Variables:**

```json
{}
```

---

## Recommended Testing Order

1. **Login** (as admin) → token auto-saved
2. **Me** → verify admin identity
3. **Request User Registration** → submit a public request
4. **User Registration Requests** → view the pending request
5. **Register** (admin creates user with same email) → request auto-linked
6. **User Registration Requests** → verify status changed to "created"
7. **Login** (as new user) → first login, get temporary token
8. **Change Password** → set permanent password, get full token
9. **Me** → verify new user
10. **Logout**
11. **Forgot Password** → trigger reset email
12. **Reset Password** → use token from email
