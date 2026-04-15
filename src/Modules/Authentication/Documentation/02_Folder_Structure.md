# 02 — Folder Structure

## Module Directory Tree

```
src/Modules/Authentication/
│
├── AuthenticationServiceProvider.php
│
├── Domain/
│   └── Authentication.php
│
├── Application/
│   ├── Services/
│   │   ├── AuthenticationService.php
│   │   └── UserRegistrationRequestService.php
│   └── Exceptions/
│       ├── AuthenticationException.php
│       └── BusinessLogicException.php
│
├── Infrastructure/
│   ├── Repositories/
│   │   └── AuthenticationRepository.php
│   └── Database/
│       └── migrations/
│           ├── 2026_04_15_000001_create_user_registration_requests_table.php
│           └── 2026_04_15_000002_add_user_id_to_user_registration_requests_table.php
│
├── GraphQL/
│   ├── Resolvers/
│   │   └── AuthResolver.php
│   ├── Scalars/
│   │   └── JSON.php
│   ├── inputs.graphql
│   ├── types.graphql
│   ├── queries.graphql
│   └── mutations.graphql
│
└── Documentation/
    ├── README.md
    ├── 01_Overview.md
    ├── 02_Folder_Structure.md
    ├── 03_Architecture_Flow.md
    ├── 04_Authentication_Features.md
    ├── 05_User_Registration_Request.md
    ├── 06_GraphQL_API.md
    ├── 07_Postman_Setup.md
    ├── 08_Full_Source_Code/
    └── 09_Rebuild_Guide.md
```

## File-by-File Explanation

### Root

| File                                | Purpose                                                                                 |
| ----------------------------------- | --------------------------------------------------------------------------------------- |
| `AuthenticationServiceProvider.php` | Registers all module classes with Laravel's service container. Loads module migrations. |

### Domain/

| File                 | Purpose                                                                                                                                                           |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Authentication.php` | Eloquent model representing a user. Maps to the `users` table. Implements `JWTSubject` for JWT token generation and uses Spatie `HasRoles` for role-based access. |

### Application/Services/

| File                                 | Purpose                                                                                                                                                                             |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AuthenticationService.php`          | Contains ALL authentication business logic: login, register, OTP, password reset, token management. Delegates to `UserRegistrationRequestService` for registration request updates. |
| `UserRegistrationRequestService.php` | Handles registration request operations: marking requests as "created" when admin creates a user, and listing all requests.                                                         |

### Application/Exceptions/

| File                          | Purpose                                                                                         |
| ----------------------------- | ----------------------------------------------------------------------------------------------- |
| `AuthenticationException.php` | Thrown when authentication fails (wrong password, invalid token). Category: `authentication`.   |
| `BusinessLogicException.php`  | Thrown when a business rule is violated (expired OTP, duplicate request). Category: `business`. |

### Infrastructure/Repositories/

| File                           | Purpose                                                                                                    |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------- |
| `AuthenticationRepository.php` | ALL database operations: CRUD for users, OTP storage/verification, password resets, registration requests. |

### Infrastructure/Database/migrations/

| File                                                                    | Purpose                                                                                           |
| ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `2026_04_15_000001_create_user_registration_requests_table.php`         | Creates the `user_registration_requests` table with uuid id, username, email, status, timestamps. |
| `2026_04_15_000002_add_user_id_to_user_registration_requests_table.php` | Adds a nullable `user_id` UUID foreign key column to `user_registration_requests`.                |

### GraphQL/Resolvers/

| File               | Purpose                                                                                                               |
| ------------------ | --------------------------------------------------------------------------------------------------------------------- |
| `AuthResolver.php` | Thin GraphQL resolver. Receives all mutations/queries and delegates to service classes. Contains zero business logic. |

### GraphQL/Scalars/

| File       | Purpose                                                                                                    |
| ---------- | ---------------------------------------------------------------------------------------------------------- |
| `JSON.php` | Custom GraphQL scalar type that allows returning flexible JSON data (used by login, forgotPassword, etc.). |

### GraphQL Schema Files

| File                | Purpose                                                                                                                                                                     |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `inputs.graphql`    | Defines input types: LoginInput, RegisterInput, ChangePasswordInput, VerifyOtpInput, ResendOtpInput, ForgotPasswordInput, ResetPasswordInput, RequestUserRegistrationInput. |
| `types.graphql`     | Defines response types: User, RegisterResponse, ChangePasswordResponse, OtpVerificationResponse, RequestUserRegistrationResponse, UserRegistrationRequest.                  |
| `queries.graphql`   | Defines queries: `me` (get current user), `userRegistrationRequests` (list requests).                                                                                       |
| `mutations.graphql` | Defines mutations: login, register, changePassword, verifyOtp, resendOtp, forgotPassword, resetPassword, refreshToken, logout, requestUserRegistration.                     |

## Related Files Outside the Module

| File                                  | Location                     | Purpose                                             |
| ------------------------------------- | ---------------------------- | --------------------------------------------------- |
| `OtpMail.php`                         | `app/Mail/`                  | Mailable class for OTP emails                       |
| `TempPasswordMail.php`                | `app/Mail/`                  | Mailable class for temporary password emails        |
| `PasswordResetMail.php`               | `app/Mail/`                  | Mailable class for password reset emails            |
| `RegistrationRequestMail.php`         | `app/Mail/`                  | Mailable class for admin registration notifications |
| `otp.blade.php`                       | `resources/views/emails/`    | HTML template for OTP email                         |
| `temp-password.blade.php`             | `resources/views/emails/`    | HTML template for temporary password email          |
| `password-reset.blade.php`            | `resources/views/emails/`    | HTML template for password reset email              |
| `registration-request.blade.php`      | `resources/views/emails/`    | HTML template for registration request notification |
| `SanitizedValidationErrorHandler.php` | `app/GraphQL/ErrorHandlers/` | Cleans error responses for frontend consumption     |
| `schema.graphql`                      | `graphql/`                   | Root GraphQL schema — imports all module schemas    |
| `auth.php`                            | `config/`                    | Configures JWT guard and Authentication model       |
| `providers.php`                       | `bootstrap/`                 | Registers AuthenticationServiceProvider             |
