---
slug: repositories-and-crud
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: doctrine-orm
chapterTitle: "Chapter 6 — Doctrine ORM"
chapterOrder: 6
title: "Repositories and CRUD with Doctrine"
difficulty: intermediate
order: 3
estimatedMinutes: 10
tags: [symfony, doctrine, repository, crud]
---

## Learning Objective

By the end of this lesson you will understand what a Doctrine **repository** is and why it's the right place for query logic, and you will be able to perform full CRUD (Create, Read, Update, Delete) operations on entities using the EntityManager and repositories.

## Explanation

A **repository**, in the Doctrine sense, is a dedicated PHP class responsible for *finding* entities — it holds your database query logic, separate from the entity class itself. This isn't just a Doctrine-specific quirk; it's a real, well-known architectural pattern (the "Repository pattern") used across many languages and frameworks: keep "how do I load this kind of object from storage" logic in one clearly-named place, instead of scattering queries throughout controllers and services, and instead of bloating the entity class itself with query methods.

The entity (`Task`) describes what a task *is* — its properties and behavior. The repository (`TaskRepository`) describes how to *find* tasks — "give me all overdue tasks for this user," "give me tasks in this category," and so on. Every Doctrine entity automatically gets a default repository with basic methods like `find($id)`, `findAll()`, and `findBy(['status' => 'done'])` — and you can (and usually should) extend it with your own custom, meaningfully-named query methods, which you'll practice properly in the next lesson on QueryBuilder.

Repositories only *read*. Writing changes — creating, updating, or deleting — goes through the **EntityManager**, which is Doctrine's central service for tracking and persisting changes:

- **Create**: build a new entity object with `new Task(...)`, then call `$entityManager->persist($task)` to tell Doctrine "start tracking this object," and `$entityManager->flush()` to actually write it to the database.
- **Read**: use a repository, e.g. `$taskRepository->find($id)` or `$taskRepository->findBy(['status' => 'done'])`.
- **Update**: fetch an existing entity, call its setter methods (e.g. `$task->setStatus(...)`), and call `$entityManager->flush()`. You do **not** need to call `persist()` again — Doctrine already tracks entities it previously fetched or persisted, in a mechanism called the Unit of Work.
- **Delete**: fetch the entity, call `$entityManager->remove($task)`, then `$entityManager->flush()`.

The crucial detail beginners miss: nothing is actually written to the database until `flush()` is called. `persist()` and setter calls only stage changes in memory; `flush()` is what generates and executes the real SQL (`INSERT`, `UPDATE`, `DELETE`) inside a transaction.

## Why It Matters

Separating query logic (repositories) from write logic (EntityManager) and from data structure (entities) keeps a Symfony codebase navigable as it grows. When a new developer needs to find where "tasks overdue by more than 3 days" is computed, they know to look in `TaskRepository` — not to search the entire codebase for scattered SQL. This convention is so standard in Symfony projects that controllers, services, and tests all assume repositories are the entry point for reads, and the EntityManager is the entry point for writes. Getting this pattern right also avoids a common real bug: staging changes on an entity and forgetting to `flush()`, which silently does nothing and is a frequent source of "why didn't my change save?" debugging sessions.

## Example

To create a task for a user, read it back, update its status, and eventually delete it, a service or controller would use both the repository (for reading) and the EntityManager (for writing):

```php
// Create
$task = new Task($owner, 'Write chapter 6 lesson');
$entityManager->persist($task);
$entityManager->flush();

// Read
$task = $taskRepository->find($task->getId());

// Update
$task->setStatus(TaskStatus::DONE);
$entityManager->flush(); // no persist() needed again

// Delete
$entityManager->remove($task);
$entityManager->flush();
```

## Code

```php
<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Entity\TaskStatus;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class TaskCrudController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_task_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[CurrentUser] $user,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $task = new Task($user, $data['title']);

        // Stage the new entity for insertion; nothing is written to the
        // database yet — flush() is what actually runs the SQL.
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['id' => $task->getId()], 201);
    }

    #[Route('/api/tasks/{id}/complete', name: 'api_task_complete', methods: ['PATCH'])]
    public function complete(int $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (null === $task) {
            return $this->json(['error' => ['message' => 'Task not found.']], 404);
        }

        $task->setStatus(TaskStatus::DONE);
        // No persist() call needed: Doctrine already tracks $task because
        // it was loaded through the EntityManager via the repository.
        $entityManager->flush();

        return $this->json(['id' => $task->getId(), 'status' => $task->getStatus()]);
    }

    #[Route('/api/tasks/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(int $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (null === $task) {
            return $this->json(['error' => ['message' => 'Task not found.']], 404);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json(null, 204);
    }
}
```

