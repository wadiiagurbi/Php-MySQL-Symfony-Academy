---
slug: dependency-injection
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: services-di
chapterTitle: "Chapter 5 — Services & Dependency Injection"
chapterOrder: 5
title: "Dependency Injection"
difficulty: beginner
order: 2
estimatedMinutes: 11
tags: [symfony, services, dependency-injection]
---

## Learning Objective

Understand what **dependency injection (DI)** means, how constructor injection delivers services where they are needed, and why it beats creating objects with `new` inside your classes.

## Explanation

A **dependency** is simply an object your class needs in order to do its work — a controller needs a `TaskSlugger`, a `TaskManager` needs a database repository. **Dependency injection** is the practice of *giving* a class its dependencies from the outside (usually through the **constructor**, the `__construct()` method PHP calls when an object is created) instead of letting the class create them itself with `new`. When you write `public function __construct(private TaskSlugger $slugger)`, you are saying: "whoever creates me, please hand me a `TaskSlugger` — I will not build my own."

Creating dependencies with `new` inside a class causes three concrete problems. First, the class is **coupled** (hard-wired) to one specific implementation — you cannot swap in a different slugger for testing without editing the class. Second, construction details leak everywhere: if `TaskSlugger` later needs its own dependency, every class that news it up must change too. Third, testing becomes painful, because there is no way to substitute a **test double** (a simplified stand-in object used in tests, such as a fake slugger returning fixed values) for the real dependency.

Constructor injection fixes all three: the class only declares *what type* it needs, Symfony's container builds and supplies the real object automatically (**autowiring** — Symfony's ability to match constructor type-hints to registered services without manual configuration, covered in the next lesson), and tests can pass in a fake by simply calling `new TaskController($fakeSlugger)`. The class stays focused on its own job instead of also being a factory for its collaborators.

## Why It Matters

DI is the backbone of every professional Symfony codebase: virtually every controller and service you will ever read receives its collaborators through the constructor. Recognizing the pattern lets you trace "where does this object come from" instantly (answer: the container built it from the type-hints), and writing your own classes this way keeps them testable from day one — a expectation in real code reviews.

## Example

A `TaskController` needs the `TaskSlugger` service from the previous lesson to build slugs when creating tasks. Instead of instantiating the slugger itself, it declares the dependency in its constructor and lets Symfony inject it.

## Code

```php
<?php

namespace App\Controller;

use App\Service\TaskSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskSluggerController extends AbstractController
{
    public function __construct(private TaskSlugger $slugger)
    {
    }

    #[Route('/api/tasks/slug/{title}', name: 'api_tasks_slug', methods: ['GET'])]
    public function preview(string $title): JsonResponse
    {
        return $this->json(['slug' => $this->slugger->slugify($title)]);
    }
}
```

## Code Explanation

- `public function __construct(private TaskSlugger $slugger)` declares the dependency: PHP's **constructor property promotion** (the `private` keyword in the parameter list, which simultaneously creates and assigns the property) stores the injected slugger for later use.
- The controller never calls `new TaskSlugger()` — Symfony reads the `TaskSlugger` type-hint and supplies the shared service instance automatically.
- `$this->slugger->slugify($title)` uses the injected collaborator exactly like a locally created object; from this point on, nothing about DI is visible in the business logic.
- In a test, you could write `new TaskSluggerController($fakeSlugger)` to substitute controlled behavior — impossible if the controller built its own slugger internally.
- If `TaskSlugger` gains its own dependencies later, only the container configuration changes; this controller's code stays untouched.

## Common Mistakes

- Calling `new TaskSlugger()` inside the controller "because it is shorter." It works once, then blocks testing and spreads construction logic across the codebase.
- Injecting the entire service container to "fetch what I need later." This hides the class's real dependencies and defeats the explicitness DI is meant to provide — inject exactly the services you use.
- Using setter or property injection (assigning dependencies after construction) without a reason. Constructor injection guarantees the object is never in a half-built state missing a collaborator.

## Best Practices

- Prefer constructor injection for every dependency — declare what you need as type-hinted constructor parameters and store them as private properties.
- Type-hint interfaces or specific classes deliberately: concrete classes are fine to start with; extract interfaces when you genuinely need multiple implementations.
- Keep constructors free of real work (no queries, no API calls) — they should only receive and store collaborators so objects stay cheap to build in tests.

## Exercise

A `ReportGenerator` service needs a `TaskRepository` (to fetch tasks) and a `Mailer` (to send the report). Write its constructor using property promotion, and explain in one sentence how a test could verify the report logic without a real database or mail server.

## Solution

Write `public function __construct(private TaskRepository $tasks, private Mailer $mailer) {}`. Because both collaborators arrive from outside, a test can pass test doubles (a fake repository returning fixed tasks and a fake mailer recording what it "sent") and assert on the generated report without touching a database or sending real email.

## Mini Quiz

```yaml
- question: "What is dependency injection?"
  options:
    - "A way to inject SQL queries directly into entities"
    - "Supplying a class with the objects it needs from the outside (usually via the constructor) instead of creating them with new"
    - "Automatically generating database migrations"
    - "A Twig feature for including sub-templates"
  answer: 1
  explanation: "DI means collaborators are handed to a class (typically through __construct()) rather than built internally, keeping the class decoupled and testable."
- question: "Why is new TaskSlugger() inside a controller worse than constructor injection?"
  options:
    - "It is a syntax error in Symfony controllers"
    - "It hard-wires the controller to one implementation, spreads construction details around, and blocks substituting test doubles"
    - "It makes the response render as XML instead of JSON"
    - "It prevents the route from being registered"
  answer: 1
  explanation: "Internal instantiation couples the class to a concrete implementation, leaks construction knowledge, and makes tests unable to swap in controlled stand-ins."
- question: "How does a test benefit when a controller receives its slugger through the constructor?"
  options:
    - "Tests run without needing PHP installed"
    - "The test can pass a fake slugger with predictable behavior and verify the controller logic in isolation"
    - "The database is created automatically"
    - "Routes are registered faster"
  answer: 1
  explanation: "Constructor parameters are just arguments, so tests can supply test doubles and check the unit's behavior without real collaborators or infrastructure."
```
