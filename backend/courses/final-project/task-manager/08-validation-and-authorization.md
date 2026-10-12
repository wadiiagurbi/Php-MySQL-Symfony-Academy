---
slug: validation-and-authorization
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Validation and Authorization"
difficulty: advanced
order: 8
estimatedMinutes: 12
tags: [final-project, task-manager, validation, security]
---

## Learning Objective

By the end of this lesson, you will be able to enumerate every validation constraint on `TaskRequest`, and explain — with a justification, not just a description — why this project returns `401` for "not logged in" but `404` (not `403`) for "logged in, but not your task."

## Explanation

**Validation** answers "is this input well-formed?" and happens entirely at the DTO layer, before any business logic runs:

```php
final class TaskRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 140, maxMessage: 'Title cannot be longer than {{ limit }} characters.')]
    public string $title = '';

    public ?string $description = null;

    #[Assert\Choice(choices: ['TODO', 'IN_PROGRESS', 'DONE'], message: 'Status must be TODO, IN_PROGRESS or DONE.')]
    public string $status = 'TODO';

    #[Assert\Choice(choices: ['LOW', 'MEDIUM', 'HIGH'], message: 'Priority must be LOW, MEDIUM or HIGH.')]
    public string $priority = 'MEDIUM';

    #[Assert\Date(message: 'Due date must be a valid date (YYYY-MM-DD).')]
    public ?string $dueDate = null;

    #[Assert\Length(max: 80)]
    public ?string $category = null;
}
```

