---
slug: rest-concepts-and-json-responses
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: api-development
chapterTitle: "Chapter 11 — API Development"
chapterOrder: 11
title: "REST Concepts and JSON Responses"
difficulty: advanced
order: 1
estimatedMinutes: 12
tags: [symfony, api, rest, http]
---

## Learning Objective

Understand what "REST" means, how HTTP methods and status codes give an API a shared vocabulary, and how to return correct, meaningful JSON responses from a Symfony controller.

## Explanation

So far in this course, your controllers have mostly returned rendered HTML or a handful of JSON endpoints without thinking too hard about *why* they were shaped the way they were. Now that you are building a real API consumed by a separate Vue frontend, you need a shared convention so that the frontend (and any other client) can predict how your backend behaves without reading its source code every time.

**REST** (Representational State Transfer) is not a library or a protocol — it is an architectural *style* for designing APIs on top of plain HTTP. The core idea is simple: your application is made of **resources** (a task, a user, a category), each resource has a **URL** that identifies it (`/api/tasks/5`), and you use the existing **HTTP methods** to say what you want to do with that resource, instead of inventing your own verbs in the URL:

- `GET /api/tasks` — list tasks (read, no side effects).
- `GET /api/tasks/5` — read a single task.
- `POST /api/tasks` — create a new task.
- `PUT /api/tasks/5` — replace/update an existing task.
- `DELETE /api/tasks/5` — delete a task.

Notice what is absent: there is no `/api/getTasks` or `/api/deleteTask?id=5`. The verb lives in the HTTP method, not the URL. This is what makes an API "RESTful" rather than just "an API that happens to return JSON."

The second half of REST's shared vocabulary is the **HTTP status code** returned with every response — a three-digit number telling the client, at a glance, how the request went, without it having to parse the response body first. The codes you will use constantly in a Symfony API:

