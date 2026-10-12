---
slug: deployment
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: deployment
chapterTitle: "Chapter 17 — Deployment"
chapterOrder: 17
title: "Deploying Symfony Applications"
difficulty: intermediate
order: 3
estimatedMinutes: 12
tags: [symfony, deployment, production, devops]
---

## Learning Objective

Understand the steps of a production Symfony deploy — environment config, dependency install, cache warmup, migrations — and how they fit into an automated, repeatable release.

## Explanation

**Deployment** is the process of taking the code from your repository and making it run reliably on a production server. A Symfony deploy follows the same ordered checklist every time, because skipping steps is how "works on my machine" outages happen. First, the server gets the exact code revision (via git checkout or an artifact) and installs PHP dependencies with `composer install --no-dev --optimize-autoloader` — `--no-dev` excludes testing and debugging packages that must never reach production, while the optimized autoloader maps classes for speed.

Second, the production **environment** is configured: `APP_ENV=prod` (telling Symfony to use optimized caches and hide debug tools) plus real secrets (database URL, mailer credentials) supplied as genuine environment variables by the hosting platform — never copied from a developer's `.env.local`. Third, the release is prepared: `php bin/console cache:warmup --env=prod` pre-builds the container and route caches into `var/cache/prod/`, and asset building (or copying) places compiled CSS/JS under `public/`. Fourth, the database is migrated with `php bin/console doctrine:migrations:migrate --no-interaction`, applying exactly the versioned schema changes tested in staging — the `--no-interaction` flag lets it run unattended in scripts.

Mature teams run these steps in **CI/CD (Continuous Integration / Continuous Deployment)** — an automated pipeline (GitHub Actions, GitLab CI, or similar) that tests every push and deploys passing builds with zero manual server logins. Atomic-release tools (Deployer, or platform features) point the live symlink at the new build only after all steps succeed, so visitors never see a half-deployed app, and rollbacks mean repointing the symlink.

## Why It Matters

Deploy day is when hobby projects and professional systems diverge: a repeatable checklist (or pipeline) turns releases from stressful events into boring routines. Knowing each step's purpose also makes 2 AM failures diagnosable — "forgotten migration" versus "cold cache" versus "dev dependencies in prod" each has a distinct signature you will learn to recognize instantly.

## Example

Releasing task-app version 2.4 to production: install optimized dependencies, warm the prod cache, run pending migrations, and switch traffic — all as one automated sequence that either fully succeeds or leaves the previous release untouched.

## Code

```php
<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $this->em->getConnection()->executeQuery('SELECT 1');

        return $this->json(['status' => 'ok']);
    }
}
```

## Code Explanation

- This `/api/health` endpoint exists to support deployments: load balancers and release scripts poll it to confirm the new build actually serves traffic.
- `executeQuery('SELECT 1')` verifies the database connection works from the fresh release — catching misconfigured `DATABASE_URL` secrets before users do.
- It uses the standard controller stack (routing, DI, Doctrine), so a `200 {"status":"ok"}` proves the container built, routes registered, and the database is reachable.
- Release automation promotes the build to live traffic only when this check passes, and rolls back to the previous release otherwise.
- Keeping the query trivial (`SELECT 1`) ensures the check itself never becomes a load problem under frequent polling.

## Common Mistakes

- Deploying with `APP_ENV=dev` or the debug toolbar enabled: slow responses plus public stack traces leaking internals to visitors.
- Running migrations by hand "just this once" instead of through the pipeline — environments drift, and the next automated deploy may fail or double-apply changes.
- Forgetting `cache:warmup`, so the first visitors after each release suffer multi-second cold-cache responses while Symfony rebuilds everything lazily.

## Best Practices

- Automate the full sequence (install → env → warmup → assets → migrate → health-check → switch traffic) in CI/CD so releases are identical every time and need no server logins.
- Keep deploys atomic with instant rollback (symlink switch or platform slots) and gate traffic on a passing `/api/health` check that exercises the database.
- Never ship dev dependencies or local `.env` files to production; secrets come from the platform's secret store, and `--no-interaction` keeps every command script-safe.

## Exercise

Your deploy pipeline currently runs `composer install` (with dev packages) and skips cache warmup. Name two production problems this causes and the exact corrected commands/steps.

## Solution

Shipping dev packages bloats and endangers production (debug tools, profiler overhead, larger attack surface) — use `composer install --no-dev --optimize-autoloader`. Skipping warmup makes first visitors pay cold-cache rebuilds — add `php bin/console cache:warmup --env=prod` during the build. Together with `APP_ENV=prod` and unattended migrations, the release becomes fast, lean, and deterministic.

## Mini Quiz

```yaml
- question: "Why does a production deploy use composer install --no-dev --optimize-autoloader?"
  options:
    - "It makes composer run without internet access"
    - "It excludes dev-only packages that must never reach production and builds a faster class map"
    - "It automatically writes database migrations"
    - "It deploys the frontend JavaScript bundle"
  answer: 1
  explanation: "Production needs only runtime packages; skipping dev tools shrinks the install and attack surface while the optimized autoloader speeds class loading."
- question: "What does cache:warmup --env=prod accomplish during a deploy?"
  options:
    - "It deletes all user sessions"
    - "It pre-builds the container, route, and config caches so the first visitor never pays cold-build costs"
    - "It backs up the database automatically"
    - "It sends a notification email to all users"
  answer: 1
  explanation: "Warming generates the optimized prod caches at build time, turning first-request multi-second rebuilds into instant cache hits."
- question: "What is the purpose of a /api/health endpoint in a deployment pipeline?"
  options:
    - "It displays the application's homepage to visitors"
    - "It lets automation verify the new release serves traffic with a working database before switching users to it"
    - "It runs pending migrations on every request"
    - "It clears the production cache hourly"
  answer: 1
  explanation: "Health checks gate the traffic switch: only builds answering healthy (app booted, DB reachable) go live, otherwise the pipeline rolls back."
```