Every constraint here maps directly to a rule from the requirements lesson: title is required and bounded (matching the entity's `VARCHAR(140)` column); status and priority are restricted to exactly the strings the enums accept, so `TaskStatus::from()` in the service layer never receives a value it can't handle; the due date must parse as a real calendar date; category has a length ceiling matching its own column. `RequestValidator::fromJson` runs these constraints and throws `ValidationFailedException` on any failure, which `ApiExceptionListener` turns into `422 Unprocessable Entity` with a `details` array — one entry per failed field.

**Authorization** answers a different question: "is this authenticated user *allowed* to touch this specific resource?" This project actually distinguishes two authorization failures that are easy to conflate:

- **Not authenticated at all** → `401 Unauthorized`. This is enforced by the `access_control` rules in `security.yaml` (`{ path: ^/api, roles: IS_AUTHENTICATED_FULLY }`) before a request even reaches a controller. `ApiExceptionListener` also maps any `AuthenticationException` to 401 as a safety net.
- **Authenticated, but the resource belongs to someone else** → `404 Not Found`, via `findOwnedTask`, covered in the previous lesson.

The deliberate choice here is **not** to use `403 Forbidden` for the second case. A `403` response confirms to the caller "yes, task #999 exists, you're just not allowed to see it" — which leaks the existence of a resource the caller has no legitimate reason to know about. Returning `404` for both "doesn't exist" and "exists but isn't yours" makes the two cases indistinguishable from the outside, denying an attacker any way to enumerate valid task IDs belonging to other users by watching for a 403-vs-404 split.

## Why It Matters

This is a real, well-known API security pattern (sometimes called "404 over 403" or object-level access control), and recognizing when to apply it is exactly the kind of judgment call production teams make deliberately, not by accident. It's also a good example of validation and authorization being genuinely separate concerns that happen at different layers and different times in the request lifecycle: validation runs on the *shape* of input regardless of who's asking; authorization runs on the *combination* of who's asking and which specific resource they're asking about.

## Example

Three requests to the same task ID, showing all three outcomes:

```
GET /api/tasks/42          (no Authorization header)     -> 401 Authentication required.
GET /api/tasks/42          (token for a different user)  -> 404 Task not found.
GET /api/tasks/42          (token for the actual owner)   -> 200 { ...task json... }
```

And a validation failure, independent of authorization entirely:

```
POST /api/tasks   {"title": ""}
-> 422 {"error": {"message": "Validation failed.", "details": [{"field": "title", "message": "Title is required."}]}}
```

## Code

```php
<?php
// Adapted from src/EventListener/ApiExceptionListener.php — the mapping
// that turns validation and authentication failures into distinct,
// consistent status codes.

private function resolve(\Throwable $exception): array
{
    if ($exception instanceof ValidationFailedException) {
        $details = [];
        foreach ($exception->getViolations() as $violation) {
            $details[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return [422, 'Validation failed.', $details];
    }

    if ($exception instanceof AuthenticationException) {
        return [401, 'Authentication required.', null];
    }

    if ($exception instanceof HttpExceptionInterface) {
        return [$exception->getStatusCode(), $exception->getMessage() ?: 'Request failed.', null];
    }

    return [500, 'Internal server error.', null];
}
```

## Code Explanation

Three distinct exception types map to three distinct outcomes: `ValidationFailedException` (thrown by `RequestValidator`) always becomes `422` with a per-field `details` array built from the violation list — this is what lets a frontend highlight exactly which field was wrong. `AuthenticationException` (Symfony's own security exception, thrown when the `jwt: ~` authenticator can't verify a token, or when there's no token at all against a protected route) always becomes `401`. `NotFoundHttpException` — the one `findOwnedTask` throws — falls through to the generic `HttpExceptionInterface` branch, which simply reads the status code (`404`) and message off the exception itself; this is the same branch any other deliberately-thrown HTTP exception in the app would hit.

## Common Mistakes

- Confusing validation errors with authorization errors — putting an ownership check inside the DTO's constraints, or putting a `Choice` constraint check manually inside the controller after the DTO has already validated it.
- Using `403 Forbidden` for "not your resource," which unintentionally confirms the resource's existence to an unauthorized caller.
- Relying only on `access_control` in `security.yaml` for authorization and forgetting that per-resource ownership (an authenticated user hitting *someone else's* specific task) needs its own explicit check — `access_control` only knows about roles and paths, never about which row a request is trying to reach.

## Best Practices

- Keep validation entirely declarative on the DTO (`Assert\*` attributes) so the rules are visible in one place and enforced before any business logic runs.
- Choose `404` deliberately (not by default) for object-level authorization failures on resources where existence itself is sensitive information; document that choice, since it surprises developers used to `403`.
- Let one exception listener centralize the exception-to-status-code mapping, so every controller in the app gets the same consistent error shape without repeating logic.

## Exercise

A new requirement arrives: administrators (`ROLE_ADMIN`) should be able to view *any* user's task by ID, for support purposes, and should get a real `403` (not a `404`) if they try to modify one — viewing is allowed, editing isn't. How would you adjust `findOwnedTask` and the controller to support this without breaking the existing 404-for-non-owners behavior for regular users?

## Solution

Split the single check into two: a lookup helper that returns the task if it exists (404 if not, regardless of role), and a separate ownership/permission check applied differently per action. For `show`, allow the request through if `$task->getOwner()->getId() === $this->currentUser()->getId()` **or** `$this->isGranted('ROLE_ADMIN')`. For `update`/`delete`, keep the strict owner-only check for regular users, but for an admin attempting to modify a task they don't own, throw `AccessDeniedException` (mapping to `403`) instead of `NotFoundHttpException` — since for an admin, "I found it but you can't edit it" is the honest, intended message, whereas for a regular user, existence itself should stay hidden.

## Mini Quiz

```yaml
- question: "Why does this project return 404, not 403, when an authenticated user requests another user's task?"
  options:
    - "403 is not supported by Symfony's HttpKernel"
    - "To avoid confirming to an unauthorized caller that a specific task ID exists at all"
    - "404 is faster to return than 403"
    - "TaskRepository cannot distinguish ownership from existence"
  answer: 1
  explanation: "Returning the same 404 for 'does not exist' and 'exists but isn't yours' prevents an attacker from using the status code to enumerate other users' valid task IDs."

- question: "Which exception type does RequestValidator::fromJson throw when a TaskRequest fails its constraints?"
  options:
    - "NotFoundHttpException"
    - "AuthenticationException"
    - "ValidationFailedException"
    - "AccessDeniedException"
  answer: 2
  explanation: "ValidationFailedException carries the list of constraint violations, which ApiExceptionListener converts into a 422 response with a details array."

- question: "What is the key difference between validation and object-level authorization in this project's design?"
  options:
    - "They are the same concern implemented in two places for redundancy"
    - "Validation checks the shape of input regardless of who is asking; authorization checks whether this specific user may access this specific resource"
    - "Validation only applies to GET requests"
    - "Authorization is optional in a stateless API"
  answer: 1
  explanation: "TaskRequest's constraints validate input shape independent of identity, while findOwnedTask's check depends on both who is asking and which row they're asking about — two different questions answered at two different layers."
```
