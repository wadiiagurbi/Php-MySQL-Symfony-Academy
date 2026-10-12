---
slug: security-and-firewalls
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: security
chapterTitle: "Chapter 10 — Security & Authentication"
chapterOrder: 10
title: "Security and Firewalls"
difficulty: intermediate
order: 1
estimatedMinutes: 12
tags: [symfony, security, firewall, authentication]
---

## Learning Objective

Understand Symfony's security vocabulary — **authentication** versus **authorization** — and how **firewalls** in `security.yaml` decide which parts of your app require a logged-in user.

## Explanation

Security starts with two questions that sound similar but are very different. **Authentication** ("authN") asks *who are you?* — proving identity with a login form, an API token, or similar credentials. **Authorization** ("authZ") asks *are you allowed to do this?* — checking whether the now-identified user may delete this task or view that admin page. Symfony handles both through one central configuration file, `config/packages/security.yaml`, so every rule lives in a single auditable place instead of being scattered across controllers.

The central concept in that file is the **firewall**: a named section (such as `main`) mapped to a URL pattern that defines *how* authentication works for matching requests. Despite the name, it has nothing to do with network firewalls — think of it as a security checkpoint at the entrance to part of your app. A typical setup has a `dev` firewall that bypasses security for the profiler toolbar, and a `main` firewall covering everything else with a **user provider** (the service that loads user objects, usually from the database via Doctrine, the database library) plus one or more **authenticators** (classes that implement a specific login mechanism, like form login or JSON login).

Below firewalls sit **access control** rules (`access_control:`), an ordered list of "URL pattern → required role" mappings evaluated top to bottom — for example, `path: ^/admin` requiring `ROLE_ADMIN`. A **role** is a simple string label like `ROLE_USER` or `ROLE_ADMIN` attached to each user that describes what category they belong to. The first matching rule wins, so ordering matters: specific rules first, general ones last.

## Why It Matters

Nearly every production Symfony app protects *something* — user accounts, admin sections, APIs — and `security.yaml` is where auditors, teammates, and future-you look first. Misconfiguring a firewall (or adding access rules in the wrong order) is how "anyone can open the admin panel" incidents happen. Understanding firewalls now makes the registration, login, and roles lessons that follow click into place.

## Example

The task app needs a public homepage but wants every URL under `/admin` to require a fully logged-in user. A `main` firewall handles authentication for the whole site, and one access-control rule locks down the admin section.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
```

## Code Explanation

- `#[IsGranted('ROLE_ADMIN')]` is a PHP attribute that denies access before the method runs unless the current user has the `ROLE_ADMIN` role — method-level authorization complementing the URL rules in `security.yaml`.
- Users without the role receive a `403 Forbidden` response (authenticated but not allowed) rather than reaching the dashboard code at all.
- Anonymous visitors are first intercepted by the `main` firewall, which can redirect them to the login page — authentication happens before this authorization check.
- Keeping this rule as an attribute places the requirement next to the code it protects, while broad URL patterns stay in `access_control:`.
- Together, firewall (who are you?) plus `IsGranted` (may you proceed?) implement the full authentication-then-authorization pipeline.

## Common Mistakes

- Confusing authentication with authorization: logging in successfully (authN) does not automatically grant admin pages (authZ) — roles and rules must also permit it.
- Putting a general rule like `path: ^/` before specific ones in `access_control:`. The first match wins, so the general rule shadows everything below it and the specific rules never apply.
- Securing only the Twig links (hiding an "Admin" button) while leaving the `/admin` route itself unprotected — attackers request URLs directly, so every sensitive route needs server-side enforcement.

## Best Practices

- Order `access_control` from most specific to most general, and keep the list short enough to review at a glance.
- Enforce authorization server-side on every sensitive route (via `access_control` or `#[IsGranted]`), never by merely hiding links in templates.
- Use one `main` firewall for the app plus the `dev` bypass, and run `php bin/console debug:firewall` to verify which firewall guards each URL.

## Exercise

Your app has public pages plus an `/account` section for any logged-in user. Describe the two pieces you would configure: which firewall setup handles authentication, and which access-control rule requires login for `/account`.

## Solution

Keep the standard `main` firewall with your user provider and login authenticator so the whole site shares one authentication checkpoint. Then add an access-control entry `path: ^/account` requiring `ROLE_USER` (the baseline role every logged-in user has) placed above any general rules — anonymous visitors to `/account` get redirected to login, while public pages stay open.

## Mini Quiz

```yaml
- question: "What is the difference between authentication and authorization?"
  options:
    - "They are two names for exactly the same thing"
    - "Authentication proves who you are; authorization checks whether you may perform an action"
    - "Authorization happens first and authentication second"
    - "Authentication only applies to APIs, authorization only to HTML pages"
  answer: 1
  explanation: "AuthN establishes identity (login), authZ decides permissions for that identity (roles and rules); Symfony handles both via security.yaml."
- question: "What is a firewall in Symfony's security.yaml?"
  options:
    - "A network device blocking hacker IP addresses"
    - "A named configuration section mapping URL patterns to authentication behavior (user provider plus authenticators)"
    - "A Twig filter that escapes dangerous HTML"
    - "A database table storing blocked users"
  answer: 1
  explanation: "A Symfony firewall is a security checkpoint for matching requests, defining how users authenticate — unrelated to network firewall hardware."
- question: "Why does the order of access_control rules matter?"
  options:
    - "It only affects the speed of the page, not behavior"
    - "Rules are evaluated top to bottom and the first match wins, so a general rule can shadow specific ones below it"
    - "Symfony sorts them alphabetically at runtime"
    - "Order matters only for API routes, not HTML pages"
  answer: 1
  explanation: "Because evaluation stops at the first matching rule, specific patterns must come before general ones or they will never take effect."
```
