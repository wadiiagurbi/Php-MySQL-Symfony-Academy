---
slug: named-routes-and-http-methods
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: routing
chapterTitle: "Chapter 2 — Routing"
chapterOrder: 2
title: "Named Routes and HTTP Methods"
difficulty: intermediate
order: 3
estimatedMinutes: 10
tags: [symfony, routing, http-methods]
---

## Learning Objective

Understand why every route has a unique name, how to generate URLs from route names instead of hardcoding paths, and how to design a set of routes around HTTP methods that follow REST conventions.

## Explanation

You've already seen the `name:` argument on `#[Route]` attributes in passing — every route needs one, and it must be unique across the whole application. The reason names matter goes beyond bookkeeping: Symfony lets you **generate a URL from a route's name**, anywhere in your code, instead of writing the literal path string by hand. This matters because paths change — you might rename `/api/tasks/{id}` to `/api/v2/tasks/{id}` during a refactor, and if the actual URL string were scattered across dozens of files (redirects, email links, tests), you'd have to find and fix every occurrence. If instead everything refers to the route by its stable name (`api_tasks_show`), you only need to change the path in one place — the `#[Route]` attribute itself — and every generated URL automatically reflects the new path.

Inside a controller, you generate a URL using the `generateUrl()` helper (available via `AbstractController`), passing the route name and any parameters it needs:

```
$url = $this->generateUrl('api_tasks_show', ['id' => 5]); // e.g. "/api/tasks/5"
```

You'll use this most often when building a redirect (covered in the next chapter) — redirecting a client to another endpoint by name rather than by hardcoded path.

The second major idea in this lesson is designing routes around **HTTP methods** in a way that follows common REST conventions — a widely used pattern for structuring APIs around resources (like "tasks" or "posts") and standard verbs describing what to do with them:

- **GET** — read data, with no side effects. Fetching a list (`GET /api/tasks`) or a single item (`GET /api/tasks/{id}`).
- **POST** — create a new resource. `POST /api/tasks` with a JSON body creates a new task.
- **PUT** (or **PATCH**) — update an existing resource. `PUT /api/tasks/{id}` replaces/updates task `{id}`.
- **DELETE** — remove a resource. `DELETE /api/tasks/{id}` deletes task `{id}`.

Notice that the *same path* (`/api/tasks/{id}`) can be used by multiple routes, as long as each specifies different `methods:` — Symfony treats a route's path and its allowed methods together as what defines a match, not the path alone. This is exactly how a well-designed REST-style API keeps URLs clean: one URL per resource, with the HTTP method expressing the action, rather than encoding the action into the URL itself (like the older style of `/api/deleteTask?id=5`).

## Why It Matters

Following REST conventions with named routes is close to an industry-wide default for Symfony (and most modern) APIs, including the exact backend this course's platform runs on — so recognizing and writing routes this way is essential for working on any real API codebase. Beyond convention, using `generateUrl()` (or the equivalent Twig function `path()`, covered in the Twig chapter) instead of hardcoded strings is what makes large applications maintainable: a refactor that changes a URL structure becomes a one-line change to a route's path, rather than a error-prone search-and-replace across the entire codebase, including tests and any HTML/email templates.

## Example

A task-tracking API needs full CRUD (Create, Read, Update, Delete) support for tasks, all under the single resource path shape `/api/tasks` and `/api/tasks/{id}`, distinguished purely by HTTP method, each with its own clearly named route.

