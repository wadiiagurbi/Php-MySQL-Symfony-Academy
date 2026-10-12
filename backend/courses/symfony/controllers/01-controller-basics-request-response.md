---
slug: controller-basics-request-response
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: controllers
chapterTitle: "Chapter 3 — Controllers"
chapterOrder: 3
title: "Controller Basics: Request and Response"
difficulty: intermediate
order: 1
estimatedMinutes: 10
tags: [symfony, controllers, http]
---

## Learning Objective

Understand what a controller is, how to read incoming request data using Symfony's `Request` object, and how to build outgoing `Response` objects manually.

## Explanation

Recall from Chapter 1 that a **controller** is the piece of code Symfony calls once a route has matched an incoming request — its job is to run whatever logic is needed and return a `Response` object. So far, every example controller method took its inputs only from route parameters (like `{id}`) and returned output using the `$this->json(...)` shortcut. Real controllers usually need more: reading query strings, request bodies, and headers, and occasionally building responses more explicitly than the JSON shortcut allows.

Symfony represents the entire incoming HTTP request as a single PHP object: `Symfony\Component\HttpFoundation\Request`. Instead of reading PHP's raw superglobals (`$_GET`, `$_POST`, `$_SERVER`) as you did earlier in this course, you get an instance of this object handed to you by simply type-hinting it as an argument in your controller method — Symfony recognizes the `Request` type and automatically passes in the current request, no matter where you put the argument in your method signature. This is a small preview of a bigger idea — Symfony automatically supplying objects your controller needs — that you'll study in full in the Services & Dependency Injection chapter.

The `Request` object exposes the different sources of input as clearly separated sub-objects:

- `$request->query` — the URL's query string parameters (`?page=2` becomes `$request->query->get('page')`).
- `$request->request` — traditional form-encoded POST body fields.
- `$request->getContent()` — the raw request body as a string, which is what you use for a JSON API request body (you then `json_decode()` it yourself, or use a dedicated deserializer, covered in a later chapter).
- `$request->headers` — incoming HTTP headers.

On the output side, `Symfony\Component\HttpFoundation\Response` is the base class representing everything about an HTTP response: status code, headers, and body content. `$this->json(...)` (used in every previous lesson) is a convenient shortcut on `AbstractController` that builds a `JsonResponse` (a `Response` subclass) for you. But you can also construct a plain `Response` directly, which is useful for non-JSON output, like plain text or a specific status code with no meaningful body.

## Why It Matters

Every real controller in a production Symfony API reads *some* form of client input beyond the URL path — filtering a list by a query parameter, reading a JSON body on a POST/PUT request, or checking a header for something like an API version. Understanding the `Request` object's structure (query vs. request vs. raw content vs. headers) prevents a common beginner confusion: trying to read a JSON POST body with `$request->request->get(...)` (which only works for traditional form-encoded bodies) and getting `null` back, then incorrectly concluding the data "isn't there." Knowing exactly where each kind of input lives on the `Request` object is a foundational skill for writing correct controllers.

## Example

A task-tracking API's list endpoint should support optional pagination via a `page` query parameter (e.g., `/api/tasks?page=2`), defaulting to page 1 if not provided, and a create endpoint needs to read a JSON body like `{"title": "Buy milk"}` sent in a POST request.

