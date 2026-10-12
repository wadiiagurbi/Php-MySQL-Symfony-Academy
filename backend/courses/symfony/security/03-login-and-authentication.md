---
slug: login-and-authentication
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: security
chapterTitle: "Chapter 10 — Security & Authentication"
chapterOrder: 10
title: "Login and Authentication"
difficulty: intermediate
order: 3
estimatedMinutes: 12
tags: [symfony, security, login, authentication]
---

## Learning Objective

Understand how login (**authentication**) works in Symfony: how the login form, the **authenticator**, and the firewall cooperate to turn credentials into a logged-in **session**.

## Explanation

**Authentication** is the act of proving identity — showing credentials (an email plus password, or an API token) so the app can trust that you are who you claim to be. In Symfony this involves three cooperating pieces. The **login form** is an ordinary HTML form (or a JSON endpoint for APIs) that collects the credentials and POSTs them to a check path like `/login`. The **authenticator** is a PHP class implementing `AuthenticatorInterface` that reads those credentials from the request, loads the matching user via the **user provider** (the service, usually Doctrine-based, that fetches user objects from storage), and verifies the password hash. The **firewall** (the `main` entry in `security.yaml` you met in lesson 1) ties them together: it declares which authenticator guards which URLs and where to redirect on success or failure.

When verification succeeds, Symfony stores the authenticated user in the **session** — a server-side storage area keyed by a cookie in the visitor's browser that persists identity across requests (sessions are covered in depth in lesson 5). Every subsequent request reloads the user from the provider, so `$this->getUser()` in controllers and the `app.user` variable in Twig templates reflect the logged-in account. On failure, the firewall redisplays the login page with an error from `AuthenticationUtils` (a helper service exposing the last login error and username) instead of revealing whether the email or the password was wrong — a deliberate vagueness that denies attackers useful feedback.

Symfony's `make:auth` generator scaffolds a working form-login authenticator, so you rarely write one from scratch; your job is understanding the flow well enough to customize redirects, error messages, and remember-me behavior.

## Why It Matters

Login is the front door of your application and the most attacked endpoint in it. Teams customize failure messages, add rate limiting, and wire login events (such as updating "last seen at") through this exact pipeline. Knowing which piece owns which job — form collects, authenticator verifies, firewall orchestrates, session remembers — lets you debug "I log in but am immediately logged out" issues instead of guessing.

## Example

The task app needs a `/login` page: GET shows the form with the last error (if any), and the firewall's form-login authenticator verifies POSTed credentials, sending successful logins to the task list.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_tasks');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }
}
```

## Code Explanation

- `AuthenticationUtils $authUtils` is injected by the container; it exposes the previous attempt's username and error, stored by the firewall after a failed login.
- `$this->getUser()` returns the currently logged-in user or `null` — already-authenticated visitors skip the form and go straight to their tasks.
- `getLastUsername()` repopulates the email field so users do not retype it after a typo in the password.
- `getLastAuthenticationError()` carries the failure reason to the template, which displays a generic "Invalid credentials" message without leaking which half was wrong.
- The actual credential check never appears here: the firewall intercepts the POST to `/login` and runs the configured authenticator before this method renders anything for GET requests.

## Common Mistakes

- Writing manual password comparisons in the login controller instead of letting the authenticator verify hashes — hand-rolled checks skip salting, timing-safe comparison, and future algorithm upgrades.
- Revealing "email not found" versus "wrong password" in error messages. Distinct messages let attackers enumerate registered accounts one by one.
- Forgetting the `methods: ['GET', 'POST']` (or the firewall check path) so the POSTed form never reaches the authenticator and "login does nothing."

## Best Practices

- Scaffold with `make:auth` and customize the generated authenticator (redirects, remember-me) rather than inventing a login mechanism from scratch.
- Show one generic "Invalid credentials" error and repopulate only the username field — never echo the attempted password back.
- Redirect already-logged-in users away from the login page, and send fresh logins to a sensible landing page (dashboard or task list).

## Exercise

After a failed login, the form should redisplay with the attempted email filled in and a generic error shown. Name the two `AuthenticationUtils` methods that provide these values and explain why the error must stay generic.

## Solution

Use `getLastUsername()` to refill the email field and `getLastAuthenticationError()` to display the failure. The message stays generic ("Invalid credentials") so attackers cannot distinguish an unknown email from a wrong password, which would let them harvest the list of registered accounts.

## Mini Quiz

```yaml
- question: "Which three pieces cooperate to log a user in with form login?"
  options:
    - "The login form collects credentials, the authenticator verifies them via the user provider, and the firewall orchestrates the flow"
    - "Twig, Doctrine migrations, and the mailer"
    - "The router, the validator, and the cache pool"
    - "Three database tables: users, roles, and tokens"
  answer: 1
  explanation: "The form gathers input, the authenticator checks it against the stored hash using the user provider, and the firewall wires the check path, success, and failure handling together."
- question: "How does Symfony remember the user across requests after a successful login?"
  options:
    - "It rewrites every URL to include the password"
    - "It stores the authenticated identity in the session, keyed by a browser cookie"
    - "It asks for the password again on every page"
    - "It writes the password into a Twig global variable"
  answer: 1
  explanation: "Post-login, the user is kept in the server-side session linked by a cookie, so each new request reloads the identity without re-entering credentials."
- question: "Why should login errors stay generic instead of saying 'email not found'?"
  options:
    - "Generic messages render faster in Twig"
    - "Specific messages let attackers enumerate which emails are registered"
    - "Symfony cannot detect which half of the credentials failed"
    - "Databases forbid storing the reason for a failed login"
  answer: 1
  explanation: "Distinguishing unknown emails from wrong passwords hands attackers an oracle for harvesting registered accounts, so one vague message covers both cases."
```
