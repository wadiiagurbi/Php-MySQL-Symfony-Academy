---
slug: service-layer-and-repository-queries
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "The Service Layer and Repository Queries"
difficulty: advanced
order: 10
estimatedMinutes: 13
tags: [final-project, task-manager, service-layer, architecture]
---

## Learning Objective

By the end of this lesson, you will be able to explain exactly what `TaskService` does that a controller shouldn't, trace the "find-or-create category" logic it runs on every save, and see how it turns a single repository query into the `/api/stats` endpoint's dashboard numbers.

## Explanation

Every write operation in this project — create, update, delete — and the stats aggregation flow through one class: `TaskService`. Its constructor tells you exactly what it depends on and nothing more:

```php
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}
}
```

Two repositories (one per entity it touches) and the entity manager (needed for `persist`/`flush`, since a service — unlike a repository — is allowed to *write*, not just read). Nothing about HTTP, JSON, or request objects appears anywhere in this class. That's deliberate: `TaskService` has no idea it's being called from an API controller. It could just as easily be called from a console command or a test, which is exactly what makes it independently testable.

Look at `create` and `update` side by side:

```php
public function create(User $owner, TaskRequest $request): Task
{
    $task = new Task($owner, $request->title);
    $this->applyRequest($task, $request, $owner);

    $this->entityManager->persist($task);
    $this->entityManager->flush();

    return $task;
}

public function update(Task $task, TaskRequest $request): Task
{
    $task->setTitle($request->title);
    $this->applyRequest($task, $request, $task->getOwner());

    $this->entityManager->flush();

    return $task;
}
```

Both funnel through the same private `applyRequest` method for everything except the title and the initial construction — description, status, priority, due date, and category are set identically whether you're creating or editing a task. That's not an accident; it's the whole point of extracting a private helper: the "how do I turn a `TaskRequest` into task field values" logic exists in exactly one place, so a bug fix or a new field only needs to change once.

The most interesting part of `applyRequest` is what it does with `category`:

```php
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
```

The API never exposes a "create category" endpoint the frontend has to call first. A client just sends a category *name* as a plain string on the task itself (`"category": "Backend"`), and the service quietly finds an existing category with that name for this owner, or creates one on the fly if it doesn't exist yet. This is a small but genuine example of business logic that belongs nowhere near a controller: it's a decision ("reuse or create?") based on querying the database, not a mechanical translation of the request.

## Why It Matters

This is what "the service layer" means in practice, beyond the abstract definition: it's the one place in the codebase where "what should happen" is decided, independent of "how did the request arrive" and "how is it stored." Controllers change if you add a new API version or switch to GraphQL; repositories change if you switch database vendors or optimize a query; `TaskService` shouldn't need to change for either reason — only when the actual business rules change (e.g., "categories should now be shared across all users" would be a `TaskService`/`TaskCategoryRepository` change, not a controller change).

## Example

Two requests that both end up creating a `TaskCategory` row, from completely different entry points, without either one needing to know a category table exists as a separate concept:

```
POST /api/tasks {"title": "Fix login bug", "category": "Backend"}
POST /api/tasks {"title": "Design new logo", "category": "Design"}
```

If a third request later reuses `"category": "Backend"`, `findOneForOwnerAndName` finds the existing row instead of creating a duplicate — the category list stays deduplicated per user without the frontend ever managing category IDs directly.

## Code

```php
<?php
// Adapted from src/Service/TaskService.php and src/Controller/Api/StatsController.php

public function statsByStatus(User $owner): array
{
    $counts = $this->taskRepository->countByStatusForOwner($owner);

    $stats = [];
    foreach (TaskStatus::cases() as $status) {
        $stats[$status->value] = $counts[$status->value] ?? 0;
    }

    return $stats;
}
```

## Code Explanation

