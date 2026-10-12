---
slug: error-handling-and-testing
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Error Handling and Testing"
difficulty: advanced
order: 11
estimatedMinutes: 14
tags: [final-project, task-manager, testing, error-handling]
---

## Learning Objective

By the end of this lesson, you will be able to explain how one event listener guarantees every `/api/*` error is JSON, and read (or write) a functional test in the same style as this project's real `TaskApiTest`.

## Explanation

By default, an unhandled exception in a Symfony app renders an HTML error page — useless for an API a Vue frontend has to parse. This project solves that once, centrally, with `ApiExceptionListener`:

```php
#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$status, $message, $details] = $this->resolve($exception);

        $event->setResponse(new JsonResponse([
            'error' => array_filter([
                'message' => $message,
                'details' => $details,
            ], static fn ($value) => null !== $value),
        ], $status));
    }
}
```

The `#[AsEventListener(event: 'kernel.exception')]` attribute is all it takes to register this class on Symfony's exception event — no YAML configuration needed, thanks to autoconfiguration. The guard clause (`if (!str_starts_with(...))`) is what keeps this listener scoped to the API: if this app ever grew a traditional HTML admin section, exceptions there would still get Symfony's normal (or a custom Twig) error page, untouched by this listener. `resolve()` (covered in the Validation and Authorization lesson) maps specific exception types to specific status codes; anything unrecognized falls through to a generic `500`, so a raw stack trace can never leak into an API response, even for a bug nobody anticipated.

