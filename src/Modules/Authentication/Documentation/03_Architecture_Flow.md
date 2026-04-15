# 03 — Architecture Flow

## Layered Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CLIENT (Postman / Frontend)                  │
│                  Sends a GraphQL request to /graphql                │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   GRAPHQL LAYER (Presentation)                                      │
│   Files: AuthResolver.php, *.graphql schema files                   │
│                                                                     │
│   Job: Receive the request, validate input with @rules directives,  │
│        and pass data to the Service layer.                           │
│   Does NOT contain any business logic.                              │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   APPLICATION LAYER (Business Logic)                                │
│   Files: AuthenticationService.php,                                 │
│          UserRegistrationRequestService.php, Exceptions             │
│                                                                     │
│   Job: Apply ALL business rules.                                    │
│   Examples: Check admin role, generate passwords, send emails,      │
│   issue JWT tokens, mark registration requests as created.          │
│   Does NOT talk to the database directly.                           │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   INFRASTRUCTURE LAYER (Data Access)                                │
│   Files: AuthenticationRepository.php, migrations                   │
│                                                                     │
│   Job: ALL database operations.                                     │
│   Examples: Find user by email, save OTP, create reset token,       │
│   mark registration request as created, list all requests.          │
│   Does NOT contain any business rules.                              │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│   DOMAIN LAYER (Data Model)                                         │
│   Files: Authentication.php                                         │
│                                                                     │
│   Job: Define what a "user" looks like in the database.             │
│   Maps PHP code to the database table.                              │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Login Flow

```
Client                 AuthResolver          AuthenticationService       AuthenticationRepository       Database
  │                        │                         │                           │                        │
  │  POST /graphql         │                         │                           │                        │
  │  mutation login(...)   │                         │                           │                        │
  │───────────────────────>│                         │                           │                        │
  │                        │  loginUser($data)       │                           │                        │
  │                        │────────────────────────>│                           │                        │
  │                        │                         │  findUserByEmail()        │                        │
  │                        │                         │─────────────────────────>│  SELECT * FROM users    │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │  user data / null       │
  │                        │                         │                           │<───────────────────────│
  │                        │                         │  Hash::check(password)    │                        │
  │                        │                         │                           │                        │
  │                        │                         │  [If first login]         │                        │
  │                        │                         │  → return temp token      │                        │
  │                        │                         │                           │                        │
  │                        │                         │  [If 2FA enabled]         │                        │
  │                        │                         │  → generateOtp()          │                        │
  │                        │                         │  → return temp token      │                        │
  │                        │                         │                           │                        │
  │                        │                         │  [Normal login]           │                        │
  │                        │                         │  → issueJwtToken()        │                        │
  │                        │                         │  → updateLastLogin()      │                        │
  │                        │                         │                           │                        │
  │                        │  { user, token, ... }   │                           │                        │
  │                        │<────────────────────────│                           │                        │
  │  { user, token, ... }  │                         │                           │                        │
  │<───────────────────────│                         │                           │                        │
```

---

## Registration Flow (Admin Creates User)

```
Admin Client           AuthResolver          AuthenticationService       UserRegReqService      AuthenticationRepository       Database
  │                        │                         │                        │                        │                           │
  │  POST /graphql         │                         │                        │                        │                           │
  │  mutation register(...)│                         │                        │                        │                           │
  │  + Bearer token        │                         │                        │                        │                           │
  │───────────────────────>│                         │                        │                        │                           │
  │                        │                         │                        │                        │                           │
  │                 @guard validates JWT             │                        │                        │                           │
  │                        │                         │                        │                        │                           │
  │                        │  registerUser($data)    │                        │                        │                           │
  │                        │────────────────────────>│                        │                        │                           │
  │                        │                         │  getAuthenticatedUser() │                        │                           │
  │                        │                         │  hasRole('admin')?      │                        │                           │
  │                        │                         │                         │                        │                           │
  │                        │                         │  createUser()           │                        │                           │
  │                        │                         │────────────────────────────────────────────────>│  INSERT INTO users         │
  │                        │                         │                         │                        │─────────────────────────>│
  │                        │                         │                         │                        │  new user (UUID)          │
  │                        │                         │                         │                        │<─────────────────────────│
  │                        │                         │                         │                        │                           │
  │                        │                         │  assignRole('user')     │                        │                           │
  │                        │                         │                         │                        │                           │
  │                        │                         │  markAsCreatedByEmail() │                        │                           │
  │                        │                         │───────────────────────>│                        │                           │
  │                        │                         │                        │  markRegistration...   │                           │
  │                        │                         │                        │───────────────────────>│  UPDATE                    │
  │                        │                         │                        │                        │  user_registration_requests│
  │                        │                         │                        │                        │  SET status='created',     │
  │                        │                         │                        │                        │      user_id=<uuid>        │
  │                        │                         │                        │                        │  WHERE email=<email>       │
  │                        │                         │                        │                        │    AND status='pending'    │
  │                        │                         │                        │                        │─────────────────────────>│
  │                        │                         │                        │                        │                           │
  │                        │                         │  Mail::send(TempPwd)   │                        │                           │
  │                        │                         │                         │                        │                           │
  │                        │  { user, message }      │                        │                        │                           │
  │                        │<────────────────────────│                        │                        │                           │
  │  { user, message }     │                         │                        │                        │                           │
  │<───────────────────────│                         │                        │                        │                           │
```

