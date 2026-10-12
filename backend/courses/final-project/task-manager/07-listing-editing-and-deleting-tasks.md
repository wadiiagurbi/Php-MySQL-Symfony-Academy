---
slug: listing-editing-and-deleting-tasks
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Listing, Editing, and Deleting Tasks"
difficulty: advanced
order: 7
estimatedMinutes: 12
tags: [final-project, task-manager, api, crud]
---

## Learning Objective

By the end of this lesson, you will be able to implement the remaining CRUD endpoints — `GET`, `PUT`, `DELETE` on `/api/tasks/{id}` — and explain the single shared helper that enforces ownership across all three.

## Explanation

`TaskController` has four route methods beyond `create`: `list`, `show`, `update`, and `delete`. Three of them — `show`, `update`, `delete` — all need the same guarantee: *only load a task that belongs to the current user*. Rather than repeating that check three times, the controller centralizes it in one private helper:

```php
private function findOwnedTask(int $id, TaskRepository $taskRepository): Task
{
    $task = $taskRepository->find($id);

    if (null === $task || $task->getOwner()->getId() !== $this->currentUser()->getId()) {
        throw new NotFoundHttpException('Task not found.');
    }

    return $task;
}
```

Every one of `show`, `update`, and `delete` calls this exact method before doing anything else:

```php
#[Route('/api/tasks/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
public function show(int $id, TaskRepository $taskRepository): JsonResponse
{
    return $this->json(TaskView::fromEntity($this->findOwnedTask($id, $taskRepository)));
}

#[Route('/api/tasks/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
public function update(int $id, Request $request, TaskRepository $taskRepository, RequestValidator $requestValidator, TaskService $taskService): JsonResponse
{
    $task = $this->findOwnedTask($id, $taskRepository);
    $data = $requestValidator->fromJson($request, TaskRequest::class);

    return $this->json(TaskView::fromEntity($taskService->update($task, $data)));
}

#[Route('/api/tasks/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
public function delete(int $id, TaskRepository $taskRepository, TaskService $taskService): JsonResponse
{
    $taskService->delete($this->findOwnedTask($id, $taskRepository));

    return $this->json(null, 204);
}
```

Note the `requirements: ['id' => '\d+']` on every `{id}` route — this ensures Symfony's router only matches numeric IDs, so a request to `/api/tasks/abc` falls through to a 404 from routing itself rather than reaching the controller with a non-numeric `$id` that would need its own error handling.

`update` delegates the actual field changes to `TaskService::update`, which reuses the same `applyRequest` helper from `create` (covered in the previous lesson) plus one line specific to updates:

```php
public function update(Task $task, TaskRequest $request): Task
{
    $task->setTitle($request->title);
    $this->applyRequest($task, $request, $task->getOwner());

    $this->entityManager->flush();

    return $task;
}
```

Unlike `create`, there's no `persist()` call — `$task` is already a managed entity (it was loaded from the repository), so Doctrine's unit of work is already tracking it; calling `flush()` alone is enough for any changed properties to be written back.

`delete` is the simplest: `TaskService::delete` calls `$this->entityManager->remove($task)` followed by `flush()`, and the controller returns an empty `204 No Content` — the conventional response for a successful delete with nothing to give back.

## Why It Matters

Extracting `findOwnedTask` once and reusing it three times is what actually *guarantees* the ownership rule holds everywhere it needs to. If each action re-implemented its own ownership check, a future edit to one of them (or a new endpoint added later) could easily introduce a copy-paste mistake — comparing the wrong ID, or forgetting the check entirely. A single, small, well-named method is both the implementation and the enforcement point; there's exactly one place to audit if you ever need to verify this rule is correct.

## Example

Requesting `GET /api/tasks/999` for a task ID that either doesn't exist, or belongs to a different user, produces the exact same response either way:

```
GET /api/tasks/999
Authorization: Bearer <token-for-a-different-user>

404 Not Found
{"error": {"message": "Task not found."}}
```

This is intentional and is the subject of the next lesson: from the outside, "doesn't exist" and "isn't yours" are indistinguishable, which is itself a security property.

## Code

```php
<?php
// Adapted from src/Controller/Api/TaskController.php

private function findOwnedTask(int $id, TaskRepository $taskRepository): Task
{
    $task = $taskRepository->find($id);

    if (null === $task || $task->getOwner()->getId() !== $this->currentUser()->getId()) {
        throw new NotFoundHttpException('Task not found.');
    }

    return $task;
}

private function currentUser(): User
{
    /** @var User $user */
    $user = $this->getUser();

    return $user;
}
```

