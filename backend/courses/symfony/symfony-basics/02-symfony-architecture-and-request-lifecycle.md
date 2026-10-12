---
slug: symfony-architecture-and-request-lifecycle
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: symfony-basics
chapterTitle: "Chapter 1 — Symfony Basics"
chapterOrder: 1
title: "Symfony Architecture and the Request Lifecycle"
difficulty: intermediate
order: 2
estimatedMinutes: 12
tags: [symfony, architecture, http, routing]
---

## Learning Objective

Understand the full journey a single HTTP request takes through a Symfony application, from the moment it hits the server to the moment a response is sent back to the browser or API client.

## Explanation

Every time someone visits a URL, submits a form, or an API client calls an endpoint, that is an **HTTP request**. In plain PHP, a request typically arrives at a specific `.php` file on disk (e.g., visiting `/tasks.php` runs `tasks.php`). Symfony works differently: every single request — no matter the URL — is funneled through one entry point, and Symfony itself figures out what code should handle it. Understanding this flow is essential, because it explains *where* your code plugs in and *why* Symfony asks you to write things the way it does.

Here is the journey, step by step:

1. **The request arrives at the front controller.** In a Symfony project, there is one PHP file, `public/index.php`, that every request goes through (this is the only PHP file directly exposed to the web; everything else lives outside `public/` for security). It creates Symfony's `Kernel` object and hands the request to it.
2. **Routing matches the URL to a route.** "Routing" means mapping a URL pattern (like `/api/tasks/{id}`) to the specific piece of code that should run for it. Symfony looks through all the routes defined in your application (which you'll define using PHP attributes, covered in the next chapter) and finds the one that matches the incoming URL and HTTP method (GET, POST, etc.). If nothing matches, Symfony returns a 404 Not Found response automatically — you never write that check yourself.
3. **The matching controller runs.** Once a route matches, Symfony calls the corresponding controller method (a plain PHP method you wrote), automatically passing in any parameters extracted from the URL (like the `5` in `/api/tasks/5`) and any services the method asks for (you'll learn *how* Symfony knows what to pass in Chapter 5, on services and autowiring).
4. **The controller returns a Response object.** Your controller method must return a Symfony `Response` object (or a subclass, like `JsonResponse`) — never a raw string or an echoed value. This object bundles together the status code (200, 404, etc.), headers, and body content.
5. **Symfony sends the Response back.** The kernel takes your `Response` object, converts it into the actual raw HTTP text (status line, headers, body), and sends it back to whoever made the request — a browser, or in this course's case, the Vue frontend making an API call.

Along the way, Symfony also fires internal "events" (like `kernel.request` before routing and `kernel.response` before sending) that let you hook in extra behavior — for example, checking authentication before a controller runs. You don't need to build this event system yourself; it's part of what the framework already gives you.

## Why It Matters

Debugging a Symfony application without understanding this lifecycle is guesswork. If a request returns an unexpected 404, an experienced developer immediately thinks "the route didn't match — check the URL, the method, or the route requirements" rather than digging through unrelated files. If a response has the wrong `Content-Type` or missing headers, you know to look at how the `Response` object was built. Understanding the lifecycle also explains *why* your controller must return a `Response` object instead of just echoing text: because the kernel — not your code — is responsible for actually sending bytes to the client, and it needs a structured object to do that correctly (setting headers, status codes, and so on in the right order).

## Example

A GET request to `/api/tasks/5` arrives at `public/index.php`. The router scans the registered routes and finds one whose pattern `/api/tasks/{id}` matches, with method `GET`. Symfony calls `TaskController::show(5)`. Inside, the controller loads task #5 (in a real app, from the database) and returns `$this->json($task)`. The kernel turns that into an HTTP response with status `200`, header `Content-Type: application/json`, and the JSON body, and sends it to the Vue frontend that made the request.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $task = $this->findTask($id); // pretend this loads from the database

        if (null === $task) {
            // Explicitly building a 404 Response — Symfony does this automatically
            // when NO route matches at all, but here WE matched a route and
            // then decided, ourselves, that the specific task doesn't exist.
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($task);
    }

    private function findTask(int $id): ?array
    {
        $tasks = [5 => ['id' => 5, 'title' => 'Write lesson', 'done' => false]];

        return $tasks[$id] ?? null;
    }
}
```

## Code Explanation

- The `#[Route(...)]` attribute is what makes step 2 (routing) possible for this URL — without it, Symfony has no route to match and this method would never run.
- `public function show(int $id): JsonResponse` is the controller step (step 3): Symfony calls this automatically once the route matches, injecting `5` as `$id` because it was captured from the URL.
- The method always returns some form of `Response` (here, `JsonResponse`, a subclass) — whether the task was found (200) or not (404). This satisfies step 4: the kernel needs a `Response` object no matter what happened inside your logic.
- `Response::HTTP_NOT_FOUND` is a readable constant equal to the integer `404` — using named constants instead of raw numbers makes controller code self-documenting.
- Notice nothing in this file calls `header()` or `echo`s anything — the kernel (step 5) takes care of actually transmitting the response; your only job is to construct and return the right `Response` object.

## Common Mistakes

- Trying to `echo` output or call `header()` directly inside a controller instead of returning a `Response` object. Symfony's kernel expects a `Response` and will throw an error ("The controller must return a Response object") if you return a plain string or array directly.
- Assuming routing failures and "not found" business logic are the same thing. A 404 from *no route matching* happens before your controller ever runs; a 404 you return yourself (like above, when task #5 doesn't exist) happens *inside* your controller logic. Both produce a 404 status, but for different reasons — useful to know when debugging.
- Forgetting that `public/index.php` is the only entry point. Placing other PHP files inside `public/` and expecting Symfony's routing/security to apply to them is a mistake — anything outside the normal routing flow bypasses Symfony's request handling entirely.

## Best Practices

- When debugging an unexpected response, mentally walk the lifecycle in order: did the route match? Did the right controller run? What `Response` did it actually return? This narrows down problems quickly.
- Keep controllers focused on steps 3 and 4 only (run the matching logic, return a `Response`) — heavier business logic belongs in services, which you'll learn about in Chapter 5, and which keeps controllers thin and easy to reason about.
- Use `JsonResponse` (via `$this->json(...)`) for API responses rather than manually building a generic `Response` with a JSON string — it correctly sets the `Content-Type` header for you.

## Exercise

Describe, in your own words and in order, the five steps a GET request to `/api/tasks/42` goes through in a Symfony application, assuming task 42 does not exist in the database. Your description should end with what HTTP status code the client receives and why.

## Solution

1. The request hits `public/index.php`, the single front controller, which creates the kernel.
2. The router scans defined routes and finds that `/api/tasks/{id}` (GET) matches `/api/tasks/42`, capturing `42` as the `id` parameter.
3. Symfony calls the matching controller method, passing `42` as `$id`.
4. Inside the controller, the code looks up task 42, finds nothing, and constructs a `JsonResponse` with an error message and status `404`.
5. The kernel takes that `Response` object and sends it back to the client as raw HTTP, so the client receives a `404 Not Found` status — not because routing failed, but because the controller's own logic explicitly decided the task doesn't exist.

## Mini Quiz

```yaml
- question: "What is the single PHP file that every request to a Symfony application passes through?"
  options:
    - "Whichever controller file matches the URL"
    - "public/index.php, the front controller"
    - "config/routes.php"
    - "src/Kernel.php directly from the browser"
  answer: 1
  explanation: "public/index.php is Symfony's front controller — the one entry point exposed to the web that boots the kernel for every incoming request."

- question: "What must every Symfony controller method return?"
  options:
    - "A plain PHP array"
    - "An echoed string"
    - "A Response object (or subclass like JsonResponse)"
    - "Nothing; Symfony infers the output automatically"
  answer: 2
  explanation: "Symfony's kernel requires controllers to return a Response object so it can correctly build the status code, headers, and body before sending the HTTP response."

- question: "What does 'routing' refer to in the request lifecycle?"
  options:
    - "Sending emails based on user actions"
    - "Mapping an incoming URL (and HTTP method) to the specific code that should handle it"
    - "Compiling PHP into machine code"
    - "Connecting to the MySQL database"
  answer: 1
  explanation: "Routing is the step where Symfony matches the incoming request's URL and method against registered route patterns to determine which controller method should run."
```