---

## Registration Request Flow (Public User)

```
Public User            AuthResolver          AuthenticationService       AuthenticationRepository       Database
  │                        │                         │                           │                        │
  │  POST /graphql         │                         │                           │                        │
  │  requestUserReg(...)   │                         │                           │                        │
  │  NO auth needed        │                         │                           │                        │
  │───────────────────────>│                         │                           │                        │
  │                        │ requestUserReg($data)   │                           │                        │
  │                        │────────────────────────>│                           │                        │
  │                        │                         │  findPendingReq(email)    │                        │
  │                        │                         │─────────────────────────>│  SELECT WHERE pending   │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │  null (no duplicate)    │
  │                        │                         │                           │<───────────────────────│
  │                        │                         │  findUserByEmail()        │                        │
  │                        │                         │─────────────────────────>│  SELECT * FROM users    │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │  null (no account)      │
  │                        │                         │                           │<───────────────────────│
  │                        │                         │  createRegistrationReq()  │                        │
  │                        │                         │─────────────────────────>│  INSERT INTO             │
  │                        │                         │                           │  user_reg_requests       │
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │                        │
  │                        │                         │  Send email to ALL admins │                        │
  │                        │                         │  (RegistrationRequestMail)│                        │
  │                        │                         │                           │                        │
  │                        │  { success, message }   │                           │                        │
  │                        │<────────────────────────│                           │                        │
  │  { success, message }  │                         │                           │                        │
  │<───────────────────────│                         │                           │                        │
```

---

## List Registration Requests Flow (Admin)

```
Admin Client           AuthResolver          UserRegReqService           AuthenticationRepository       Database
  │                        │                         │                           │                        │
  │  POST /graphql         │                         │                           │                        │
  │  query userRegReqs     │                         │                           │                        │
  │  + Bearer token        │                         │                           │                        │
  │───────────────────────>│                         │                           │                        │
  │                        │                         │                           │                        │
  │                 @guard validates JWT             │                           │                        │
  │                        │                         │                           │                        │
  │                        │ getAllRequests($status)  │                           │                        │
  │                        │────────────────────────>│                           │                        │
  │                        │                         │ getAllRegistrationReqs()   │                        │
  │                        │                         │─────────────────────────>│  SELECT * FROM           │
  │                        │                         │                           │  user_reg_requests       │
  │                        │                         │                           │  ORDER BY created_at DESC│
  │                        │                         │                           │───────────────────────>│
  │                        │                         │                           │  [array of requests]    │
  │                        │                         │                           │<───────────────────────│
  │                        │  [array of requests]    │                           │                        │
  │                        │<────────────────────────│                           │                        │
  │  [array of requests]   │                         │                           │                        │
  │<───────────────────────│                         │                           │                        │
```

---

## Service Dependency Diagram

```
AuthenticationServiceProvider
        │
        ├── registers → AuthenticationRepository (singleton)
        │
        ├── registers → UserRegistrationRequestService (singleton)
        │                     └── depends on → AuthenticationRepository
        │
        └── registers → AuthenticationService (singleton)
                              ├── depends on → AuthenticationRepository
                              └── depends on → UserRegistrationRequestService
```

```
AuthResolver
    ├── depends on → AuthenticationService
    └── depends on → UserRegistrationRequestService
```
