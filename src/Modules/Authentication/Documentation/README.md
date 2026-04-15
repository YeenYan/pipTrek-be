# Authentication Module — Documentation

Complete technical documentation for the `src/Modules/Authentication` module.

---

## Navigation

| #   | Document                                                     | Description                                        |
| --- | ------------------------------------------------------------ | -------------------------------------------------- |
| 01  | [Overview](01_Overview.md)                                   | What the module does and why it exists             |
| 02  | [Folder Structure](02_Folder_Structure.md)                   | Every file and folder explained                    |
| 03  | [Architecture Flow](03_Architecture_Flow.md)                 | Layered architecture, login/register flow diagrams |
| 04  | [Authentication Features](04_Authentication_Features.md)     | All features: login, 2FA, password reset, etc.     |
| 05  | [User Registration Request](05_User_Registration_Request.md) | Public request flow + admin creation integration   |
| 06  | [GraphQL API](06_GraphQL_API.md)                             | Every mutation & query with examples               |
| 07  | [Postman Setup](07_Postman_Setup.md)                         | Step-by-step Postman testing guide                 |
| 08  | [Full Source Code](08_Full_Source_Code/)                     | Complete code dump of every file                   |
| 09  | [Rebuild Guide](09_Rebuild_Guide.md)                         | Recreate the module from scratch                   |

---

## Full Source Code Index

| Document                                                     | Contents                                                    |
| ------------------------------------------------------------ | ----------------------------------------------------------- |
| [Services.md](08_Full_Source_Code/Services.md)               | AuthenticationService, UserRegistrationRequestService       |
| [Resolvers.md](08_Full_Source_Code/Resolvers.md)             | AuthResolver                                                |
| [Repositories.md](08_Full_Source_Code/Repositories.md)       | AuthenticationRepository                                    |
| [Domain.md](08_Full_Source_Code/Domain.md)                   | Authentication model                                        |
| [Exceptions.md](08_Full_Source_Code/Exceptions.md)           | AuthenticationException, BusinessLogicException             |
| [GraphQL_Schema.md](08_Full_Source_Code/GraphQL_Schema.md)   | inputs, types, queries, mutations, JSON scalar, root schema |
| [Migrations.md](08_Full_Source_Code/Migrations.md)           | All module migrations                                       |
| [ServiceProvider.md](08_Full_Source_Code/ServiceProvider.md) | AuthenticationServiceProvider                               |
| [Mail_Classes.md](08_Full_Source_Code/Mail_Classes.md)       | OtpMail, TempPasswordMail, PasswordResetMail, etc.          |
| [Blade_Templates.md](08_Full_Source_Code/Blade_Templates.md) | All email HTML templates                                    |
| [External_Config.md](08_Full_Source_Code/External_Config.md) | auth.php, providers.php, SanitizedValidationErrorHandler    |

---

## Tech Stack

| Technology                     | Version | Role                      |
| ------------------------------ | ------- | ------------------------- |
| Laravel                        | 12      | PHP framework             |
| PHP                            | 8.2+    | Language                  |
| PostgreSQL                     | 15+     | Database                  |
| Lighthouse                     | 6.66    | GraphQL server            |
| php-open-source-saver/jwt-auth | 2.8     | JWT authentication        |
| Spatie Permission              | 6.25    | Role-based access control |

---

_Last updated: April 15, 2026_