## Code Explanation

- `create()` builds a brand-new `Task` object in memory, calls `persist()` to hand it to Doctrine's Unit of Work (marking it for insertion), then `flush()` to actually run the `INSERT` statement. After `flush()`, `$task->getId()` is populated with the database-generated ID.
- `complete()` demonstrates that fetched entities are automatically tracked: because `$task` came from `$taskRepository->find($id)`, calling a setter and then `flush()` is enough — Doctrine compares the object's current state to what it originally loaded and generates the correct `UPDATE` statement with only the changed columns.
- `delete()` calls `remove()` to mark the entity for deletion, then `flush()` to actually execute the `DELETE` statement. Like `persist()`, `remove()` alone does not touch the database.
- Every action always checks for `null` after `find()`, since a missing ID returns `null` rather than throwing — a controller must handle that case explicitly (404 here).

## Common Mistakes

- **Forgetting to call `flush()`.** This is the single most common Doctrine mistake: `persist()`, setter calls, and `remove()` only stage changes in memory. Without `flush()`, nothing reaches the database and no error is raised — the request just silently does nothing to the database.
- **Calling `persist()` on an already-fetched entity.** It's harmless but unnecessary — Doctrine already tracks entities it loaded via a repository or previously persisted, so calling `persist()` again on them is redundant (though not an error).
- **Calling `flush()` inside a tight loop.** Each `flush()` runs a database round-trip; flushing once after a whole batch of changes is much more efficient than flushing after each individual change.
- **Assuming `remove()` deletes immediately.** Like `persist()`, it stages the deletion — the row still exists until `flush()` runs.

## Best Practices

- Treat repositories as read-only entry points and the EntityManager as the write entry point — don't add write methods to a repository.
- Batch related changes and call `flush()` once at a natural boundary (end of a controller action, end of a use case), not after every single property change.
- Always null-check the result of `find()` (and other single-result fetches) before using it.
- Keep entity construction responsible for required, valid initial state (as `Task`'s constructor does by requiring `$owner` and `$title`), so a `persist()`-ed entity is never in an incomplete state.

## Exercise

Write a Symfony controller action that duplicates an existing task: given a task ID in the route, load it, create a brand-new `Task` for the same owner with the title `"Copy of " . $originalTitle`, save it, and return the new task's ID in a JSON response. Handle the case where the original task does not exist.

## Solution

```php
#[Route('/api/tasks/{id}/duplicate', name: 'api_task_duplicate', methods: ['POST'])]
public function duplicate(int $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): JsonResponse
{
    $original = $taskRepository->find($id);

    if (null === $original) {
        return $this->json(['error' => ['message' => 'Task not found.']], 404);
    }

    $copy = new Task($original->getOwner(), 'Copy of '.$original->getTitle());
    $copy->setDescription($original->getDescription());
    $copy->setPriority($original->getPriority());

    $entityManager->persist($copy);
    $entityManager->flush();

    return $this->json(['id' => $copy->getId()], 201);
}
```

This fetches the original task through the repository (read), constructs a brand-new entity in memory, copies over relevant fields, stages it with `persist()`, and writes it with a single `flush()` call — matching the create pattern shown in the lesson.

## Mini Quiz

```yaml
- question: "What is the recommended role of a Doctrine repository?"
  options:
    - "It holds query/finding logic for an entity, separate from the entity's own class and separate from write operations"
    - "It is where you put setters and getters for an entity"
    - "It replaces the EntityManager for all create, update, and delete operations"
    - "It stores raw SQL migration files"
  answer: 0
  explanation: "Repositories are the recommended place for query (read) logic — the Repository pattern keeps 'how do I find this' logic separate from the entity class and from write operations, which go through the EntityManager."
- question: "After calling $task->setStatus(...) on an entity you fetched via a repository, what else is required to save the change?"
  options:
    - "Nothing else is needed"
    - "Calling $entityManager->persist($task) followed by flush()"
    - "Calling $entityManager->flush() — persist() is not needed again for an already-tracked entity"
    - "Re-fetching the entity after the change"
  answer: 2
  explanation: "Entities loaded via the EntityManager (including through a repository) are already tracked; only flush() is needed to generate and run the UPDATE statement."
- question: "What happens if you call $entityManager->remove($task) but never call flush()?"
  options:
    - "The task is deleted immediately"
    - "Nothing happens to the database; the deletion is only staged in memory"
    - "It throws an exception"
    - "The task is soft-deleted automatically"
  answer: 1
  explanation: "remove(), like persist(), only stages the change. No SQL is executed and the database is unaffected until flush() runs."
```
