---
slug: error-handling
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Error Handling and Exceptions"
difficulty: intermediate
order: 6
estimatedMinutes: 11
tags: [symfony, errors, exceptions, http]
---

## Learning Objective

Learn how Symfony converts PHP **exceptions** into HTTP responses, how to throw the right `HttpException` for each situation, and how custom error pages work.

## Explanation

An **exception** is PHP's mechanism for signaling "something went wrong and normal execution cannot continue" — a missing task, denied access, a failed payment. Code **throws** (raises) an exception object, and execution jumps to the nearest matching **catch** block, or, if none exists, to Symfony's **exception listener** (a framework subscriber that converts any uncaught exception into an HTTP response). This last part is what makes Symfony apps robust: an unhandled problem still produces a clean error page or JSON error instead of a blank screen or leaked stack trace.

For HTTP apps, the exceptions you throw most are **HTTP exceptions** carrying a status code: `throw $this->createNotFoundException('Task not found')` produces a `404`, while `new AccessDeniedHttpException()` yields `403` and `new BadRequestHttpException('...')` a `400`. Throwing these from controllers (or services, which controllers let bubble up) is preferable to manually building error responses, because the framework then handles content negotiation (HTML page for browsers, JSON for API clients), logging, and status codes consistently. Unexpected exceptions (`\RuntimeException` and friends) become `500 Internal Server Error` responses — logged with full stack traces server-side while showing users only a generic page.

What users actually see is controlled by templates in `templates/bundles/TwigBundle/Exception/`: `error404.html.twig` customizes the not-found page, `error403.html.twig` the forbidden page, and so on. In `dev`, Symfony instead shows the detailed exception page with stack trace and profiler link; in `prod`, users see only your branded templates while developers read `var/log/prod.log`.

## Why It Matters

Error behavior *is* user experience for the unhappy path: a clear 404 page keeps visitors, while a stack trace leaks internals and destroys trust. For APIs, consistent JSON error shapes let frontend teams handle failures programmatically instead of parsing HTML error pages. Reviewers check that missing resources 404, forbidden actions 403, and bad input 400s — status-code discipline is part of every API contract.

## Example

Fetching one task by id must return the task, a clean 404 page when the id does not exist, and a 403 when the current user does not own it — each via the appropriate exception, not hand-built responses.

## Code

```php
<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskShowController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'])]
    public function show(int $id, TaskRepository $tasks): JsonResponse
    {
        $task = $tasks->find($id);

        if (null === $task) {
            throw $this->createNotFoundException('Task not found.');
        }

        $this->denyAccessUnlessGranted('TASK_VIEW', $task);

        return $this->json([
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'done' => $task->isDone(),
        ]);
    }
}
```

## Code Explanation

- `$tasks->find($id)` returns the entity or `null` — the explicit null check turns "missing row" into a deliberate 404 instead of a fatal error on a null method call.
- `createNotFoundException()` builds the 404 exception; Symfony's listener converts it into the `error404.html.twig` page (or JSON for API clients) with status 404.
- `denyAccessUnlessGranted('TASK_VIEW', $task)` throws the 403/401 exception when the voter refuses, before any data is exposed.
- Checks run in dependency order: existence first (404), then permission (403), then the successful 200 payload — each failure mode maps to its correct status.
- No try/catch is needed here: letting HTTP exceptions bubble to the framework listener is the intended pattern, keeping controller code linear.

## Common Mistakes

- Returning `200 OK` with `['error' => 'not found']` bodies instead of real 404/403 statuses. HTTP clients, caches, and frontends all key off status codes — a 200 "error" breaks every one of them.
- Catching exceptions too early and swallowing them (`catch (\Exception) {}` with no logging). The request "succeeds" while the failure vanishes without a trace in the logs.
- Showing dev-style stack traces in production by misconfiguring the environment — exception details belong in logs, never in user-facing `prod` responses.

## Best Practices

- Throw semantic HTTP exceptions (`createNotFoundException`, `AccessDeniedHttpException`, `BadRequestHttpException`) and let the framework render them consistently.
- Order guards as existence → authorization → validation, so each failure surfaces with its precise status code.
- Customize `error404.html.twig` / `error403.html.twig` (and JSON error shapes for APIs) so failures look intentional, and always log unexpected 500s with context for diagnosis.

## Exercise

An API endpoint receives a `priority` field that must be an integer between 1 and 5. State which exception to throw for an out-of-range value, what status the client sees, and why a 200-with-error-body would be wrong.

## Solution

Throw `new BadRequestHttpException('Priority must be between 1 and 5.')` (or the `UnprocessableEntityHttpException` your API standard prefers) so the client receives `400 Bad Request`. A 200-with-error-body lies to HTTP clients, caches, and frontend error handling, all of which route on status codes — the failure would be treated as success everywhere upstream.

## Mini Quiz

```yaml
- question: "What happens to an exception thrown in a controller that nothing catches?"
  options:
    - "The PHP process crashes and the server stops"
    - "Symfony's exception listener converts it into a proper HTTP error response (with logging), e.g. 404 or 500"
    - "The exception is silently ignored and a 200 is returned"
    - "The database rolls back all migrations"
  answer: 1
  explanation: "Uncaught exceptions bubble to the framework listener, which maps HTTP exceptions to their statuses (others to 500), logs details, and renders the error page or JSON."
- question: "Why throw createNotFoundException() instead of returning a 200 response containing an error message?"
  options:
    - "There is no practical difference between the two"
    - "Real status codes let HTTP clients, caches, and frontends handle failures correctly; a 200 error body is misclassified as success"
    - "Controllers cannot return 200 responses"
    - "Exceptions execute faster than return statements"
  answer: 1
  explanation: "Status codes are the machine-readable contract of HTTP; misusing 200 for failures breaks client logic, caching, and monitoring built on those codes."
- question: "How do custom error pages work in Symfony?"
  options:
    - "By editing vendor framework files directly"
    - "By adding error404.html.twig style templates under templates/bundles/TwigBundle/Exception/, shown in prod while dev keeps the detailed exception page"
    - "By writing raw HTML inside controllers"
    - "Error pages cannot be customized in Symfony"
  answer: 1
  explanation: "Per-status templates in that directory override the defaults for production users, while the dev environment retains the debugging exception page."
```
