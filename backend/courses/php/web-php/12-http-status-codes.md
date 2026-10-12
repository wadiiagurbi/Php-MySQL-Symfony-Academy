---
slug: http-status-codes
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "HTTP Status Codes in PHP"
difficulty: beginner
order: 12
estimatedMinutes: 9
tags: [php, http, status]
---

## Learning Objective

By the end of this lesson, you will be able to explain what HTTP status codes mean, use the most common ones correctly, and set them from PHP with `http_response_code()`.

## Explanation

Every HTTP response carries a three-digit **status code**: a number summarizing what happened while handling the request. The first digit defines the family:
- **2xx (success)** — the request worked. `200 OK` is the default for normal pages and API data.
- **3xx (redirection)** — the client should look elsewhere. `302 Found` pairs with a `Location` header to send the browser to a new URL.
- **4xx (client error)** — the request itself was wrong. `400 Bad Request` means malformed input, `401 Unauthorized` means login is needed, `403 Forbidden` means logged in but not allowed, and `404 Not Found` means the thing does not exist.
- **5xx (server error)** — the server failed while handling a valid request. `500 Internal Server Error` signals an unexpected bug.

In PHP, `http_response_code(404)` sets the status for the current response. It must run before any output, just like `header()`, and it works alongside the response body you echo afterward.

## Why It Matters

Status codes are the shared language between your application and everything around it: browsers, JavaScript `fetch()` calls, search engines, and monitoring tools all read the code before reading the body. Returning `200 OK` with "not found" text confuses every one of them, while correct codes make redirects work, errors detectable, and APIs predictable.

## Example

A product lookup page receives an `id` query parameter. If the id is missing it answers `400`, if the product does not exist it answers `404`, and otherwise it answers `200` with the product name.

## Code

```php
<?php

$catalog = ['1' => 'Coffee Mug', '2' => 'Notebook'];

$id = $_GET['id'] ?? null;

if ($id === null || $id === '') {
    http_response_code(400);
    echo 'Missing product id.';
    exit;
}

if (!isset($catalog[$id])) {
    http_response_code(404);
    echo 'Product not found.';
    exit;
}

http_response_code(200);
echo 'Product: ' . htmlspecialchars($catalog[$id]);
```

## Code Explanation

- `$_GET['id'] ?? null` reads the query parameter safely, yielding `null` when it is absent instead of raising a warning.
- The first branch answers `400 Bad Request` when the client sent no usable id, because the request itself is malformed.
- The second branch answers `404 Not Found` when the id is well formed but matches nothing, because the resource does not exist.
- The final branch answers `200 OK` explicitly and escapes output with `htmlspecialchars()` to keep the page safe from injected markup.

## Common Mistakes

- **Echoing output before setting the code.** Headers must precede the body, so any early `echo` locks in the default `200`.
- **Returning `200` for every error.** API clients and search engines treat `200` as success and will misbehave.
- **Confusing `401` and `403`.** Use `401` when the visitor must log in, and `403` when they are logged in but still not permitted.

## Best Practices

- Set the most specific accurate code for every response: `400`, `401`, `403`, `404`, or `500` instead of a generic fallback.
- Pair `3xx` redirects with a `Location` header so the browser knows where to go.
- Stop the script with `exit` after an error response so no success content leaks after the error message.

## Exercise

A `/profile` page requires login. If the visitor has no `user_id` in the session, respond with status `401` and the text "Please log in." Otherwise respond `200` with "Welcome back." Write the script.

## Solution

```php
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Please log in.';
    exit;
}

http_response_code(200);
echo 'Welcome back.';
```

## Mini Quiz

```yaml
- question: "What does the 404 status code mean?"
  options:
    - "The request succeeded"
    - "The requested resource could not be found"
    - "The browser should redirect to a new URL"
    - "The database server is offline"
  answer: 1
  explanation: "404 Not Found tells the client the URL is well formed but matches nothing on the server."
- question: "Which function sets the HTTP status code in PHP?"
  options:
    - "echo()"
    - "http_response_code()"
    - "session_start()"
    - "json_decode()"
  answer: 1
  explanation: "http_response_code() sets the numeric status of the current response and must run before any output."
- question: "When should a PHP script return 500?"
  options:
    - "When the visitor mistypes a URL"
    - "When the server hits an unexpected error while handling a valid request"
    - "When a page loads successfully"
    - "When redirecting to a login page"
  answer: 1
  explanation: "5xx codes signal server-side failure; 500 is the generic code for an unexpected internal error."
```
