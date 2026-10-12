---
slug: real-world-architecture
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: architecture
chapterTitle: "Chapter 15 — Real-World Architecture"
chapterOrder: 15
title: "Real-World Symfony Architecture"
difficulty: intermediate
order: 1
estimatedMinutes: 12
tags: [symfony, architecture, design, layers]
---

## Learning Objective

Understand how professional Symfony apps organize code into **layers** (HTTP, application, domain, infrastructure) so business rules stay independent of frameworks and databases.

## Explanation

Small tutorials put everything in controllers, but real applications separate *what the app does* from *how it is delivered*. **Layered architecture** divides code by responsibility. The **HTTP layer** (controllers, Twig templates, API responses) translates between the outside world and the app — it knows about requests and status codes but contains no business rules. The **application layer** (services like `TaskService`) orchestrates use cases: "create a task" means validate input, build the entity, persist it, and send notifications, in that order. The **domain layer** (entities plus domain rules) holds the business truth that would survive even if you replaced Symfony — "a task needs a non-empty title" is true regardless of framework. The **infrastructure layer** (Doctrine repositories, mailers, file storage) talks to the outside world: databases, SMTP, filesystems.

Dependencies point strictly inward: controllers depend on application services, services depend on domain objects and **abstractions** (interfaces like `TaskRepositoryInterface` describing *what* persistence offers without naming Doctrine), and infrastructure implements those interfaces. This is the **Dependency Inversion Principle** (the "D" in **SOLID** — five classic object-design principles; this one says high-level code should depend on interfaces, not concrete tools): the service never imports Doctrine classes, so swapping storage (or faking it in tests) changes one class, not fifty callers.

The payoff is testability and longevity. Application services accept plain inputs and collaborators via constructor injection, so unit tests cover entire use cases with in-memory fakes — no database, no HTTP. Framework upgrades touch the outer layers while the domain, where the company's real rules live, barely notices.

## Why It Matters

"Where does this code go?" at scale is answered by layers, not gut feeling — and code review on real teams enforces the boundaries (no SQL in controllers, no HTTP in the domain). Architects also credit this separation for multi-year survival: Symfony and Doctrine versions come and go, but a well-isolated domain migrates with a fraction of the pain. Explaining these layers convincingly marks the jump from tutorial coder to professional developer.

## Example

Creating a task currently lives as 60 lines inside a controller (validation, slug, persistence, email). Restructured, a `TaskService::create()` application method orchestrates the same steps behind a clean API the controller calls in three lines — and a console command could reuse it unchanged.

## Code

```php
<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;

class TaskService
{
    public function __construct(
        private TaskRepository $tasks,
        private TaskSlugger $slugger,
    ) {
    }

    public function createFor(User $owner, string $title): Task
    {
        $title = trim($title);

        if ('' === $title) {
            throw new \InvalidArgumentException('Task title cannot be empty.');
        }

        $task = new Task($owner, $title, $this->slugger->slugify($title));
        $this->tasks->save($task);

        return $task;
    }
}
```

## Code Explanation

- `App\Service\TaskService` is the application layer: it owns the *use case* "create a task" without knowing anything about HTTP requests or responses.
- Constructor injection supplies the repository (infrastructure, hidden behind a class the service treats as a contract) and the slugger (domain helper) — both swappable in tests.
- Validation (`empty title` rejection) lives with the rule it protects, raising a plain `\InvalidArgumentException` the HTTP layer translates into a 400/422 response.
- The controller's job shrinks to reading input, calling `createFor()`, and rendering — while a future console command reuses the identical method.
- Persistence goes through the repository rather than the raw entity manager, keeping Doctrine details out of the orchestration logic.

## Common Mistakes

- Letting controllers orchestrate multi-step use cases directly (validate → build → save → notify inline). The second entry point (API, command, queue worker) forces copy-paste of the whole sequence.
- Leaking infrastructure into the domain: calling the entity manager or mailer from inside entities, making business rules untestable without a database and mail server.
- Creating layers as empty pass-throughs (controller → service → repository each adding nothing). Layers must own distinct decisions, or they are ceremony without benefit.

## Best Practices

- Enforce inward dependencies: HTTP depends on application, application on domain/abstractions, infrastructure implements the edges — never the reverse.
- Put each use case behind one application-service method with plain inputs/outputs, so controllers, commands, and workers share audited behavior.
- Depend on repository interfaces in services where the codebase warrants it, letting tests substitute in-memory fakes for full use-case coverage without a database.

## Exercise

A "mark task done" feature must (1) verify ownership, (2) set the flag, (3) persist, (4) log the event. Assign each step to its layer (HTTP/application/domain/infrastructure) and name the single application-service method signature covering the use case.

## Solution

HTTP: route plus controller translating the request and returning the response (ownership failures become 403). Application: `TaskService::markDone(User $user, Task $task): void` orchestrating all four steps. Domain: the `Task::markDone()` state change plus the ownership rule. Infrastructure: repository persistence and the Monolog logger. One service method owns the sequence; every entry point reuses it.

## Mini Quiz

```yaml
- question: "What are the four layers of a layered Symfony architecture?"
  options:
    - "Routes, templates, migrations, and tests"
    - "HTTP (delivery), application (use cases), domain (business rules), infrastructure (databases, mail, storage)"
    - "Dev, test, staging, and production"
    - "Models, views, controllers, and components"
  answer: 1
  explanation: "Each layer owns one concern: delivery translates HTTP, application orchestrates use cases, domain holds business truth, infrastructure talks to external systems."
- question: "What does the Dependency Inversion Principle require in this architecture?"
  options:
    - "Services must never use constructor injection"
    - "High-level code depends on abstractions (interfaces), with infrastructure implementing them — never the reverse"
    - "Controllers must directly instantiate Doctrine repositories"
    - "Entities must send emails themselves"
  answer: 1
  explanation: "Depending on interfaces keeps application code independent of concrete tools, so storage or mail implementations can change without rewriting use cases."
- question: "Why move a 60-line controller use case into an application service?"
  options:
    - "Services execute faster than controllers"
    - "One tested, reusable method serves every entry point (web, API, console, workers) instead of trapping logic in a single HTTP action"
    - "Controllers cannot contain more than 10 lines by law"
    - "Twig requires all logic to live in services"
  answer: 1
  explanation: "Use cases outlive any single delivery mechanism; the service makes the behavior callable and unit-testable from anywhere."
```
