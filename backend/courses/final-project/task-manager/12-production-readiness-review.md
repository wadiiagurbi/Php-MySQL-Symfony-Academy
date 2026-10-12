---
slug: production-readiness-review
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Production-Readiness Review"
difficulty: advanced
order: 12
estimatedMinutes: 12
tags: [final-project, task-manager, production, review]
---

## Learning Objective

By the end of this lesson, you will be able to list, with specific technical reasoning, what this project's implementation already does correctly for production use and what a real deployment would still need to add — the kind of review a senior engineer would actually run before shipping.

## Explanation

It's tempting to treat "production-ready" as a single pass/fail label. It's more useful — and more honest — to review a project category by category, since a real app is always ready in some dimensions and deliberately deferred in others.

**What this implementation already gets right:**

- **Layered architecture.** Controller → DTO → Service → Repository → Entity is followed consistently across every feature (auth, lessons, quizzes, tasks). This isn't cosmetic — it's what makes every other item on this list *possible* to fix later without a rewrite.
- **Input validation at the boundary.** Every write endpoint validates a DTO with Symfony's Validator before any business logic runs; nothing malformed reaches Doctrine.
- **Object-level authorization.** Ownership is checked on every task operation, with a deliberate 404-over-403 choice to avoid leaking resource existence.
- **Password security.** Passwords are hashed via Symfony's `PasswordHasherInterface` (`'auto'` algorithm, currently bcrypt/argon2 depending on PHP build) — never stored or logged in plaintext.
- **Stateless authentication.** JWT means no server-side session storage, so the API can scale horizontally (multiple server instances) without needing shared session state.
- **Consistent, safe error responses.** One listener guarantees every `/api/*` error is JSON with a predictable shape, and a generic `500` for anything unanticipated — no stack traces ever reach a client.
- **Automated tests covering real behavior.** Functional tests exercise full HTTP round-trips for auth, CRUD, filtering, pagination, and cross-user authorization — not just isolated unit tests.
- **A database-agnostic configuration.** `DATABASE_URL` is the only thing that changes to move from SQLite (local dev) to MySQL (production) — no code changes required, because Doctrine's DBAL abstracts the difference.

**What a real production deployment would still need to add:**

- **JWT refresh tokens.** Today's tokens are issued once at login with a fixed expiry (Lexik's default TTL) and there's no refresh flow — a real product needs short-lived access tokens plus a refresh token (or a re-login prompt) so sessions don't just silently expire mid-use.
- **Rate limiting.** `/api/auth/login` and `/api/auth/register` have no throttling, making them vulnerable to brute-force or spam registration at scale. Symfony's RateLimiter component solves this directly.
- **HTTPS enforcement.** Local development runs over plain HTTP; a real deployment must terminate TLS (typically at a reverse proxy like Nginx) and redirect all HTTP traffic, since JWTs sent over plaintext HTTP can be intercepted.
- **Environment-specific secrets management.** `.env` files are fine for local development, but production secrets (the JWT passphrase, database credentials) belong in the deployment platform's secret store, never committed to a repository — Symfony's `secrets:set` vault is one option built for exactly this.
- **Structured logging and monitoring.** The app logs to Symfony's default channel today; production needs logs shipped somewhere searchable (e.g., an ELK stack or a hosted log service) and basic uptime/error-rate monitoring, so failures are noticed before a user reports them.
- **Database backups and migration discipline.** Migrations exist and run cleanly, but a production runbook needs automated backups before each deploy's migration step, and a tested rollback plan.
- **MySQL-specific tuning.** Moving from SQLite to MySQL is a one-line `DATABASE_URL` change functionally, but production MySQL also wants connection pooling, appropriate indexes reviewed under real data volume, and a configured `server_version` for Doctrine.
- **CORS locked down precisely.** The current `nelmio_cors.yaml` configuration is intentionally permissive for local development against `localhost`; a production config must restrict `allow_origin` to the real frontend's exact domain.

## Why It Matters

This kind of review — explicit, categorized, and specific — is what separates "it works on my machine" from a genuine production launch checklist. It also models the right mindset for reading *any* codebase you didn't write: assume the original authors made reasonable tradeoffs for their context (a learning project prioritizes clarity and time-to-build over horizontal scalability), and evaluate each gap on its own merits rather than treating "not production-hardened yet" as a single verdict.

## Example

A concrete illustration of "the architecture makes gaps fixable, not fatal": adding rate limiting to `/api/auth/login` requires touching exactly one file — wrapping the existing controller logic (or adding a Symfony RateLimiter check in `AuthController::register`, and a similar check for the `login` firewall) — because the DTO validation, password hashing, and JWT issuance are already cleanly separated. If validation, hashing, and rate-limiting logic were all tangled together in one giant controller method, the same change would risk breaking three unrelated things at once.

