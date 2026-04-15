# Full Source Code — GraphQL Schema

## `graphql/schema.graphql`

```graphql
scalar JSON
    @scalar(class: "Src\\Modules\\Authentication\\GraphQL\\Scalars\\JSON")

#import ../src/Modules/Authentication/GraphQL/inputs.graphql
#import ../src/Modules/Authentication/GraphQL/types.graphql
#import ../src/Modules/Authentication/GraphQL/queries.graphql
#import ../src/Modules/Authentication/GraphQL/mutations.graphql
```

---

## `src/Modules/Authentication/GraphQL/Scalars/JSON.php`

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

---

## `src/Modules/Authentication/GraphQL/inputs.graphql`

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

"Input for requesting user registration (public, no auth required)"
input RequestUserRegistrationInput {
    "Desired username (minimum 3 characters)"
    username: String! @rules(apply: ["required", "string", "min:3", "max:255"])

    "User's email address"
    email: String! @rules(apply: ["required", "string", "email", "max:255"])
}
```

---

## `src/Modules/Authentication/GraphQL/types.graphql`

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

"""
Request User Registration Response
Returned after a user submits a registration request.
"""
type RequestUserRegistrationResponse {
    success: Boolean!
    message: String!
}

"""
User Registration Request
Represents a single entry in the user_registration_requests table.
"""
type UserRegistrationRequest {
    id: ID!
    username: String!
    email: String!
    status: String!
    user_id: ID
    created_at: String!
    updated_at: String!
}
```

---

## `src/Modules/Authentication/GraphQL/queries.graphql`

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

---

## `src/Modules/Authentication/GraphQL/mutations.graphql`

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

    "Submit a registration request (public, no authentication required)"
    requestUserRegistration(
        input: RequestUserRegistrationInput!
    ): RequestUserRegistrationResponse
        @field(
            resolver: "Src\\Modules\\Authentication\\GraphQL\\Resolvers\\AuthResolver@requestUserRegistration"
        )
}
```
