---
slug: creating-tasks-api
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Creating Tasks (API)"
difficulty: advanced
order: 6
estimatedMinutes: 12
tags: [final-project, task-manager, api, validation]
---

## Learning Objective

By the end of this lesson, you will be able to trace the full request lifecycle of `POST /api/tasks`, from JSON body to validated DTO to persisted entity to JSON response, and explain why each layer (controller, DTO, service, read-model) exists instead of doing everything in the controller.

## Explanation

Creating a task touches four layers, each with one job:

1. **The controller** (`TaskController::create`) translates HTTP into typed data and back. It doesn't know how a task is validated or what a "category" resolves to.
2. **The DTO** (`TaskRequest`) defines and validates the exact shape of the JSON body — `title`, `description`, `status`, `priority`, `dueDate`, `category` — using Symfony's Validator constraints.
3. **The service** (`TaskService::create`) contains the actual business logic: constructing the `Task`, applying the request's fields, resolving or creating a category, persisting.
4. **The read-model** (`TaskView`) decides exactly what JSON shape comes back, decoupled from the entity's internal structure.

```php
#[Route('/api/tasks', name: 'api_tasks_create', methods: ['POST'])]
public function create(Request $request, RequestValidator $requestValidator, TaskService $taskService): JsonResponse
{
    $data = $requestValidator->fromJson($request, TaskRequest::class);
    $task = $taskService->create($this->currentUser(), $data);

    return $this->json(TaskView::fromEntity($task), 201);
}
```

Three lines of actual logic. `RequestValidator::fromJson` (used across the whole app, including registration) deserializes the request body directly into a `TaskRequest` instance and validates it in one call — if any `#[Assert\...]` constraint fails, it throws `ValidationFailedException`, which `ApiExceptionListener` turns into a 422 before this method's body even gets to line two. `$this->currentUser()` retrieves the authenticated `User` from Symfony security (`$this->getUser()`), guaranteeing every created task is attached to whoever is making the request — there's no code path where a client can specify someone else's ID as the owner.

`TaskService::create` does the actual work:

```php
public function create(User $owner, TaskRequest $request): Task
{
    $task = new Task($owner, $request->title);
    $this->applyRequest($task, $request, $owner);

    $this->entityManager->persist($task);
    $this->entityManager->flush();

    return $task;
}
```

`applyRequest` (shared with `update`, covered in the next lesson) sets description, converts the `status`/`priority` strings into their enum cases via `TaskStatus::from(...)`, parses the due date, and resolves the category name into a `TaskCategory` entity — creating one on the fly if it doesn't exist yet for this owner.

## Why It Matters

This layering is what makes the same logic reusable. `TaskService::create` and `TaskService::update` share `applyRequest` instead of duplicating category resolution and enum conversion twice. `TaskView` is used identically by `create`, `update`, `show`, and `list`, so the JSON shape of a task is guaranteed to be consistent everywhere it appears — change `TaskView` once, and every endpoint's response changes together. A controller that inlined all of this (raw `$request->toArray()`, manual validation, direct `$entityManager` calls, hand-built response arrays) would work for one endpoint but be impossible to reuse safely for the next.

## Example

A request/response pair for creating a task with a brand-new category:

```
POST /api/tasks
Authorization: Bearer <token>
{"title": "Write the migration", "status": "TODO", "priority": "HIGH", "category": "Backend"}

201 Created
{"id": 7, "title": "Write the migration", "description": null, "status": "TODO",
 "priority": "HIGH", "dueDate": null, "category": "Backend",
 "createdAt": "2026-09-18T10:00:00+00:00", "updatedAt": "2026-09-18T10:00:00+00:00"}
```

No `"Backend"` category existed before this call — `TaskService`'s category-resolution logic created it transparently, scoped to this user.

## Code

```php
<?php
// Adapted from src/Service/TaskService.php — the category resolution
// and enum conversion logic shared by create() and update().

private function applyRequest(Task $task, TaskRequest $request, User $owner): void
{
    $task->setDescription($request->description);
    $task->setStatus(TaskStatus::from($request->status));
    $task->setPriority(TaskPriority::from($request->priority));
    $task->setDueDate(null !== $request->dueDate ? new \DateTimeImmutable($request->dueDate) : null);

    if (null === $request->category || '' === trim($request->category)) {
        $task->setCategory(null);

        return;
    }

    $category = $this->categoryRepository->findOneForOwnerAndName($owner, $request->category);
    if (null === $category) {
        $category = new TaskCategory($owner, $request->category);
        $this->entityManager->persist($category);
    }

    $task->setCategory($category);
}
```