## Code

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(['items' => []]);
    }

    #[Route('/api/tasks', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // In a later chapter, this will read $request and persist a new Task.
        $location = $this->generateUrl('api_tasks_show', ['id' => 1]);

        return $this->json(['id' => 1, 'title' => 'New task'], 201, ['Location' => $location]);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(['id' => $id, 'title' => 'Example task']);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(['id' => $id, 'title' => 'Updated task']);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        return $this->json(null, 204);
    }
}
```

## Code Explanation

- All five routes share only two distinct paths (`/api/tasks` and `/api/tasks/{id}`), with `methods:` being what actually distinguishes them — this is the REST pattern described above, applied directly.
- Each route has a unique, descriptive `name:` following the `api_<resource>_<action>` convention introduced in the previous lesson, making it unambiguous which route is which even though several share the same path.
- Inside `create()`, `$this->generateUrl('api_tasks_show', ['id' => 1])` builds the URL for the *newly created* resource by referring to the `api_tasks_show` route by name, passing `1` as the value for that route's `{id}` parameter — this produces `/api/tasks/1` without hardcoding that string anywhere.
- The generated URL is placed in a `Location` header on the 201 response — a REST convention indicating "here is where to find the resource you just created," which you'll see used again in the Controllers chapter.
- `delete()` returns `$this->json(null, 204)` — HTTP status 204 ("No Content") conventionally has no response body, appropriate for a successful deletion where there's nothing more to say.

## Common Mistakes

- Encoding the action into the URL path itself, like `/api/tasks/delete/{id}`, instead of using the DELETE HTTP method on `/api/tasks/{id}` — this works but abandons REST conventions that most Symfony developers (and API clients/tools) expect, making the API less predictable to integrate with.
- Hardcoding `/api/tasks/1` as a plain string instead of using `generateUrl('api_tasks_show', ['id' => 1])` — this creates a hidden dependency on the exact path string that silently breaks if the route's path is ever changed.
- Forgetting that route names, not paths, are the unique key — it's fine (and common) for multiple routes to share an identical path string, as long as their `methods:` differ and their `name:` values are each unique.

## Best Practices

- Design new endpoints by first asking "what resource is this, and what HTTP verb describes this action" rather than inventing a new URL shape for every action — this keeps the whole API's URL surface small and consistent.
- Always generate URLs via route names (`generateUrl()` in controllers, or `path()` in Twig templates, covered later) rather than concatenating strings, even when it feels like "just this once" — consistency here pays off the first time you need to restructure a URL.
- Return the standard REST status codes for each action: `200` for successful GET/PUT, `201` for successful POST (creation), `204` for successful DELETE with no body — clients and tooling built against REST conventions rely on these being correct.

## Exercise

Using the same `/api/tasks/{id}` resource pattern, add a route for marking a task as complete without replacing its whole representation — a common REST pattern for this is a `PATCH` request to a sub-path like `/api/tasks/{id}/complete`. Write the `#[Route]` attribute and method signature, naming the route `api_tasks_complete`.

## Solution

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks/{id}/complete', name: 'api_tasks_complete', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function complete(int $id): JsonResponse
    {
        return $this->json(['id' => $id, 'done' => true]);
    }
}
```

The path `/api/tasks/{id}/complete` describes a specific sub-action on the task resource, restricted to `PATCH` (a partial update, appropriate since we're only changing the "done" status, not replacing the whole task). The route is uniquely named `api_tasks_complete`, distinct from the other task-related routes even though it shares the same `{id}` requirement pattern, so it could be safely referenced elsewhere via `generateUrl('api_tasks_complete', ['id' => $id])`.

## Mini Quiz

```yaml
- question: "Why must every route in a Symfony application have a unique name?"
  options:
    - "Names have no functional purpose beyond documentation"
    - "The name is used to generate URLs elsewhere in the code (e.g., generateUrl()), so it must unambiguously identify one specific route"
    - "Symfony requires names to match the controller's class name"
    - "Only the first route's name is actually used; others are ignored"
  answer: 1
  explanation: "A route's name is its stable, unique identifier, used to generate URLs from code without hardcoding path strings — if names weren't unique, generateUrl() wouldn't know which route to build a URL from."

- question: "How can two different routes both have the path /api/tasks/{id} without conflicting?"
  options:
    - "It's not possible; paths must always be unique"
    - "By giving them different methods (e.g., one GET, one DELETE) and unique names"
    - "By giving them the same name"
    - "Only if they are in different controller classes"
  answer: 1
  explanation: "A route match depends on both its path AND its allowed HTTP methods together. Two routes can share an identical path as long as their methods differ, and each still needs its own unique name."

- question: "What is the benefit of calling $this->generateUrl('api_tasks_show', ['id' => 5]) instead of hardcoding '/api/tasks/5'?"
  options:
    - "It runs faster than a hardcoded string"
    - "If the route's path ever changes, the generated URL automatically reflects the new path without needing to update every place the URL was used"
    - "It automatically validates that task 5 exists"
    - "There is no real benefit; both approaches are equivalent"
  answer: 1
  explanation: "generateUrl() builds the URL from the route's current path definition by name, so a later change to the path in the #[Route] attribute automatically propagates everywhere the URL is generated, unlike hardcoded path strings."
```
