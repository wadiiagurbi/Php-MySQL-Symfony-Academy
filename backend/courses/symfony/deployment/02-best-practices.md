---
slug: best-practices
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: deployment
chapterTitle: "Chapter 17 — Deployment"
chapterOrder: 17
title: "Symfony Best Practices"
difficulty: intermediate
order: 2
estimatedMinutes: 10
tags: [symfony, best-practices, code-quality]
---

## Learning Objective

Learn the core Symfony best practices — thin controllers, business logic in services, environment-based configuration — that keep a codebase maintainable as it grows.

## Explanation

Best practices are team-tested conventions that prevent the most common ways codebases rot. The first and most repeated in Symfony is **thin controllers, fat-free templates, smart services**: controllers should only translate HTTP (read the request, call a service, return a response) and stay under a few dozen lines. The moment a controller computes prices, sends email, or parses CSVs, that logic belongs in a **service** (a reusable class in `src/Service/` managed by the container) where it can be reused by console commands and unit-tested without HTTP. Templates, likewise, display data but never fetch or compute it.

Second, **configuration belongs in the environment, not in code**. Database URLs, API keys, and mailer credentials differ between your laptop, staging, and production, so Symfony reads them from **environment variables** (named values supplied outside the code, via `.env` files locally and real secrets in production) accessed through `%env(...)%` parameters. Hard-coding `new PDO('mysql:host=localhost;...', 'root', 'secret')` in a class guarantees broken deploys and leaked credentials in git history — the `.env` file is committed with safe placeholders while `.env.local` (git-ignored) holds your real local values.

Third, follow the framework's structural conventions: entities in `src/Entity/`, business rules in services, one action per controller method with explicit route methods, validation via constraints rather than `if`-ladders, and authorization checks on every sensitive route. Conventions compound: when every feature looks the same shape, onboarding takes days instead of weeks and code review focuses on logic rather than archaeology.

## Why It Matters

Teams do not fail from one bad line but from hundreds of small deviations that make the codebase unpredictable — the 400-line controller nobody dares touch, the password committed to git, the "temporary" query in a template that became load-bearing. Interviewers and reviewers probe exactly these habits: explaining *why* logic lives in a service signals professional-grade judgment beyond syntax knowledge.

## Example

A bloated controller that validates input, computes an order total, sends confirmation email, and persists — all inline — is refactored so each responsibility moves to its proper home while the controller keeps only HTTP translation.

## Code

```php
<?php

namespace App\Controller;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    public function __construct(private OrderService $orders)
    {
    }

    #[Route('/orders', name: 'app_orders_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $total = $this->orders->placeOrder(
            (string) $request->request->get('product', ''),
            (int) $request->request->get('quantity', 1),
        );

        $this->addFlash('success', sprintf('Order placed: %d EUR.', $total));

        return $this->redirectToRoute('app_orders');
    }
}
```

## Code Explanation

- The controller does only HTTP work: reading two form fields, delegating to the service, flashing a confirmation, and redirecting — no pricing math, no mailing, no SQL.
- `OrderService $orders` arrives via constructor injection; pricing, validation, persistence, and email live inside `placeOrder()`, reusable from commands and testable without HTTP.
- Explicit `methods: ['POST']` documents that this route changes state, keeping GET/POST semantics clean for caches and crawlers.
- `addFlash()` plus redirect follows the Post/Redirect/Get pattern, so refreshing the confirmation page never resubmits the order.
- Any future caller (an API controller, a console importer) reuses the same audited `placeOrder()` instead of copying its logic.

## Common Mistakes

- Letting controllers accumulate "just one more line" until they own pricing, mailing, and persistence — each addition feels cheap until the method is untestable and unreusable.
- Committing real credentials into `.env` or code "temporarily." Git history never forgets; use `.env.local` (ignored) locally and secret storage in production.
- Validating with hand-rolled `if` checks in controllers instead of Validator constraints, scattering the same rules across forms, APIs, and imports with inevitable drift.

## Best Practices

- Keep controllers to HTTP translation (under ~30 lines per action); move every rule, calculation, and side effect into injected services.
- Read all environment-specific values from env vars with safe committed defaults, and keep secrets out of git entirely.
- Reuse the framework's pipelines — validation constraints, voters, serializer groups, Messenger — instead of reimplementing their jobs inline.

## Exercise

A controller action currently (1) reads form data, (2) checks the email format with regex, (3) computes a discount, (4) saves via Doctrine, (5) sends email. State what stays in the controller and where each of the other four responsibilities moves.

## Solution

The controller keeps only (1) reading input plus returning the response (redirect/flash). Email-format checking becomes a Validator `Email` constraint on the DTO or entity; discount math moves to a pricing service; persistence stays behind a repository/service call; sending moves to a mailer service (or Messenger message). Each piece gains one home, one test, and reuse across HTTP, CLI, and API entry points.

## Mini Quiz

```yaml
- question: "What should a controller action contain, according to Symfony best practices?"
  options:
    - "All business logic, so everything is in one easy-to-find place"
    - "Only HTTP translation: read the request, delegate to services, return the response"
    - "Raw SQL queries for maximum performance"
    - "Hard-coded credentials for the services it calls"
  answer: 1
  explanation: "Thin controllers delegate rules and side effects to testable, reusable services, staying small enough to understand at a glance."
- question: "Why must credentials live in environment variables instead of code?"
  options:
    - "Environment variables execute faster than PHP constants"
    - "Values differ per environment and secrets committed to git leak permanently through history"
    - "PHP cannot store strings in class properties"
    - "Twig templates cannot read hard-coded values"
  answer: 1
  explanation: "Env-based config lets each deployment supply its own database and keys, while committed secrets are exposed forever to everyone with repo access."
- question: "What is wrong with validating the same email field with custom if-checks in three controllers?"
  options:
    - "Nothing, duplication makes each controller independent"
    - "The rules drift apart over time; a single Validator constraint reused everywhere stays consistent"
    - "PHP forbids calling the same regex twice"
    - "Validators run slower than inline checks"
  answer: 1
  explanation: "Centralized constraints define the rule once and apply it uniformly across forms, APIs, and imports, while copies inevitably diverge."
```
