---
slug: user-entity-and-authentication
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "The User Entity and Authentication"
difficulty: advanced
order: 3
estimatedMinutes: 14
tags: [final-project, task-manager, authentication, security]
---

## Learning Objective

By the end of this lesson, you will be able to explain how the project's `User` entity satisfies Symfony's security interfaces, how registration hashes a password safely, and how login exchanges credentials for a JWT without a dedicated controller action.

## Explanation

Symfony's security component doesn't care what your user table looks like — it only cares that your user class implements two interfaces: `UserInterface` (identity: "who is this and what roles do they have") and `PasswordAuthenticatedUserInterface` (credentials: "what's their hashed password"). The project's `User` entity implements both:

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
```

`getUserIdentifier()` returns the email — that's the value Symfony's security system uses to look the user up during authentication, matching the `provider` configuration (`property: email`) in `security.yaml`. `getRoles()` always appends `ROLE_USER` even if `$roles` is empty, guaranteeing every account can pass an `IS_AUTHENTICATED_FULLY` check without needing a separate "assign default role" step during registration. `eraseCredentials()` is required by the interface but is a no-op here, since the entity never holds a raw, unhashed password in memory beyond the moment it's hashed.

Registration (`POST /api/auth/register`) is a real controller action in `AuthController`. It validates a `RegisterRequest` DTO (email format, password minimum length, non-blank display name), checks for a duplicate email via `UserRepository::findOneByEmail`, and — critically — never stores the raw password:

```php
$user->setPassword($passwordHasher->hashPassword($user, $data->password));
```

Login (`POST /api/auth/login`), by contrast, has **no controller method at all**. It's configured declaratively in `security.yaml` as a `json_login` firewall: Symfony reads `email`/`password` from the JSON body, authenticates against the same `app_user_provider`, and on success delegates to `lexik_jwt_authentication.handler.authentication_success`, which issues a signed JWT. On failure, `authentication_failure` returns a 401. This is the Lexik JWT bundle doing exactly the job it exists for — turning a successful Symfony authentication into a bearer token.

## Why It Matters

This split — a real controller for registration, pure configuration for login — is a common and deliberate pattern in production Symfony APIs. Login doesn't need custom business logic (there's nothing to validate beyond "do these credentials match"), so writing a controller for it would just be boilerplate around what the security component already does declaratively. Recognizing when a feature needs a controller versus when it's fully expressible as firewall configuration is a sign of Symfony fluency, and it keeps `AuthController` focused on the one thing that actually needs custom code: registration's duplicate-email check and password hashing.

## Example

The full authentication flow for a new user:

1. `POST /api/auth/register` with `{email, password, displayName}` → `AuthController::register` hashes the password and persists a `User`, returns `201` with the public user fields (never the hash).
2. `POST /api/auth/login` with `{email, password}` → the `login` firewall's `json_login` authenticates against `app_user_provider`, and the success handler returns `{"token": "..."}`.
3. Every subsequent request sends `Authorization: Bearer <token>`, which the `api` firewall's `jwt: ~` authenticator verifies on each request — `stateless: true` means Symfony never stores a session; the token alone carries identity.

## Code

```php
<?php
// Adapted from src/Controller/Api/AuthController.php

class AuthController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        RequestValidator $requestValidator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $requestValidator->fromJson($request, RegisterRequest::class);

        if (null !== $userRepository->findOneByEmail($data->email)) {
            return $this->json(['error' => ['message' => 'An account with this email already exists.']], 409);
        }

        $user = new User($data->email, $data->displayName);
        $user->setPassword($passwordHasher->hashPassword($user, $data->password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
        ], 201);
    }
}
```

## Code Explanation

`RequestValidator::fromJson` deserializes the JSON body straight into a `RegisterRequest` DTO and validates it — invalid input (bad email format, short password) never reaches this method body at all; it throws before we get here, and `ApiExceptionListener` turns that into a 422. The duplicate-email check runs *before* any password hashing, avoiding needless CPU work for a request that's going to fail anyway. `UserPasswordHasherInterface::hashPassword($user, $data->password)` is passed the user instance (not just the raw string) because the hasher configuration in `security.yaml` is keyed by class (`App\Entity\User: 'auto'`), letting Symfony pick a strong algorithm automatically. The response deliberately hand-picks three fields instead of returning the whole entity — the password hash, roles array, and other internals never leave the server.

## Common Mistakes

- Storing `$data->password` directly on the entity, or hashing it in the entity's constructor — hashing belongs in the controller/service layer where the hasher service is available, not in the entity.
- Returning the full `User` entity (or an array built from all its getters) from `register()`, which would leak the password hash into the JSON response.
- Writing a custom login controller that manually checks credentials and issues a JWT, duplicating what `json_login` + Lexik's success handler already does correctly and more securely.
- Forgetting `stateless: true` on API firewalls, which would make Symfony try to start a PHP session for a JWT-based API that should never rely on one.

## Best Practices

- Let the security component's declarative configuration do as much as possible; only write a controller action when there's real business logic (duplicate checks, custom fields) beyond authentication itself.
- Always hash passwords through `UserPasswordHasherInterface`, never `password_hash()` directly, so the algorithm stays centrally configurable via `security.yaml`.
- Keep a single source of truth for "what does the client get back" — a small, explicit array (or a DTO like `TaskView`) rather than serializing the raw entity.

## Exercise

Add a `PUT /api/me/password` endpoint that lets an authenticated user change their password, given their current password and a new one. Decide which service/DTO you'd reuse or add, and what should happen if the current password doesn't match.

## Solution

Add a `ChangePasswordRequest` DTO with `currentPassword` and `newPassword` (validated with the same `Assert\Length(min: 8)` rule as registration). In a new controller method (or extending `MeController`), inject `UserPasswordHasherInterface`, fetch `$this->getUser()`, and call `$passwordHasher->isPasswordValid($user, $data->currentPassword)`. If it returns `false`, return a `403` (or `422` with a field-level message) — never reveal whether the *account* exists, just that the credentials are wrong. If valid, hash `$data->newPassword` the same way registration does, call `setPassword`, flush, and return `204`.

## Mini Quiz

```yaml
- question: "Which two interfaces does the User entity implement to work with Symfony's security component?"
  options:
    - "Serializable and JsonSerializable"
    - "UserInterface and PasswordAuthenticatedUserInterface"
    - "EntityInterface and AuthenticatableInterface"
    - "Authenticatable and Hashable"
  answer: 1
  explanation: "UserInterface provides identity (getUserIdentifier, getRoles) and PasswordAuthenticatedUserInterface provides the hashed password Symfony checks during authentication."

- question: "Why is there no controller method for POST /api/auth/login in this project?"
  options:
    - "Login is unimplemented"
    - "It's handled declaratively by the json_login firewall, which delegates token issuance to Lexik JWT's success handler"
    - "Login happens automatically after registration"
    - "It's implemented in MeController instead"
  answer: 1
  explanation: "The login firewall in security.yaml uses json_login with success/failure handlers provided by LexikJWTAuthenticationBundle — no custom controller code is needed."

- question: "Why does AuthController::register check for a duplicate email before hashing the password?"
  options:
    - "Hashing must happen after persistence, not before"
    - "It avoids unnecessary hashing work for a request that will fail anyway"
    - "Doctrine requires unique checks before any property is set"
    - "Password hashers cannot run inside a try/catch block"
  answer: 1
  explanation: "Password hashing is intentionally slow (that's what makes it secure), so checking for a duplicate email first avoids paying that cost on a registration that's going to be rejected regardless."
```
