---
slug: registration-and-hashing
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: security
chapterTitle: "Chapter 10 — Security & Authentication"
chapterOrder: 10
title: "Registration and Password Hashing"
difficulty: intermediate
order: 2
estimatedMinutes: 12
tags: [symfony, security, registration, passwords]
---

## Learning Objective

Learn how user registration works in Symfony and why passwords must be stored as **hashes** (one-way fingerprints) rather than plain text, using the password hasher service.

## Explanation

**Registration** is the process of creating a new user account: collecting an email and password through a form, validating them, and persisting a new `User` **entity** (a PHP class mapped by Doctrine, the database library, to a database table row) to the database. Symfony does not generate this flow for you automatically — you write a controller that builds the user, but the security system provides the critical building blocks: the `User` class implementing `UserInterface` (the interface marking "this object can log in," requiring methods like `getUserIdentifier()` and `getRoles()`), and the **password hasher** service that protects the password.

**Hashing** transforms a password into a fixed-length, irreversible string: `hash('s3cret-pw')` becomes something like `$2y$13$...` from which the original password cannot be recovered, even by you. When the user later logs in, Symfony hashes the entered password the same way and compares the two hashes — the real password never needs to be stored or read back. This is fundamentally different from **encryption**, which is reversible with a key: encrypted passwords can be decrypted after a database leak, while properly hashed ones cannot. Symfony's hasher (bcrypt-based via `UserPasswordHasherInterface`) additionally adds a unique random **salt** per password and a configurable computational cost, defeating precomputed lookup tables.

The registration controller therefore never assigns the raw password to the entity. It calls `$hasher->hashPassword($user, $plainPassword)`, stores the resulting hash in the user's `password` column, and erases the plain value from memory. Validation from the Validator chapter (email format, minimum length) runs first so weak input never reaches the hasher.

## Why It Matters

Storing plain-text or reversibly encrypted passwords is among the most damaging mistakes a backend can make — a single database backup leak then compromises every user's password, including ones reused on other sites. Professional teams treat correct hashing as non-negotiable, and code reviewers check every registration flow for exactly this pattern: hash before persist, never store the plain value.

## Example

The task app needs a `/register` page where a new user submits an email and password. The controller validates the input, hashes the password with the framework hasher, and saves the new user with a default `ROLE_USER` role.

## Code

```php
<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $user = new User(
            (string) $request->request->get('email', ''),
            (string) $request->request->get('displayName', '')
        );

        $plainPassword = (string) $request->request->get('password', '');
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }
}
```

## Code Explanation

- `UserPasswordHasherInterface $hasher` is injected by the **service container** (the central registry that builds services from constructor and method type-hints) — no manual setup needed.
- `$hasher->hashPassword($user, $plainPassword)` produces the irreversible hash; only this hash is stored via `setPassword()`, never the raw password.
- `$user->setRoles(['ROLE_USER'])` gives every new account the baseline logged-in role used by access rules from the previous lesson.
- `EntityManagerInterface` is Doctrine's persistence service: `persist()` stages the new user and `flush()` executes the SQL `INSERT`.
- After successful registration the user is redirected to the login page — a common pattern so the new account signs in through the normal, audited login flow.

## Common Mistakes

- Storing `$request->request->get('password')` directly on the user without hashing. The database then holds plain-text passwords — a critical vulnerability.
- Hashing twice (hashing an already-hashed value on profile updates). Login compares `hash(entered)` against the stored value, so double-hashing permanently locks the user out.
- Forgetting to erase or overwrite the plain password variable in long-running processes. Keep its lifetime as short as possible and never log or dump it.

## Best Practices

- Always hash with `UserPasswordHasherInterface` — never MD5, SHA1, or hand-rolled schemes, which are far too fast and unsalted for password storage.
- Validate email and password strength (length, format) *before* hashing so junk input fails fast with clear form errors.
- Assign the least-privileged default role (`ROLE_USER`) at registration and grant stronger roles only through an explicit, reviewed admin action.

## Exercise

A teammate's registration code calls `$user->setPassword($request->request->get('password'))` and persists. Name the vulnerability, explain its real-world impact in two sentences, and state the one-line fix.

## Solution

The vulnerability is plain-text password storage: the database holds recoverable passwords, so any backup or SQL-injection leak exposes every user's actual password (often reused elsewhere). Fix it by hashing first: `$user->setPassword($hasher->hashPassword($user, $plainPassword))` with an injected `UserPasswordHasherInterface`, persisting only the hash.

## Mini Quiz

```yaml
- question: "Why must passwords be hashed instead of stored as plain text?"
  options:
    - "Hashed passwords take up less disk space"
    - "Hashing is one-way, so a database leak does not reveal usable passwords, while login can still verify by comparing hashes"
    - "Plain-text passwords cannot be displayed in Twig templates"
    - "Databases refuse to store plain-text strings"
  answer: 1
  explanation: "A hash cannot be reversed into the password, so stolen hashes are useless to attackers; verification re-hashes the login attempt and compares the results."
- question: "What is the difference between hashing and encryption for passwords?"
  options:
    - "There is no difference"
    - "Encryption is reversible with a key while hashing is one-way, which is why passwords must be hashed, not encrypted"
    - "Hashing is reversible and encryption is not"
    - "Encryption is faster and therefore better for passwords"
  answer: 1
  explanation: "Encrypted data can be decrypted by anyone holding the key, but a proper password hash can never be turned back into the password."
- question: "What does $hasher->hashPassword($user, $plainPassword) return and where does it go?"
  options:
    - "It returns the plain password and it is emailed to the user"
    - "It returns an irreversible hash that is stored on the user entity instead of the plain password"
    - "It logs the user in immediately and returns a Response"
    - "It deletes the user from the database"
  answer: 1
  explanation: "The hasher produces a salted, irreversible fingerprint of the password; the entity persists only that hash, never the original secret."
```
