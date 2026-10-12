---
slug: route-parameters-and-requirements
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: routing
chapterTitle: "Chapter 2 — Routing"
chapterOrder: 2
title: "Route Parameters and Requirements"
difficulty: intermediate
order: 2
estimatedMinutes: 10
tags: [symfony, routing, parameters]
---

## Learning Objective

Learn how to capture dynamic pieces of a URL as route parameters, how Symfony passes them into your controller method, and how to constrain them with requirements so invalid URLs don't match at all.

## Explanation

The routes you saw in the previous lesson had fixed paths like `/api/tasks`. But most real endpoints need to operate on a *specific* resource identified by part of the URL — for example, `/api/tasks/5` to fetch task number 5, or `/api/tasks/17` for task 17. Writing a separate route for every possible ID is obviously not workable. Instead, Symfony lets you declare a **route parameter**: a named placeholder inside curly braces in the path, like `{id}` in `/api/tasks/{id}`.

When a request comes in for `/api/tasks/5`, Symfony's router matches it against the pattern `/api/tasks/{id}`, and extracts `5` as the value of the `id` placeholder. It then automatically passes that value into your controller method as an argument, matched by parameter name — if your method signature has `int $id`, Symfony passes the extracted value as `$id`, converting the string `"5"` from the URL into a PHP `int` because you type-hinted it as one.

By default, a route parameter like `{id}` matches almost any sequence of characters (except a forward slash, which would break up the path into another segment). This is often too permissive — for `/api/tasks/{id}`, you likely want `id` to only ever be numeric, so that a request to `/api/tasks/abc` doesn't even attempt to match this route (and hits a proper 404, or another more specific route, instead of reaching your controller with a nonsensical value). You constrain this using **requirements**: a regular expression per parameter, provided as an additional argument to the `#[Route]` attribute.

```
#[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'], requirements: ['id' => '\d+'])]
```

Here, `requirements: ['id' => '\d+']` tells Symfony that the `{id}` segment must match the regular expression `\d+` (one or more digits) to be considered a match at all. A request to `/api/tasks/5` matches; a request to `/api/tasks/abc` does not match this route (Symfony will either return a 404, or try other routes that might match `abc` instead — useful when you have multiple routes sharing a similar path shape).