Testing this project happens at two levels, mirroring the Symfony Testing chapter: **unit tests** for isolated logic (this project's `CourseContentLoaderTest` is the reference example, testing a plain PHP class with no HTTP or database involved), and **functional tests** for full request-through-response behavior, using `WebTestCase`. Every test in `tests/Functional/` extends a small shared base, `ApiTestCase`, which resets the database schema before each test and provides a `registerAndLogin()` helper:

```php
abstract class ApiTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function registerAndLogin(string $email = 'learner@example.com', string $password = 'password123'): string
    {
        // ...registers via POST /api/auth/register, logs in via POST /api/auth/login,
        // returns "Bearer <token>" ready to pass as an Authorization header.
    }
}
```

Dropping and recreating the schema before every single test looks wasteful, but it buys something valuable: complete test isolation. No test can leave behind a row that makes a *different* test pass or fail depending on run order — a common, hard-to-debug source of flaky test suites. For this project's SQLite-backed test database, recreating the schema is fast enough (milliseconds) that the safety is essentially free.

A real test built on top of that base, `TaskApiTest::testUserCannotAccessAnotherUsersTask`, exercises the exact 404-not-403 design decision from the previous lesson end-to-end over real HTTP:

```php
public function testUserCannotAccessAnotherUsersTask(): void
{
    $tokenA = $this->registerAndLogin('owner@example.com');
    $id = $this->createTask($tokenA, 'Owner-only task');

    $tokenB = $this->registerAndLogin('intruder@example.com');
    $this->client->request('GET', "/api/tasks/{$id}", server: $this->authHeader($tokenB));

    self::assertResponseStatusCodeSame(404);
}
```

This is not a test of `findOwnedTask` in isolation — it registers two real users, creates a real task through the real API, and asserts on the real HTTP response another real user gets back. That's the defining trait of a functional test: it proves the *whole stack* (routing, security, controller, service, repository, database) behaves correctly together, not just one class in isolation.

## Why It Matters

A centralized exception listener and a disciplined functional test suite are two sides of the same goal: making the API's behavior predictable and provable, not just "probably fine." Without the listener, every controller would need its own try/catch boilerplate to avoid leaking HTML error pages or stack traces — easy to forget in just one place, which is all it takes to create an information-disclosure bug. Without functional tests that hit real routes with a real database, a refactor of `TaskRepository` or `security.yaml` could silently break authorization and nobody would notice until a user reported seeing someone else's tasks.

## Example

Two ways the exact same underlying bug — a missing entity — surfaces, both landing on a clean JSON `404` thanks to the listener:

```
GET /api/lessons/does-not-exist   -> {"error":{"message":"No lesson found for slug \"does-not-exist\"."}}
GET /api/tasks/999999             -> {"error":{"message":"Task not found."}}
```

One comes from `CourseContentLoader` throwing `LessonNotFoundException`; the other from `TaskController::findOwnedTask` throwing `NotFoundHttpException`. Neither controller has a single line of error-formatting code — the listener does that work for the entire app.

## Code

```php
<?php
// Adapted from tests/Functional/TaskApiTest.php

public function testDeleteTask(): void
{
    $token = $this->registerAndLogin();
    $id = $this->createTask($token, 'To be deleted');

    $this->client->request('DELETE', "/api/tasks/{$id}", server: $this->authHeader($token));
    self::assertResponseStatusCodeSame(204);

    $this->client->request('GET', "/api/tasks/{$id}", server: $this->authHeader($token));
    self::assertResponseStatusCodeSame(404);
}

private function createTask(string $token, string $title, string $status = 'TODO'): int
{
    $this->client->request('POST', '/api/tasks', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode([
        'title' => $title,
        'status' => $status,
    ]));

    return $this->jsonBody()['id'];
}
```

## Code Explanation

`testDeleteTask` follows the same three-step shape as most functional tests here: arrange (log in, create a task), act (call the endpoint under test), assert (check the response, and often a *second* request to confirm the side effect actually happened — here, that the deleted task really is gone, not just that the DELETE call returned the right status). `createTask` is a small private helper used across many tests in the file, exactly like `applyRequest` was a shared helper inside `TaskService` — the same "don't repeat yourself" instinct applies to test code as much as to application code.

## Common Mistakes

- Writing only unit tests for a service and never a functional test for the controller that calls it — a unit test can't catch a wrong route, a misconfigured firewall, or a serialization bug, since those only exist at the HTTP layer.
- Letting tests share state (e.g., reusing the same seeded user across tests) instead of resetting the schema or using a unique email per test — this project uses both: a fresh schema per test *and* unique emails per test (`'owner@example.com'`, `'intruder@example.com'`) to avoid the unique-email constraint colliding between tests.
- Forgetting to assert on the *shape* of an error response (e.g., that `error.message` exists) and only checking the status code — a 422 with an empty or malformed body is still a bug worth catching.
- Adding exception-to-JSON conversion logic inside individual controllers "just for now" instead of extending the one shared listener — this duplicates logic that will inevitably drift out of sync.

## Best Practices

- Centralize exception-to-response mapping in one listener scoped to the API path prefix, so new controllers get consistent error handling for free.
- Give every functional test a clean, isolated starting point (fresh schema, unique test data) rather than relying on execution order or hand-seeded fixtures shared across tests.
- Test both the "happy path" and the specific security/authorization edge cases (wrong owner, missing auth, invalid input) — the edge cases are exactly where real bugs tend to hide.
- Keep small private helpers (like `createTask`) in test classes to avoid repeating multi-line request setup in every test method.

## Exercise

Write a functional test, `testCannotDeleteAnotherUsersTask`, that mirrors `testUserCannotAccessAnotherUsersTask` but for the `DELETE` endpoint: user A creates a task, user B attempts to delete it, and the response should be `404` — and a follow-up `GET` by user A should prove the task still exists.

## Solution

```php
public function testCannotDeleteAnotherUsersTask(): void
{
    $tokenA = $this->registerAndLogin('owner2@example.com');
    $id = $this->createTask($tokenA, 'Protected task');

    $tokenB = $this->registerAndLogin('intruder2@example.com');
    $this->client->request('DELETE', "/api/tasks/{$id}", server: $this->authHeader($tokenB));
    self::assertResponseStatusCodeSame(404);

    $this->client->request('GET', "/api/tasks/{$id}", server: $this->authHeader($tokenA));
    self::assertResponseIsSuccessful();
}
```

This works because `TaskController::delete` calls the same `findOwnedTask` helper as `show` and `update` — the ownership check is shared code, so it's already correctly enforced for every action without writing it three separate times.

## Mini Quiz

```yaml
- question: "What is the main reason ApiExceptionListener checks the request path starts with /api/ before handling an exception?"
  options:
    - "It's required by Symfony for all event listeners"
    - "To avoid affecting error pages for any non-API part of the application"
    - "It makes the listener run faster"
    - "JSON responses are only valid for paths starting with /api/"
  answer: 1
  explanation: "The path check scopes this listener's JSON-only error behavior strictly to the API, leaving any traditional HTML routes (if the app ever had any) with Symfony's normal error handling."
- question: "Why does ApiTestCase drop and recreate the database schema before every test?"
  options:
    - "SQLite requires the schema to be rebuilt periodically"
    - "To guarantee each test starts from a clean, isolated state, unaffected by data from other tests"
    - "It makes the test suite run faster"
    - "It is required by PHPUnit's WebTestCase"
  answer: 1
  explanation: "Resetting the schema before each test prevents leftover data from one test from affecting another, avoiding order-dependent flaky tests."
- question: "What does a functional test verify that a unit test on TaskService alone cannot?"
  options:
    - "Whether the PHP code has any syntax errors"
    - "That the whole stack — routing, security, controller, service, repository, database — behaves correctly together over a real HTTP request"
    - "Functional tests are simply a faster way to run the same checks"
    - "Nothing; they test exactly the same thing"
  answer: 1
  explanation: "A functional test using WebTestCase sends a real HTTP request through the full application stack, catching integration issues (routing, firewall config, serialization) that an isolated unit test of one class cannot."
```