## Code Explanation

`TaskStatus::from($request->status)` converts the validated string (already constrained to `'TODO'|'IN_PROGRESS'|'DONE'` by `TaskRequest`'s `Assert\Choice`) into the actual enum case — `from()` throws if the value isn't a valid case, but by this point validation has already guaranteed it is, so this call is safe. The category block is the more interesting piece: an empty or missing `category` string clears any existing category (important for `update`, where a user might remove a category from a task). Otherwise, it looks for an existing category with that exact name *for this owner* — `findOneForOwnerAndName` always scopes by owner, matching the per-user uniqueness designed earlier — and creates one transparently if none exists. This "find or create" pattern means the client-facing API never needs a separate "create category" step; categories are an implicit side effect of typing a name.

## Common Mistakes

- Trusting a client-supplied `ownerId` field instead of always using `$this->getUser()` for the owner — this project never accepts an owner from the request body, precisely to prevent one user from creating tasks "as" another.
- Calling `TaskStatus::from()` on unvalidated input — if `TaskRequest`'s `Assert\Choice` constraint were removed, an invalid status string would throw an uncaught `ValueError` instead of a clean 422.
- Forgetting to persist the newly created `TaskCategory` before persisting the `Task` that references it — Doctrine's unit of work generally handles ordering via cascade, but explicit `persist()` calls (as shown here) make the intent unambiguous.

## Best Practices

- Keep controllers thin: decode/validate in, DTO/entity through a service, response DTO out — no direct business logic in the controller method body.
- Reuse shared logic (like `applyRequest`) between "create" and "update" flows instead of duplicating field-mapping code, since a bug fix or new field then only needs to change in one place.
- Let a dedicated read-model (`TaskView`) — not the entity — decide the wire format, so internal changes to the entity don't automatically become breaking API changes.

## Exercise

Add support for an optional `estimatedMinutes` integer field on task creation. Walk through every layer you'd touch: DTO, entity, migration, service, and read-model.

## Solution

1. **Entity**: add `#[ORM\Column(nullable: true)] private ?int $estimatedMinutes = null;` to `Task`, plus a getter/setter (the setter calling `touch()` like the others).
2. **Migration**: run `doctrine:migrations:diff` to generate the `ALTER TABLE task ADD estimated_minutes INTEGER DEFAULT NULL` migration, then `doctrine:migrations:migrate`.
3. **DTO**: add `public ?int $estimatedMinutes = null;` to `TaskRequest`, optionally with `#[Assert\PositiveOrZero]`.
4. **Service**: in `applyRequest`, add `$task->setEstimatedMinutes($request->estimatedMinutes);`.
5. **Read-model**: add `'estimatedMinutes' => $task->getEstimatedMinutes(),` to `TaskView::jsonSerialize()`.

## Mini Quiz

```yaml
- question: "Why does TaskController::create call $this->currentUser() rather than reading an ownerId from the request body?"
  options:
    - "TaskRequest does not support extra fields"
    - "To guarantee a task is always owned by whoever is actually authenticated, never a client-supplied user ID"
    - "Because Doctrine forbids setting foreign keys from controllers"
    - "Because currentUser() is faster than parsing JSON"
  answer: 1
  explanation: "Always deriving ownership from the authenticated session (never trusting client input) prevents one user from creating tasks attributed to another user."

- question: "Why is category resolution logic shared between TaskService::create and TaskService::update via applyRequest, instead of duplicated?"
  options:
    - "PHP requires private methods to be reused across public methods"
    - "So a change to how categories are resolved only needs to happen in one place, and both flows stay consistent"
    - "Because Doctrine caches method calls"
    - "To reduce the number of files in the project"
  answer: 1
  explanation: "Sharing applyRequest means create and update always apply the same field-mapping and category-resolution rules, avoiding subtle drift between the two code paths."

- question: "What happens in applyRequest when TaskRequest::$category is an empty string?"
  options:
    - "It throws a validation error"
    - "It creates a category named empty string"
    - "The task's category is set to null, clearing any existing category"
    - "The previous category is kept unchanged"
  answer: 2
  explanation: "The method explicitly checks for null or a blank (trimmed) string and calls setCategory(null) in that case, so submitting an empty category clears it rather than leaving the old value or erroring."
```