You can also give a parameter a default value, making it optional in the URL, by giving the corresponding controller argument a default value in PHP itself (e.g., `function list(int $page = 1)` paired with a route like `/api/tasks/{page}` — though optional trailing parameters are more commonly handled via query strings rather than route segments, which you'll see in later controller lessons).

## Why It Matters

Getting requirements right is not just a nice-to-have — it directly affects both correctness and how cleanly your routes coexist. Without a numeric requirement on `{id}`, a URL like `/api/tasks/export` intended for a completely different, more specific route (say, exporting all tasks) might instead get incorrectly captured by the generic `/api/tasks/{id}` route, with `export` treated as an `id` value, leading to a confusing "task not found" error instead of running the export logic you actually wanted. In professional codebases with dozens of similarly shaped routes, requirements are what keep routing behavior precise and predictable rather than accidentally ambiguous.

## Example

A task-tracking API needs a route to fetch one task by its numeric database ID, and a separate, more specific route to fetch tasks by category slug (a URL-friendly text identifier, like `work` or `personal`). Both share a similar shape, so requirements are what let them coexist safely.

## Code

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    // Only matches when {id} is purely digits, e.g. /api/tasks/5
    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(['id' => $id, 'title' => 'Example task']);
    }

    // Only matches when {slug} is lowercase letters/hyphens, e.g. /api/tasks/category/work
    #[Route(
        '/api/tasks/category/{slug}',
        name: 'api_tasks_by_category',
        methods: ['GET'],
        requirements: ['slug' => '[a-z\-]+']
    )]
    public function byCategory(string $slug): JsonResponse
    {
        return $this->json(['category' => $slug, 'tasks' => []]);
    }
}
```

## Code Explanation

- `{id}` in the path and `int $id` in the method signature are linked by name — Symfony matches route placeholders to controller arguments by matching names, not by position, so the argument could be listed anywhere in the method signature and still receive the correct value.
- `requirements: ['id' => '\d+']` restricts the `show()` route to numeric IDs only. `\d+` is a standard regular expression meaning "one or more digit characters."
- The type-hint `int $id` causes Symfony to cast the matched string value to a real PHP integer before your method body runs — inside `show()`, `$id` is genuinely an `int`, not a string that merely looks numeric.
- The second route, `/api/tasks/category/{slug}`, has a different, more specific path shape (`category/{slug}` rather than just `{id}`), so it does not actually conflict with the first route even without requirements — the `requirements: ['slug' => '[a-z\-]+']` here additionally ensures only sensible slug-shaped values (lowercase letters and hyphens) are accepted, rejecting things like `/api/tasks/category/123` if that combination should never be valid.
- `string $slug` receives the raw matched text for the `{slug}` segment — no type conversion happens for `string` type-hints since the URL segment is already a string.

## Common Mistakes

- Leaving `{id}` unconstrained and assuming "PHP will just error out anyway" if someone passes text into a numeric-looking route — without a requirement, `/api/tasks/abc` would actually match the route (since `{id}` matches any non-slash text by default), and if the argument isn't explicitly type-hinted as `int`, your controller would receive the literal string `"abc"`, likely causing a database error deeper in the code instead of a clean 404.
- Writing an overly broad regular expression, like `.*`, as a "requirement" — this defeats the purpose, since `.*` matches everything, including empty strings and slashes in some contexts, providing no real constraint.
- Assuming route parameter names must match a database column name or similar — they only need to match the corresponding controller method's argument name; the connection to your data layer happens inside the method body, not in the routing itself.

## Best Practices

- Add a numeric requirement (`'\d+'`) to any route parameter representing a database primary key — this is one of the most common and valuable requirements you'll write in real Symfony applications.
- Keep requirements as specific as the actual expected data shape (e.g., a UUID format, a fixed-length code, lowercase slugs) rather than leaving parameters unconstrained "to be safe" — a route that's too permissive can unexpectedly swallow requests meant for a different, more specific route.
- When two routes could plausibly overlap in shape, order and constrain them deliberately, and verify the result with `php bin/console debug:router {route_name}` or by testing both URLs manually.

## Exercise

Write a route for fetching a single blog post by a numeric ID at the path `/api/posts/{postId}`, responding to GET only, named `api_posts_show`, with a requirement ensuring `postId` is numeric, and a matching controller method signature.

## Solution

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/api/posts/{postId}', name: 'api_posts_show', methods: ['GET'], requirements: ['postId' => '\d+'])]
    public function show(int $postId): JsonResponse
    {
        return $this->json(['id' => $postId, 'title' => 'Example post']);
    }
}
```

The route parameter `{postId}` in the path matches the `int $postId` argument by name. The `requirements: ['postId' => '\d+']` entry ensures only requests where that segment is entirely digits will match this route at all, so `/api/posts/abc` correctly falls through to a 404 instead of reaching this controller with an invalid value.

## Mini Quiz

```yaml
- question: "In the route path /api/tasks/{id}, what determines which controller argument receives the matched value?"
  options:
    - "The position of the argument in the method signature"
    - "The argument's name must match the {id} placeholder's name"
    - "Symfony always uses the first argument, regardless of name"
    - "You must manually extract it from the Request object"
  answer: 1
  explanation: "Symfony matches route placeholders to controller method arguments by name, not position, so an argument named $id receives the value captured by {id} in the path."

- question: 'What does adding requirements: [''id'' => ''\d+''] to a route accomplish?'
  options:
    - "It converts the id parameter into a string automatically"
    - "It restricts the route to only match when the {id} segment consists of one or more digits"
    - "It sets a default value for id if none is provided"
    - "It makes the id parameter optional"
  answer: 1
  explanation: "Requirements attach a regular expression constraint to a route parameter; the route will only match an incoming URL if that segment satisfies the given pattern, here restricting it to digits only."

- question: "Without any requirement on {id}, what value would a controller method with an untyped $id parameter receive for a request to /api/tasks/abc?"
  options:
    - "It would not match the route at all"
    - "The literal string \"abc\"; PHP would not automatically reject it as invalid"
    - "Symfony would throw a compile-time error"
    - "It would always default to 0"
  answer: 1
  explanation: "Without a requirement restricting the parameter to digits, and without a type-hint forcing conversion, {id} matches any non-slash text, so an untyped parameter would simply receive the raw string \"abc\"."
```