`TaskRepository::countByStatusForOwner` runs one grouped query (`SELECT status, COUNT(id) ... GROUP BY status`) and returns only the statuses that actually have at least one row — if a user has zero `IN_PROGRESS` tasks, that key simply won't be in the array Doctrine returns. `statsByStatus` then loops over `TaskStatus::cases()` (all three enum cases, always) and fills in `0` for anything missing with `$counts[$status->value] ?? 0`. This guarantees `GET /api/stats` always returns all three keys — `TODO`, `IN_PROGRESS`, `DONE` — even for a brand-new user with no tasks at all, which is far friendlier for a dashboard chart than a frontend having to guess which keys might be absent.

## Common Mistakes

- Putting the "find or create category" logic inside the controller, which would mean duplicating it between `create` and `update` actions instead of sharing one `applyRequest` method.
- Querying `countByStatusForOwner` and returning it directly from the controller, which would silently omit statuses with zero tasks instead of reporting them as `0`.
- Giving `TaskService` a dependency on `Request` or any HTTP-layer class "just in case" — the moment a service needs `Request`, business logic and transport concerns are mixing again.
- Forgetting to `persist()` the newly-created `TaskCategory` before `flush()` — Doctrine won't insert an entity that was never registered with the entity manager, even if it's attached to another entity that does get flushed... though in this case, `persist()` is called explicitly, which is exactly why it's not skipped.

## Best Practices

- Keep one private helper for logic shared between "create" and "update" paths rather than letting them drift out of sync over time.
- Let a service depend only on repositories and other services it genuinely needs — the constructor signature should be an honest, minimal summary of what the class does.
- Design aggregate/stats methods (like `statsByStatus`) to return a complete, predictable shape (all enum values present) rather than whatever the raw query happened to produce.

## Exercise

Add a `duplicate(Task $task): Task` method to `TaskService` that creates a copy of an existing task (same title with " (copy)" appended, same description/priority/category, status reset to `TODO`, no due date). What would the method body look like, and would it need any new repository method?

## Solution

```php
public function duplicate(Task $task): Task
{
    $copy = new Task($task->getOwner(), $task->getTitle().' (copy)');
    $copy->setDescription($task->getDescription());
    $copy->setPriority($task->getPriority());
    $copy->setCategory($task->getCategory());
    // status defaults to TODO and dueDate defaults to null in the Task constructor already.

    $this->entityManager->persist($copy);
    $this->entityManager->flush();

    return $copy;
}
```

No new repository method is needed — this only reuses data already loaded on the existing `$task` entity and reuses the same `Task` constructor and setters every other method in this class already uses. This is a good sign: a new feature that fits naturally using only the existing building blocks usually means the service's design was right in the first place.

## Mini Quiz

```yaml
- question: "Why does TaskService depend on EntityManagerInterface directly, while TaskRepository methods never call persist() or flush()?"
  options:
    - "It's an arbitrary style choice with no real reason"
    - "Repositories are conventionally responsible for querying/reading; deciding when to write (persist/flush) is a business-logic decision that belongs in the service"
    - "EntityManagerInterface cannot be injected into a repository"
    - "Doctrine requires all writes to happen in a Controller"
  answer: 1
  explanation: "Keeping persist()/flush() calls in the service (not the repository) keeps the repository focused purely on building and running queries, while the service decides when a change should actually be committed."
- question: "What does applyRequest's category handling do when a task's category name already exists for that owner?"
  options:
    - "It throws an error because categories must be unique globally"
    - "It creates a duplicate category with the same name"
    - "It reuses the existing TaskCategory instead of creating a new one"
    - "It ignores the category field entirely"
  answer: 2
  explanation: "findOneForOwnerAndName looks up an existing category by name for that owner first, and only creates a new TaskCategory if none was found — preventing duplicate categories with the same name."
- question: "Why does statsByStatus loop over TaskStatus::cases() instead of just returning the repository's raw grouped-count result?"
  options:
    - "cases() is required by Doctrine for enum columns"
    - "To guarantee every status key (including ones with zero tasks) is present in the response"
    - "The raw result is not valid JSON"
    - "It improves query performance"
  answer: 1
  explanation: "The raw GROUP BY query only returns statuses that have at least one task; looping over all enum cases and defaulting missing ones to 0 makes the response shape predictable regardless of a user's actual data."
```
