---
slug: routes-and-attributes
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: routing
chapterTitle: "Chapter 2 — Routing"
chapterOrder: 2
title: "Routes and PHP Attributes"
difficulty: intermediate
order: 1
estimatedMinutes: 10
tags: [symfony, routing, attributes]
---

## Learning Objective

Understand what a route is, how PHP attributes are used to define routes directly on controller methods, and how Symfony uses this information to match incoming requests.

## Explanation

As introduced in Chapter 1, **routing** is the process of mapping an incoming URL (and HTTP method) to the specific piece of code that should handle it. A **route** is one single mapping rule: "requests to this URL pattern, with this HTTP method, should be handled by this controller method." A real application has many routes — one (or a few) for each distinct action your API supports, like listing tasks, creating a task, or fetching one specific task.

Symfony needs some way for you to *declare* these routes. Modern Symfony (6.4+, which this course uses) does this using **PHP attributes** — a native PHP language feature (available since PHP 8) that lets you attach structured metadata directly to a class, method, or property using the `#[...]` syntax. You have not used attributes yet in this course because plain PHP rarely needs them, but Symfony uses them heavily, and `#[Route(...)]` is the most common one you'll write.

A route attribute is placed immediately above a controller method:

```
#[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]
public function list(): JsonResponse { ... }
```

This single line tells Symfony everything it needs: the **path** (`/api/tasks` — the URL pattern to match), a **name** (`api_tasks_list` — a unique internal identifier for this route, used for generating URLs elsewhere in your code, covered later in this chapter), and the **methods** it responds to (`['GET']` — this route only matches GET requests; a POST to the same URL would not match it).

When a request comes in, Symfony's router checks it against every registered route (Symfony actually compiles them into an efficient lookup structure, so this is fast even with hundreds of routes) and calls the controller method attached to the first matching route. If the path matches but the method doesn't (e.g., a DELETE request to `/api/tasks`), Symfony returns a `405 Method Not Allowed` response, distinct from the `404 Not Found` it returns when no path matches at all.

Because the route is written as an attribute directly on the method it applies to, you never have to maintain a separate routing file that lists "URL X goes to method Y" — the routing information lives right next to the code it triggers, which makes it much harder for routes and controllers to drift out of sync as the codebase grows. Symfony automatically discovers all `#[Route]` attributes in your `src/Controller/` directory (this scanning behavior is configured once, in `config/routes.yaml`, when the project is set up).

## Why It Matters

In any real Symfony codebase, `#[Route]` attributes are how you discover "what does this application actually expose" — running `php bin/console debug:router` lists every registered route, and a huge part of onboarding onto an existing API-based Symfony project involves reading through controller classes and their route attributes to understand the surface area of the API. Getting the `methods` array right also matters for correctness and security: accidentally leaving a route open to `GET` when it should only accept `POST` (for an action that changes data) can allow requests that were never intended to be possible, and browsers/proxies may cache GET responses in ways that break an action meant to have side effects.

## Example

A task-tracking API needs an endpoint to list all of a user's tasks. The route's path is `/api/tasks`, it should only respond to GET requests (since listing data has no side effects), and it needs a unique name so other parts of the app can refer to it later.

## Code

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = [
            ['id' => 1, 'title' => 'Write routing lesson', 'done' => false],
            ['id' => 2, 'title' => 'Review pull request', 'done' => true],
        ];

        return $this->json($tasks);
    }
}
```

## Code Explanation

- `use Symfony\Component\Routing\Attribute\Route;` imports the `Route` attribute class itself — without this import, PHP would not know what `#[Route(...)]` refers to.
- `#[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]` is the attribute. The first, unnamed argument (`'/api/tasks'`) is the path. `name:` and `methods:` are named arguments — a plain PHP 8 feature you may already know — making the attribute's meaning clear without relying on argument order.
- Placing the attribute directly above `public function list(...)` associates this specific route with this specific method — Symfony reads the attribute via PHP's reflection capabilities when it builds its routing table (typically once, then cached, as discussed in Chapter 1's coverage of `var/cache/`).
- `methods: ['GET']` restricts this route to GET requests only; a POST request to the exact same `/api/tasks` path would not match this route at all (it would need its own separate route, with its own attribute, as you'll build in the Controllers chapter).
- The method body itself is unrelated to routing — once Symfony has matched the route and decided to call `list()`, everything inside is ordinary controller logic, as covered in Chapter 1's lifecycle lesson.

## Common Mistakes

- Forgetting the `use Symfony\Component\Routing\Attribute\Route;` import and getting a confusing "Attribute class not found" or similar error — attributes still require importing the class they reference, just like any other PHP class usage.
- Omitting `methods:`, which makes a route match *any* HTTP method by default — often not what you want, since it means a GET-only listing action would also technically respond to a POST or DELETE request to the same URL.
- Reusing the same route `name` on two different routes. Route names must be unique across the whole application; Symfony will throw an error at boot time if two routes share a name, since the name is meant to uniquely identify one specific route.

## Best Practices

- Give routes descriptive, consistent names following a pattern like `api_<resource>_<action>` (e.g., `api_tasks_list`, `api_tasks_create`) — this makes the full list of routes easy to scan and keeps naming predictable across a growing codebase.
- Always specify `methods:` explicitly, even though it's technically optional, so a route's intent (read-only vs. state-changing) is unambiguous just by reading the attribute.
- Run `php bin/console debug:router` periodically while developing to check that your new route was registered as expected, with the path, name, and methods you intended.

## Exercise

Write a `#[Route]` attribute (with its full method signature, returning `JsonResponse`) for an endpoint that returns the current server's health status at the path `/api/health`, responding only to GET requests, named `api_health_check`.

## Solution

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'api_health_check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
```

The `#[Route('/api/health', name: 'api_health_check', methods: ['GET'])]` attribute matches GET requests to `/api/health` and routes them to the `check()` method, which returns a simple JSON status payload via `JsonResponse`, satisfying the requirement of an explicitly named, GET-only health check endpoint.

## Mini Quiz

```yaml
- question: "What PHP language feature does Symfony use to define routes directly on controller methods?"
  options:
    - "Docblock comments"
    - "PHP attributes, using the #[...] syntax"
    - "A separate routes.php array file exclusively"
    - "Magic method names like __route()"
  answer: 1
  explanation: "Symfony 6.4 uses native PHP 8 attributes (#[Route(...)]) placed directly above controller methods to declare routing information alongside the code it applies to."

- question: "If a route is defined with methods: ['GET'] and a client sends a POST request to that exact path, what happens?"
  options:
    - "Symfony automatically treats it as a GET request"
    - "The route does not match for POST; Symfony returns a 405 Method Not Allowed (assuming no other route matches)"
    - "The application crashes with a fatal error"
    - "Symfony silently ignores the methods restriction"
  answer: 1
  explanation: "The methods array restricts which HTTP methods a route matches. A path match with a disallowed method results in a 405 Method Not Allowed response, distinct from a 404 when no path matches at all."

- question: "Why must route names be unique across an entire Symfony application?"
  options:
    - "They aren't required to be unique"
    - "Because the route name uniquely identifies one specific route, and is used elsewhere (e.g., generating URLs) to refer back to it"
    - "Names are only used for documentation and have no functional purpose"
    - "Route names must match the controller's class name exactly"
  answer: 1
  explanation: "A route's name acts as its unique identifier within the application; Symfony will throw a startup error if two routes are given the same name, since other code may rely on the name to unambiguously reference that specific route."
```