- **200 OK** — the request succeeded and the body contains the result (a `GET`, or a successful `PUT`).
- **201 Created** — a `POST` succeeded and a *new* resource now exists; the body usually contains that new resource.
- **204 No Content** — the request succeeded but there is nothing meaningful to return (a typical `DELETE` response).
- **400 Bad Request** — the request itself was malformed (e.g., invalid JSON syntax in the body) — the server could not even understand what was asked.
- **401 Unauthorized** — the client did not prove who it is (missing or invalid authentication credentials).
- **403 Forbidden** — the client *is* known, but is not allowed to do this specific thing (e.g., accessing someone else's task).
- **404 Not Found** — the URL does not correspond to any resource that exists (or, for security, one the current user is allowed to see).
- **422 Unprocessable Entity** — the request was well-formed JSON, but the *data* failed validation (e.g., a blank title).
- **500 Internal Server Error** — something broke on the server that the client could not have prevented.

Getting these right matters because the client's code branches on them: a Vue frontend might redirect to a login page on 401, show a form's field errors on 422, and show a generic error toast on 500 — three completely different behaviors that the status code alone communicates.

## Why It Matters

A frontend team (or a third-party integrator) that consumes your API cannot read your PHP source code — the HTTP method, URL, and status code *are* the contract. If every endpoint in your application follows the same REST conventions, a new developer can guess how an endpoint behaves before reading a single line of its controller, and generic frontend code (like a shared HTTP client that redirects to login on any 401) can be written once and reused everywhere. Inconsistent APIs — one endpoint returning 200 for an error, another burying the real status inside the JSON body — force every client to special-case your API, which is exactly the kind of hidden complexity that causes production bugs months later.

## Example

Consider deleting a task. A non-RESTful design might expose `POST /api/deleteTask` with `{"id": 5}` in the body, and always return `200 OK` with `{"success": true}` or `{"success": false}` — forcing every caller to parse the body just to know if it worked. The RESTful version exposes `DELETE /api/tasks/5` and returns a bare `204 No Content` on success, or a `404 Not Found` with a JSON error body if the task does not exist (or does not belong to the current user) — the status code alone tells the caller everything, no body parsing required for the success case.

## Code

```php
<?php

namespace App\Controller\Api;

use App\Repository\TaskRepository;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_tasks_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, TaskRepository $taskRepository, TaskService $taskService): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (null === $task || $task->getOwner()->getId() !== $this->getUser()->getId()) {
            // 404, not 403 — we don't reveal that a task with this id exists at all
            // if it belongs to someone else.
            throw new NotFoundHttpException('Task not found.');
        }

        $taskService->delete($task);

        // No body needed: 204 tells the client "it worked, there's nothing to show."
        return $this->json(null, 204);
    }
}
```

## Code Explanation

- `#[Route(..., methods: ['DELETE'])]` restricts this action to the `DELETE` HTTP method — a `GET` to the same URL is routed elsewhere (to `show()`), keeping the verb-to-action mapping explicit rather than relying on a query parameter like `?action=delete`.
- `$taskRepository->find($id)` looks up the task by its primary key; if Doctrine finds nothing, it returns `null` rather than throwing, so the check below is required.
- The ownership check (`$task->getOwner()->getId() !== $this->getUser()->getId()`) is deliberately combined with the "task doesn't exist" case into a single `404`, not split into "404 if missing, 403 if someone else's." This avoids leaking to an attacker that a given task ID exists but belongs to another user.
- `throw new NotFoundHttpException(...)` is Symfony's built-in exception for "this resource isn't there"; Symfony's kernel catches it and turns it into an HTTP 404 response automatically (you will see the custom version of this handling with `ApiExceptionListener` in the events chapter).
- `$this->json(null, 204)` returns an empty body with status 204 — the second argument to `AbstractController::json()` is always the HTTP status code, and it is easy to forget to pass it, which would silently default to 200.

## Common Mistakes

- Returning `200 OK` for every response and putting the real result inside a `{"success": false, "error": "..."}` JSON body. This defeats the purpose of status codes and forces every client to parse the body before knowing if anything went wrong.
- Using `404` for authorization failures on resources that genuinely exist but belong to someone else, when the app's threat model actually calls for `403`. For most apps (like this one), collapsing "not yours" into `404` is intentional and correct — but do it consciously, not because you never considered `403`.
- Returning `201 Created` from an endpoint that did not actually create anything (e.g., an update via `PUT`), or returning `200` from a `POST` that did create something — both break the predictable contract clients rely on.
- Putting the action verb in the URL (`/api/tasks/5/delete`) instead of using the HTTP method, which duplicates information and invites inconsistency once other developers start adding their own verb conventions.

## Best Practices

- Pick one status code per situation and use it consistently across your entire API: always `201` for successful creation, always `204` for successful deletion, always `422` for validation failures.
- Let Symfony's exception-to-response mapping do the work: throwing the correct exception type (`NotFoundHttpException`, `ValidationFailedException`, etc.) and letting a listener translate it into the correct status code keeps every controller free of repetitive `if` statements.
- Document your API's status code conventions once (in a README or an OpenAPI spec) so frontend developers do not have to reverse-engineer them endpoint by endpoint.
- Reserve `500` truly for unexpected server bugs; if a situation is predictable (bad input, missing resource, no permission), it deserves a specific 4xx code instead.

## Exercise

Design (on paper, no code needed yet) the REST endpoints for a `Comment` resource nested under tasks: users should be able to list a task's comments, add a new comment, and delete one of their own comments. For each endpoint, write down the HTTP method, the URL, and the status code you would return on success.

## Solution

- `GET /api/tasks/{taskId}/comments` — list all comments for a task. Success: `200 OK` with a JSON array of comments.
- `POST /api/tasks/{taskId}/comments` — create a new comment on a task. Success: `201 Created` with the JSON of the newly created comment.
- `DELETE /api/tasks/{taskId}/comments/{commentId}` — delete a comment. Success: `204 No Content` with an empty body.

Each URL identifies a resource (a task's comments collection, or one specific comment), and the HTTP method — not a verb in the path — expresses the action. A `GET` to a nonexistent `taskId` or `commentId` should return `404`; a `POST` with a blank comment body should return `422`.

## Mini Quiz

```yaml
- question: "In REST, how should the action being performed on a resource typically be expressed?"
  options:
    - "As a verb inside the URL, like /api/deleteTask"
    - "Through the HTTP method (GET, POST, PUT, DELETE) applied to a resource URL"
    - "As a query parameter like ?action=delete"
    - "It doesn't matter as long as the response body says what happened"
  answer: 1
  explanation: "REST expresses actions through HTTP methods applied to resource URLs, not through verbs embedded in the path or query string."

- question: "A client sends a POST request with a syntactically valid JSON body, but the 'title' field is blank and fails a NotBlank validation constraint. What status code should the API return?"
  options:
    - "200 OK, with an error flag in the body"
    - "400 Bad Request"
    - "422 Unprocessable Entity"
    - "500 Internal Server Error"
  answer: 2
  explanation: "422 means the request was well-formed but the data failed validation rules — exactly this case. 400 is reserved for malformed requests (like broken JSON syntax), not valid-but-invalid data."

- question: "Why does returning 204 No Content for a successful DELETE make sense?"
  options:
    - "Because DELETE requests are not allowed to return any status code"
    - "Because the deleted resource no longer exists, so there is nothing meaningful left to return in the body"
    - "Because 204 means the deletion failed silently"
    - "Because JSON responses require exactly zero bytes"
  answer: 1
  explanation: "204 signals success with an intentionally empty body — appropriate for DELETE, since the resource that was deleted no longer exists to describe."

- question: "Why should a task deletion endpoint return 404 (not 403) when a user tries to delete another user's task?"
  options:
    - "403 does not exist in the HTTP specification"
    - "To avoid confirming to an attacker that a task with that ID exists at all"
    - "Because 404 is faster to process than 403"
    - "There is no reason; it is purely a Symfony limitation"
  answer: 1
  explanation: "Returning 404 instead of 403 for resources owned by someone else avoids leaking information about which resource IDs exist, which is a deliberate security-conscious choice many APIs make."
```