## Code

```php
<?php
// A sketch of how rate limiting could be added to registration,
// using Symfony's RateLimiter component (not part of this project today —
// this is what "adding it later" would look like, thanks to the existing architecture).

use Symfony\Component\RateLimiter\RateLimiterFactory;

class AuthController extends AbstractController
{
    public function register(
        Request $request,
        RequestValidator $requestValidator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        RateLimiterFactory $registrationLimiter, // new dependency, autowired
    ): JsonResponse {
        $limiter = $registrationLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => ['message' => 'Too many registration attempts. Try again later.']], 429);
        }

        // ...the existing validation, duplicate-check, hashing, and persist logic
        // below this line does not need to change at all.
    }
}
```

## Code Explanation

Only one new parameter is added to the method signature, and only three new lines run before the existing logic. Everything else — the DTO validation via `RequestValidator`, the duplicate-email check, the password hashing, the `persist`/`flush` — is untouched. This is the payoff of the layered design from earlier lessons: a genuinely new cross-cutting concern (rate limiting) can be bolted onto one endpoint without risking a regression in unrelated logic that already has test coverage.

## Common Mistakes

- Treating "production-ready" as binary rather than reviewing specific, named risks — vague concerns ("we should probably add more security") don't lead to fixable action items the way "add rate limiting to /api/auth/login" does.
- Deploying with the development CORS configuration unchanged, silently allowing any origin to call the API.
- Assuming SQLite is fine for production because "it worked in testing" — SQLite has no built-in support for concurrent writers across multiple app server processes, which most production deployments need.
- Skipping a rollback plan for migrations, discovering only during an incident that there isn't a tested way to undo a bad schema change.

## Best Practices

- Do this kind of category-by-category review before every real launch, not just once at the very end of a course or project.
- Prioritize gaps by actual risk and cost: authentication/security gaps (rate limiting, HTTPS, CORS) generally outrank convenience gaps (refresh tokens) which outrank operational polish (structured logging) — though all three eventually matter.
- Keep the layered architecture intact as new production requirements arrive; resist the temptation to bypass it "just this once" for a quick fix, since that's exactly how technical debt accumulates.

## Exercise

Pick any one item from the "still needs" list above and write a short, specific plan (3-5 sentences) for how you would implement it in this exact codebase, naming the actual files/classes you'd touch.

## Solution

Example for "CORS locked down precisely": Open `config/packages/nelmio_cors.yaml` and change the `allow_origin` pattern from the permissive localhost regex to the literal production frontend URL (e.g. `https://academy.example.com`), removing the wildcard port matching used for local development. Add a `when@prod:` block in the same file so local development keeps its permissive localhost pattern while production gets the strict one — mirroring the pattern already used in `config/packages/doctrine.yaml`'s `when@test:`/`when@prod:` blocks elsewhere in this project. No application code changes are needed since CORS is purely a configuration concern handled by the bundle.

## Mini Quiz

```yaml
- question: "Why is treating 'production-ready' as a single yes/no label less useful than a category-by-category review?"
  options:
    - "Because production readiness cannot actually be evaluated before launch"
    - "Because different categories (security, scalability, operations) carry different risks and can be prioritized and fixed independently"
    - "Because a single label is faster to communicate to stakeholders"
    - "Because Symfony automatically handles all production concerns"
  answer: 1
  explanation: "A category-by-category review turns vague worry into specific, actionable, independently-prioritizable items — which is what actually gets fixed before or after launch."
- question: "Why can rate limiting be added to /api/auth/register by touching only one controller method in this project?"
  options:
    - "Because rate limiting is built into Symfony's routing by default"
    - "Because the existing validation, hashing, and persistence logic is already cleanly separated, so a new concern can be added without touching them"
    - "Because AuthController has no other logic in it"
    - "It cannot be added without rewriting the DTO layer"
  answer: 1
  explanation: "The layered architecture means unrelated concerns (validation, hashing, persistence) are isolated enough that a new cross-cutting concern like rate limiting can be inserted without risking those existing, already-tested behaviors."
- question: "Why is SQLite called out as a concern specifically for production, despite working fine in this project's development and test setup?"
  options:
    - "SQLite cannot store more than a few hundred rows"
    - "SQLite has no built-in support for concurrent writers across multiple production application server processes"
    - "SQLite is not supported by Doctrine DBAL"
    - "SQLite requires a paid license for production use"
  answer: 1
  explanation: "SQLite is excellent for local development and testing but is not designed for the concurrent-write patterns of a multi-process production web server, which is why DATABASE_URL is designed to switch to MySQL for production."
```