## Code Explanation

`$taskRepository->find($id)` performs a plain primary-key lookup with no owner filter at all — the ownership check happens entirely in the very next line, comparing `$task->getOwner()->getId()` against `$this->currentUser()->getId()`. Comparing IDs (integers) rather than comparing `User` objects directly avoids any ambiguity around object identity across different Doctrine unit-of-work instances. Throwing `NotFoundHttpException` (rather than, say, an `AccessDeniedException`) is what produces the 404 status — `ApiExceptionListener` maps any `HttpExceptionInterface` straight to its status code, so this exception's *class* is effectively what decides the HTTP response, another reason the exception type chosen here matters.

## Common Mistakes

- Filtering by owner directly in the SQL query (e.g. `$taskRepository->findOneBy(['id' => $id, 'owner' => $user])`) *and* separately checking ownership in the controller — redundant and easy to let drift out of sync. This project deliberately does the ownership check in one place only.
- Returning `403 Forbidden` for another user's task instead of `404` — this project's chosen convention (explained fully in the next lesson) is to make owned and non-existent resources indistinguishable from the outside.
- Skipping the `requirements: ['id' => '\d+']` route constraint and instead validating `$id` manually inside the method body — pushing a routing concern into business logic when Symfony's router already solves it.
- Calling `persist()` on an already-managed entity in `update()` — unnecessary, since Doctrine is already tracking it; the extra call does no harm but signals a misunderstanding of the unit of work.

## Best Practices

- Extract a single ownership-checking helper and route every ownership-sensitive action through it, rather than duplicating the check.
- Use route `requirements` to constrain path parameters (numeric IDs, slugs, etc.) instead of hand-rolling that validation in the controller.
- Return `204 No Content` with an empty body for successful deletes — it's the conventional, unambiguous response and avoids inventing a JSON shape for "nothing to say."

## Exercise

Add a `PATCH /api/tasks/{id}/status` endpoint that updates only a task's status (not the full `PUT` payload), reusing `findOwnedTask`. What would the request body and DTO look like, and which existing service method (if any) could you reuse?

## Solution

Add a small DTO, `TaskStatusRequest`, with a single validated field: `#[Assert\Choice(choices: ['TODO', 'IN_PROGRESS', 'DONE'])] public string $status = '';`. The controller method would call `$this->findOwnedTask($id, $taskRepository)` exactly as `update` does, then call a new, narrower service method — `TaskService::updateStatus(Task $task, string $status)` — that does just `$task->setStatus(TaskStatus::from($status)); $this->entityManager->flush();`, rather than reusing the full `applyRequest`, since a status-only update shouldn't touch title, description, or category at all. This keeps `PUT` (full replace) and `PATCH` (partial update) semantically distinct.

## Mini Quiz

```yaml
- question: "Why does findOwnedTask throw NotFoundHttpException instead of an AccessDeniedException when the task belongs to another user?"
  options:
    - "AccessDeniedException does not exist in Symfony"
    - "NotFoundHttpException is faster to construct"
    - "The project's design intentionally makes 'not yours' indistinguishable from 'does not exist', which NotFoundHttpException's 404 mapping achieves"
    - "Doctrine requires NotFoundHttpException for repository lookups"
  answer: 2
  explanation: "ApiExceptionListener maps HttpExceptionInterface exceptions straight to their status code, so throwing NotFoundHttpException here is what produces the 404 that hides whether the task exists at all."

- question: "Why is findOwnedTask() called by show, update, and delete but implemented only once?"
  options:
    - "Symfony requires private helper methods for every controller class"
    - "To guarantee the same ownership rule is enforced identically everywhere it's needed, from a single auditable place"
    - "Because TaskRepository cannot be injected into more than one method"
    - "To avoid a Doctrine caching bug"
  answer: 1
  explanation: "Centralizing the ownership check in one method means every action that needs it enforces exactly the same rule, and any future fix or audit only needs to happen in one place."

- question: "Why doesn't TaskService::update() call $this->entityManager->persist($task)?"
  options:
    - "persist() is deprecated in Doctrine"
    - "The task was already loaded from the repository and is already a managed entity being tracked by the unit of work"
    - "update() does not use Doctrine at all"
    - "flush() automatically calls persist() internally for every entity"
  answer: 1
  explanation: "An entity fetched via the repository is already managed, so Doctrine's unit of work tracks changes to it automatically — flush() alone is enough to write those changes back."
```
