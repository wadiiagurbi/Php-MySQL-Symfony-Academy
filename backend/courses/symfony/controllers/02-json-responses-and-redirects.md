---
slug: json-responses-and-redirects
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: controllers
chapterTitle: "Chapter 3 — Controllers"
chapterOrder: 3
title: "JSON Responses and Redirects"
difficulty: intermediate
order: 2
estimatedMinutes: 9
tags: [symfony, controllers, json, redirects]
---

## Learning Objective

Learn the details of building JSON responses correctly (status codes, headers) and how to issue HTTP redirects, both by literal URL and by route name.

## Explanation

You've been using `$this->json($data)` since Chapter 1, but it's worth understanding exactly what it does and what options it supports, since real endpoints need more control than the default. The full signature (as a helper on `AbstractController`) accepts the data to encode, an HTTP status code (defaulting to `200`), and an array of extra headers:

```
$this->json(mixed $data, int $status = 200, array $headers = []): JsonResponse
```

Internally, this constructs a `JsonResponse`, which is a `Response` subclass that automatically JSON-encodes whatever PHP value you give it (arrays, and objects that implement PHP's `JsonSerializable` interface, or plain public properties) and sets the `Content-Type: application/json` header for you — this is precisely why you should always prefer `$this->json(...)` (or constructing `JsonResponse` directly) over manually building a generic `Response` with a hand-encoded JSON string: it guarantees the header matches the body's actual format, which matters for API clients (including the Vue frontend this platform's data feeds) that inspect `Content-Type` to decide how to parse a response.

The second major topic here is **redirects**. An HTTP redirect is a response telling the client "the resource you want is actually at a different URL — go there instead," using a `3xx` status code (redirects are covered here in a controller context, though in a JSON-only API they're less common than in traditional server-rendered apps; they matter for things like redirecting after a legacy URL, or in the Twig chapter's server-rendered pages). `AbstractController` gives you two shortcuts:

- `$this->redirect($url)` — redirect to a literal URL string, with an optional status code (defaults to `302 Found`, a temporary redirect; use `301` for a permanent one).
- `$this->redirectToRoute($routeName, $params)` — redirect to a URL generated from a route name and parameters, exactly like `generateUrl()` from the previous chapter, but wrapped directly into a redirect `Response`.

Preferring `redirectToRoute()` over `redirect()` with a hardcoded string follows the same principle as `generateUrl()`: the actual path is derived from the route definition, so it automatically stays correct if the route's path changes later.

## Why It Matters

Getting status codes and the `Content-Type` header right is not cosmetic — API clients, browsers, caching layers, and monitoring tools all make real decisions based on them. A client library might refuse to parse a response body as JSON if `Content-Type` says `text/html`; a `200` returned for a failed operation can hide real errors from error-tracking tools that filter by status code; a `302` used where a permanent `301` was intended can prevent search engines or caching proxies from updating their records correctly. Professional Symfony development treats the exact status code and headers of every response as part of the API's contract, not an afterthought.

## Example

A task-tracking API's login endpoint, after successfully authenticating a user via a traditional (non-API) form submission, should redirect them to their dashboard page rather than returning JSON — a common pattern where a mostly-JSON API still has one or two traditional browser-facing routes, such as an OAuth callback or password-reset confirmation link.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $task = $id === 5 ? ['id' => 5, 'title' => 'Example task'] : null;

        if (null === $task) {
            // Explicit status + no extra headers needed; $this->json defaults status to 200.
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        // A custom header alongside the JSON body and an explicit 200 status.
        return $this->json($task, Response::HTTP_OK, ['X-Resource-Version' => '1']);
    }

    #[Route('/account/password-reset/confirm', name: 'account_password_reset_confirm', methods: ['GET'])]
    public function confirmPasswordReset(): RedirectResponse
    {
        // A confirmation link (e.g. clicked from an email) redirects to the dashboard by route name.
        return $this->redirectToRoute('account_dashboard');
    }
}
```

## Code Explanation

- `$this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND)` passes a status explicitly as the second argument — `$this->json()` always sets `Content-Type: application/json` regardless of the status code, so even this error response is correctly labeled as JSON.
- `$this->json($task, Response::HTTP_OK, ['X-Resource-Version' => '1'])` shows the third argument: extra headers merged into the response alongside the automatic `Content-Type`. Custom headers like this are common for API versioning or caching hints.
- `RedirectResponse` (the return type on `confirmPasswordReset()`) is the specific `Response` subclass both `redirect()` and `redirectToRoute()` return — it's still a `Response`, satisfying the same contract every controller method must follow, just pre-configured with a `Location` header and a 3xx status.
- `$this->redirectToRoute('account_dashboard')` builds the redirect target from a route name, exactly as `generateUrl()` would, then wraps it in a 302 response automatically — no manual `Location` header or status code needed.

## Common Mistakes

- Manually building a `Response` with a JSON string and forgetting to set `Content-Type: application/json` — the body might be valid JSON, but strict clients that check the header first may refuse to parse it, or your Vue frontend's HTTP client may not automatically parse the body as JSON.
- Using `$this->redirect('/account/dashboard')` with a hardcoded path instead of `$this->redirectToRoute('account_dashboard')` — this reintroduces the exact maintenance problem named routes are meant to solve, since the hardcoded string silently breaks if the route's path changes.
- Returning a `302` redirect for something that is actually permanent (e.g., a URL that has moved for good) — search engines and some HTTP clients treat `301` and `302` differently for caching and link-following purposes, so picking the wrong one has real, if subtle, consequences.

## Best Practices

- Always pass an explicit status code to `$this->json()` for anything other than a successful `200`, even though it's tempting to rely on defaults — explicit codes make the controller's intent unambiguous to future readers.
- Prefer `redirectToRoute()` over `redirect()` whenever the destination is a route within your own application; reserve `redirect()` for genuinely external URLs that have no corresponding Symfony route.
- Keep custom headers meaningful and documented (e.g., `X-Resource-Version`) — undocumented custom headers are easy for API consumers, including your own frontend team, to miss or misuse.

## Exercise

Write a controller method for `POST /api/tasks` that, on success, returns a 201 JSON response with the created task and a `Location` header pointing to the new task's `GET` endpoint (route name `api_tasks_show`), generated using `generateUrl()` rather than a hardcoded string.

## Solution

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $newTaskId = 42; // in a real app, this comes back from saving to the database

        $location = $this->generateUrl('api_tasks_show', ['id' => $newTaskId]);

        return $this->json(
            ['id' => $newTaskId, 'title' => 'New task'],
            Response::HTTP_CREATED,
            ['Location' => $location]
        );
    }
}
```