## Code

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Query string: /api/tasks?page=2  ->  $page = 2
        $page = $request->query->getInt('page', 1);

        return $this->json(['page' => $page, 'items' => []]);
    }

    #[Route('/api/tasks', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // JSON request bodies must be read from raw content and decoded manually.
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || '' === trim($data['title'])) {
            return new Response('Missing required field: title', Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['id' => 1, 'title' => $data['title']], Response::HTTP_CREATED);
    }
}
```

## Code Explanation

- `public function list(Request $request): JsonResponse` — type-hinting `Request $request` is all it takes for Symfony to hand this controller the current incoming request object; you never construct a `Request` yourself.
- `$request->query->getInt('page', 1)` reads the `page` query string parameter, converts it to an `int`, and falls back to `1` if it wasn't provided at all — `query` is a `ParameterBag` object with typed getters like `getInt()`, `getBoolean()`, and the plain `get()`.
- `$request->getContent()` returns the raw body of the request as a string — for a JSON API request, this is the JSON text itself, which `json_decode($request->getContent(), true)` turns into a plain PHP associative array (the `true` argument requests an array instead of PHP's default `stdClass` objects for decoded JSON).
- The `create()` method demonstrates building a `Response` object directly (not through `$this->json()`) for the error case — `new Response('Missing required field: title', Response::HTTP_BAD_REQUEST)` sends plain text with a 400 status, appropriate when the response body isn't JSON data.
- `Response::HTTP_CREATED` and `Response::HTTP_BAD_REQUEST` are readable constants for `201` and `400` respectively — using them instead of raw numbers is a common Symfony convention that makes controller code self-documenting.

## Common Mistakes

- Calling `$request->request->get('title')` expecting to read a JSON body — `$request->request` only contains data from traditional `application/x-www-form-urlencoded` or `multipart/form-data` bodies, not raw JSON. JSON bodies must go through `$request->getContent()` and be decoded explicitly.
- Forgetting to validate that `json_decode()` actually succeeded before using the result — if the client sends malformed JSON, `json_decode()` returns `null`, and code that then tries `$data['title']` on `null` triggers a PHP warning/error rather than a clean 400 response.
- Mixing up `$request->query` (URL query string, always available regardless of HTTP method) with `$request->request` (POST-style form body) — a GET request can have query parameters, but never a meaningful "form body" in the same sense.

## Best Practices

- Always provide a sensible default value to `ParameterBag` getters (like `getInt('page', 1)`) rather than assuming a query parameter will always be present.
- Validate decoded JSON input defensively before trusting its shape — check keys exist and have the expected type before using them, exactly as shown checking `isset($data['title'])`.
- Prefer the typed getters (`getInt`, `getBoolean`) over the generic `get()` followed by manual casting — they handle edge cases (like a non-numeric string passed as a supposedly numeric parameter) more predictably.

## Exercise

Write a controller method for `PUT /api/tasks/{id}`, reading a JSON body with an optional `title` field. If `title` is present and non-empty, return a 200 JSON response echoing the updated title; if the JSON body is missing a `title` field entirely, return a 400 plain-text `Response` with the message "title is required".

## Solution

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_tasks_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || '' === trim($data['title'])) {
            return new Response('title is required', Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['id' => $id, 'title' => $data['title']]);
    }
}
```

The method reads the raw JSON body via `$request->getContent()` and decodes it into an array. It checks that `title` exists and isn't empty using the same pattern as the create example; if missing, it returns a plain `Response` with status 400 and the exact required message. Otherwise, it returns a JSON response with the updated title, using the default `200` status that `$this->json()` applies automatically when no status is given.

## Mini Quiz

```yaml
- question: "How does a Symfony controller method receive the current HTTP request without constructing it manually?"
  options:
    - "By reading PHP's $_SERVER superglobal directly"
    - "By type-hinting a Request argument in the method signature; Symfony passes in the current request automatically"
    - "By calling Request::getCurrent() as a static method"
    - "Requests are not available inside controllers"
  answer: 1
  explanation: "Symfony recognizes the Request type-hint on a controller method argument and automatically supplies the current request object, regardless of where the argument appears in the signature."

- question: "Where should you read a raw JSON body sent in a POST request?"
  options:
    - "$request->request->get('fieldName')"
    - "$request->query->get('fieldName')"
    - "$request->getContent(), then decode it yourself with json_decode()"
    - "$_POST['fieldName']"
  answer: 2
  explanation: "$request->request only holds traditional form-encoded body data. A raw JSON body must be read as a string via getContent() and decoded explicitly, typically with json_decode()."

- question: "What does $request->query->getInt('page', 1) do if the 'page' query parameter is missing from the URL?"
  options:
    - "Throws an exception"
    - "Returns null"
    - "Returns the integer 1, the provided default value"
    - "Returns the string \"1\""
  answer: 2
  explanation: "The second argument to getInt() (and similar ParameterBag getters) is a default value returned when the parameter is absent, here returning the integer 1."
```
