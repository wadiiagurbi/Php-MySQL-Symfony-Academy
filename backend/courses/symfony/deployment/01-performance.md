---
slug: performance
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: deployment
chapterTitle: "Chapter 17 — Deployment"
chapterOrder: 17
title: "Performance Essentials"
difficulty: intermediate
order: 1
estimatedMinutes: 11
tags: [symfony, performance, cache, optimization]
---

## Learning Objective

Understand where Symfony response time actually goes, how **caching** (opcode, framework, and HTTP) attacks each layer, and how to measure improvements instead of guessing.

## Explanation

Response time in a Symfony app comes from three stacked layers. First, PHP must compile your code on every request unless an **opcode cache** (a PHP extension, **OPcache**, that keeps compiled bytecode in memory so files are read and parsed only once) is enabled — in production it always should be, and Symfony's `opcache.preload` support goes further by loading the framework's hottest classes into shared memory at server start. Second, Symfony itself rebuilds its container, routes, and configuration unless its own caches in `var/cache/prod/` are warm — running `php bin/console cache:warmup --env=prod` during deploy pre-builds them so the first real visitor never pays the cost. Third, your application code hits databases and APIs, where query-result caching (lesson: Advanced 02) and eager loading of Doctrine relations remove the biggest millisecond chunks.

Above all three sits **HTTP caching**: responses carrying `Cache-Control` headers can be reused by browsers and reverse proxies without touching PHP at all. Marking a public, rarely changing page `public, max-age=3600` (via `$response->setPublic(); $response->setMaxAge(3600);`) lets a **reverse proxy** (a cache like Varnish or Symfony's HttpCache sitting in front of your app, serving stored responses directly) absorb repeat traffic. Private, per-user pages instead use `private` directives or short lifetimes plus validation (`ETag`/`Last-Modified`) so caches revalidate rather than serve stale personal data.

None of this should be applied blindly: the Symfony **profiler** (the dev toolbar's timeline showing database queries, events, and memory per request) identifies the true bottleneck first. Optimizing an unmeasured 5ms Twig block while ignoring thirty N+1 queries is the classic performance anti-pattern.

## Why It Matters

Performance is a feature users feel and a bill you pay: halving average response time can halve the servers needed at scale, and slow pages measurably lose visitors. Deploy checklists at professional teams always include OPcache verification, cache warmup, and a before/after measurement — "it feels faster" is never accepted as evidence.

## Example

The public task-statistics page runs heavy aggregate queries on every visit. Combining a warmed framework cache with a ten-minute application cache and a public HTTP cache header removes nearly all per-request work.

## Code

```php
<?php

namespace App\Controller;

use App\Service\TaskStatsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/stats', name: 'app_stats', methods: ['GET'])]
    public function stats(TaskStatsProvider $stats): Response
    {
        $response = $this->render('stats/index.html.twig', [
            'stats' => $stats->getStats(),
        ]);

        $response->setPublic();
        $response->setMaxAge(600);

        return $response;
    }
}
```

## Code Explanation

- `TaskStatsProvider` (from the caching lesson) already memoizes the heavy queries for ten minutes at the application layer.
- `$this->render(...)` returns a `Response` object whose headers we can tune before returning it to the framework.
- `setPublic()` marks the page safe for shared caches (correct here because statistics contain no per-user data — never do this on private pages).
- `setMaxAge(600)` emits `Cache-Control: public, max-age=600`, letting browsers and reverse proxies serve repeats for ten minutes without reaching PHP.
- The layers multiply: HTTP cache absorbs most hits, the app cache covers proxy misses, and the warmed container keeps even full rebuilds fast.

## Common Mistakes

- Marking per-user pages `public`, so one visitor's dashboard is served from cache to strangers — a privacy breach, not just a bug.
- Deploying with `APP_ENV=dev` (debug toolbar, unoptimized container, no OPcache preload) and wondering why production is slow — environment misconfiguration dwarfs most code issues.
- Optimizing without measuring: adding caches and indexes for imagined bottlenecks while the real N+1 query problem sits visible in the profiler timeline.

## Best Practices

- Deploy checklist: `APP_ENV=prod`, OPcache enabled with preload, `cache:warmup` during build, and debug disabled — verify each, do not assume.
- Cache in layers (HTTP headers for public pages, app cache for expensive computations, warm framework cache always) and measure with the profiler before and after.
- Keep private data `private` with short lifetimes or validators (`ETag`), and reserve `public` caching for genuinely shared content.

## Exercise

A public "pricing" page renders the same content for everyone and changes weekly. State the two response-header calls you would add, and explain why `public` is safe here but would be wrong on an account page.

## Solution

Call `$response->setPublic(); $response->setMaxAge(604800);` (one week) so browsers and proxies reuse the page without hitting PHP. `public` is safe because the content is identical for all visitors with nothing personal; on an account page it would leak one user's private data to others via shared caches.

## Mini Quiz

```yaml
- question: "What does PHP's OPcache do for a Symfony app in production?"
  options:
    - "It caches database query results automatically"
    - "It keeps compiled PHP bytecode in memory so files are not re-read and re-parsed on every request"
    - "It minifies CSS and JavaScript files"
    - "It sends emails asynchronously"
  answer: 1
  explanation: "OPcache removes the per-request compile cost of PHP itself; preload goes further by preloading hot framework classes into shared memory at startup."
- question: "Why is marking a per-user dashboard response public dangerous?"
  options:
    - "It makes the page load slightly slower"
    - "Shared caches (proxies, CDNs) may serve one user's private page to strangers"
    - "Browsers refuse to display public responses"
    - "It prevents the user from logging out"
  answer: 1
  explanation: "public authorizes shared intermediaries to store and replay the response, which is only safe when content is identical for everyone."
- question: "What is the correct first step before optimizing a slow page?"
  options:
    - "Add caching to every service immediately"
    - "Measure with the profiler to find the actual bottleneck (queries, events, memory)"
    - "Rewrite the page in a different framework"
    - "Increase the server's disk space"
  answer: 1
  explanation: "The profiler timeline shows where time truly goes; optimizing unmeasured guesses wastes effort and often misses the real problem entirely."
```