`generateUrl('api_tasks_show', ['id' => $newTaskId])` produces the correct path for fetching the newly created task by referring to the route by name, so it stays correct even if `api_tasks_show`'s path changes later. The response uses `Response::HTTP_CREATED` (201) and includes the generated URL in the `Location` header, following REST conventions for a successful creation.

## Mini Quiz

```yaml
- question: "Why should you prefer $this->json($data) over manually building a Response with a hand-encoded JSON string?"
  options:
    - "$this->json() is the only way to return any data from a controller"
    - "$this->json() automatically sets the Content-Type: application/json header, guaranteeing it matches the actual body format"
    - "Manually built Response objects cannot contain JSON text at all"
    - "There is no difference between the two approaches"
  answer: 1
  explanation: "$this->json() builds a JsonResponse, which always sets Content-Type: application/json automatically — a manually built generic Response would require you to remember to set that header yourself."

- question: "What is the advantage of $this->redirectToRoute('account_dashboard') over $this->redirect('/account/dashboard')?"
  options:
    - "redirectToRoute() is faster at runtime"
    - "redirectToRoute() generates the URL from the route's current path definition by name, so it stays correct if the path changes later"
    - "redirect() cannot produce a 302 status"
    - "There is no functional difference"
  answer: 1
  explanation: "redirectToRoute() builds its target URL the same way generateUrl() does — from the named route's current definition — avoiding the maintenance risk of a hardcoded path string breaking silently after a route change."

- question: "What is the default HTTP status code returned by $this->json($data) when no status is explicitly provided?"
  options:
    - "201"
    - "204"
    - "200"
    - "302"
  answer: 2
  explanation: "The $this->json() helper defaults its status argument to 200 (OK) unless you explicitly pass a different status code as the second argument."
```
