# 04 — Authentication Features

## Feature List

| #   | Feature                    | Public? | GraphQL Operation             | Description                              |
| --- | -------------------------- | ------- | ----------------------------- | ---------------------------------------- |
| 1   | Login                      | Yes     | `login` mutation              | Authenticate with email + password       |
| 2   | Register (Admin Only)      | No      | `register` mutation           | Admin creates a new user account         |
| 3   | Change Password            | No      | `changePassword` mutation     | First-time login password change         |
| 4   | Verify OTP                 | Yes     | `verifyOtp` mutation          | Complete 2FA login                       |
| 5   | Resend OTP                 | Yes     | `resendOtp` mutation          | Request a new OTP code                   |
| 6   | Forgot Password            | Yes     | `forgotPassword` mutation     | Request a password reset email           |
| 7   | Reset Password             | Yes     | `resetPassword` mutation      | Set new password with reset token        |
| 8   | Refresh Token              | No      | `refreshToken` mutation       | Get a new JWT before the old one expires |
| 9   | Logout                     | No      | `logout` mutation             | Invalidate current JWT token             |
| 10  | Get Current User           | No      | `me` query                    | Return the logged-in user's profile      |
| 11  | Request Registration       | Yes     | `requestUserRegistration` mut | Public user requests access              |
| 12  | List Registration Requests | No      | `userRegistrationRequests` q  | Admin views all registration requests    |

---

## 1. Login

**What it does:** Verifies email + password and returns a JWT token.

**Three possible outcomes:**

| Scenario         | Response                                 | Next Step                                      |
| ---------------- | ---------------------------------------- | ---------------------------------------------- |
| First-time login | `is_first_login: true` + temporary token | Call `changePassword` with the temporary token |
| 2FA enabled      | `requires_otp: true` + OTP emailed       | Call `verifyOtp` with the 6-digit code         |
| Normal login     | Full JWT token returned                  | Use token for all subsequent requests          |

**How it works:**

1. Repository finds user by email.
2. Service checks password hash.
3. If `is_first_login = true` → returns temporary token with `first_login` claim.
4. If `two_factor_enabled = true` → generates 6-digit OTP, hashes and stores it, emails plain OTP, returns temporary token with `otp_pending` claim.
5. Otherwise → issues full JWT token, updates last login timestamp.

---

## 2. Register (Admin Only)

**What it does:** Creates a new user account. Only admins can do this.

**How it works:**

1. `@guard(with: ["api"])` validates the JWT token before the resolver runs.
2. Service verifies the authenticated user has the `admin` role.
3. Generates a random 16-character temporary password.
4. Creates user in database with `is_first_login = true`.
5. Assigns the `user` role via Spatie Permission.
6. Calls `UserRegistrationRequestService.markAsCreatedByEmail()` to update any matching pending registration request.
7. Emails the temporary password to the new user.

---

## 3. Change Password (First-Time Login)

**What it does:** Forces newly created users to set their own password.

**How it works:**

1. Gets the authenticated user from the JWT token.
2. Verifies `is_first_login = true` — if not, throws an error.
3. Updates the password.
4. Sets `is_first_login = false`.
5. Issues a full JWT token.

---

## 4. Verify OTP

**What it does:** Completes login for users with 2FA enabled.

**How it works:**

1. Finds user by email.
2. Checks if the provided OTP matches the stored hash and hasn't expired (5-minute window).
3. Clears the OTP from the database.
4. Issues a full JWT token.

---

## 5. Resend OTP

**What it does:** Sends a new OTP code to the user's email.

**How it works:**

1. Finds user by email.
2. Generates a new 6-digit OTP.
3. Stores the hash and emails the plain code.

---

## 6. Forgot Password

**What it does:** Starts the password reset process.

**How it works:**

1. Finds user by email.
2. If email doesn't exist → still returns a generic message (prevents email enumeration).
3. Generates a 64-character reset token.
4. Stores the hashed token in `password_resets` table (1-hour expiry).
5. Emails the plain token to the user.

---

## 7. Reset Password

**What it does:** Sets a new password using the reset token.

**How it works:**

1. Retrieves all non-expired reset records.
2. Hash-compares the provided token against each record.
3. Updates the user's password.
4. Deletes all reset tokens for that user.

---

## 8. Refresh Token

**What it does:** Exchanges a valid JWT for a new one.

**How it works:**

1. Parses the current JWT token.
2. Verifies it's within the refresh window (14 days default).
3. Returns a new token; the old one is invalidated.

---

## 9. Logout

**What it does:** Invalidates the current JWT token.

**How it works:**

1. Parses the JWT token.
2. Adds it to the blacklist.
3. Any future request with that token is rejected.

---

## 10. Get Current User (Me)

**What it does:** Returns the logged-in user's profile.

**How it works:**

1. `@guard` validates the JWT.
2. Service parses the token and returns the user.

---

## 11. Request Registration (Public)

**What it does:** Allows anyone to submit a registration request.

**How it works:**

1. Checks for a pending request with the same email → rejects duplicates.
2. Checks for an existing user account with the same email → rejects.
3. Stores the request with `status = pending`.
4. Emails all admin users a notification.

---

## 12. List Registration Requests (Admin)

**What it does:** Returns all registration requests. Supports optional status filtering.

**How it works:**

1. `@guard` validates the JWT.
2. `UserRegistrationRequestService.getAllRequests()` queries the database.
3. If `status` argument is provided, filters by that status.
4. Returns results ordered by newest first.
