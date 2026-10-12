---
slug: phpunit-basics-and-unit-tests
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: testing
chapterTitle: "Chapter 14 — Testing"
chapterOrder: 14
title: "PHPUnit Basics and Unit Tests"
difficulty: advanced
order: 1
estimatedMinutes: 13
tags: [symfony, testing, phpunit, unit-tests]
---

## Learning Objective

Understand what automated testing is and why it matters, what specifically makes a test a "unit test," and how to write one using PHPUnit against a plain PHP/Symfony class.

## Explanation

Throughout this course, you have checked whether your code works by running it manually — visiting a URL in the browser, calling an endpoint with Postman, or eyeballing a response. This works, but it does not scale: as an application grows to dozens of routes, services, and edge cases, manually re-checking everything after every change becomes slow, and things you forget to re-check are exactly where bugs hide. **Automated testing** is writing code whose sole job is to run your application's code and assert that it behaves correctly, so a computer re-checks it for you — in seconds, every time, without forgetting a single case.

**PHPUnit** is the standard testing framework for PHP, and it is what Symfony itself is built and tested with. A PHPUnit test is a plain PHP method, inside a class extending `PHPUnit\Framework\TestCase`, whose name starts with `test` (or is annotated to be treated as one). Inside that method, you call the code you want to test, then use **assertions** — methods like `assertSame()`, `assertTrue()`, `assertCount()` — to state what you expect to be true. If an assertion fails, PHPUnit reports exactly which one, with the expected and actual values, and marks the test as failed; the whole test suite runs with a single command (`php bin/phpunit`) and reports a pass/fail summary for everything at once.

**What makes a test specifically a "unit test."** The word "unit" refers to testing one small piece of code — typically a single class or method — in complete isolation from everything around it: no real HTTP request, no real database, no real filesystem, no other services except perhaps simple fakes/mocks you control. A unit test for `TaskService::update()` would construct a `TaskService` directly in the test, pass it a `Task` object you built in code, call `update()`, and assert on the result — no Symfony kernel booted, no real Doctrine connection involved. This project's real `CourseContentLoaderTest` is a clean example: it instantiates `CourseContentLoader` directly (`new CourseContentLoader(...)`), points it at a small fixture directory on disk, and asserts on what comes back — no HTTP layer, no application container, nothing beyond the one class under test and a folder of sample files.

Why isolate like this? Because a unit test's entire purpose is to answer one narrow question fast and unambiguously: "does this specific piece of logic behave correctly?" If a unit test also spins up a real database and a real HTTP kernel, a failure could mean the logic is wrong, *or* the database connection is misconfigured, *or* routing broke, *or* the network is down — you no longer know which, and the test becomes slow and flaky (sometimes passing, sometimes failing, for reasons unrelated to your code). Isolating dependencies means a failing unit test almost always points to exactly one thing being wrong.

The next lesson in this chapter covers **functional tests**, which deliberately *do* involve the full application (a real HTTP request through a real kernel) — the two types of test answer different questions and complement each other rather than competing.

## Why It Matters

Automated tests are what let a professional team change existing code with confidence instead of fear. Without tests, every change to a shared class like `TaskService` risks silently breaking behavior somewhere else in the application that nobody thought to manually re-check; with a solid unit test suite, running `php bin/phpunit` after a change tells you within seconds whether you broke anything the tests cover. Unit tests specifically are valuable because they are fast (no database, no HTTP overhead) and precise (a failure points at one class), which is exactly why they are run constantly during development and in CI on every commit, long before slower functional or end-to-end tests run.

## Example

Consider testing `TaskService::statsByStatus()`, a method that counts tasks by status for a given owner. A unit test does **not** need a real database: you can construct a fake `TaskRepository` (a "test double") that returns a hardcoded array when `countByStatusForOwner()` is called, inject that fake into `TaskService`, call `statsByStatus()`, and assert the returned counts are correct. The test verifies `TaskService`'s own logic (filling in zero for statuses with no tasks) without caring whether Doctrine or MySQL work correctly — those are separate concerns, tested elsewhere.

## Code

```php
<?php

namespace App\Tests\Unit\Service;

use App\Entity\TaskStatus;
use App\Entity\User;
use App\Repository\TaskCategoryRepository;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TaskServiceStatsTest extends TestCase
{
    public function testStatsByStatusFillsInZeroForStatusesWithNoTasks(): void
    {
        $owner = new User('owner@example.com', 'Owner');

        $taskRepository = $this->createMock(TaskRepository::class);
        $taskRepository->method('countByStatusForOwner')
            ->with($owner)
            ->willReturn(['TODO' => 3, 'DONE' => 2]);

        $categoryRepository = $this->createMock(TaskCategoryRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new TaskService($taskRepository, $categoryRepository, $entityManager);

        $stats = $service->statsByStatus($owner);

        self::assertSame(3, $stats[TaskStatus::TODO->value]);
        self::assertSame(2, $stats[TaskStatus::DONE->value]);
        self::assertSame(0, $stats[TaskStatus::IN_PROGRESS->value]);
    }
}
```

## Code Explanation

- `class TaskServiceStatsTest extends TestCase` — extending PHPUnit's `TestCase` gives this class assertion methods (`self::assertSame(...)`) and PHPUnit's test-running machinery; no Symfony-specific base class is needed, because this is a pure unit test.
- `$this->createMock(TaskRepository::class)` creates a **test double** — a fake object that looks like a `TaskRepository` to PHP's type system but does nothing unless you configure it, letting the test avoid a real database entirely.
- `->method('countByStatusForOwner')->with($owner)->willReturn([...])` configures the fake: "when `countByStatusForOwner($owner)` is called, return this hardcoded array" — this is the entire simulated behavior of the repository for this test.
- `new TaskService($taskRepository, $categoryRepository, $entityManager)` constructs the real class under test directly, injecting the fakes through its constructor exactly the way Symfony's container would inject the real services in production — the difference is this test controls exactly what each dependency returns.
- `self::assertSame(3, $stats[TaskStatus::TODO->value]);` asserts the exact expected value; `assertSame` checks both value and type (strict `===`), which is generally preferred over the looser `assertEquals` unless you specifically need loose comparison.
- The `IN_PROGRESS` assertion (`assertSame(0, ...)`) verifies the specific business rule that a status with zero tasks should still appear in the result with a count of `0`, rather than being missing from the array — exactly the kind of edge case a unit test is well suited to pin down.

## Common Mistakes

- Booting a full Symfony kernel or connecting to a real database inside a class meant to be a unit test — at that point it has become a functional test in disguise, losing the speed and isolation that make unit tests valuable.
- Testing private implementation details instead of public behavior (e.g., asserting on an internal property directly) — tests should verify *what a method returns or does*, not *how* it does it internally, so refactoring the internals without changing behavior does not break the test.
- Writing one giant test method that checks five unrelated things — when it fails, it is unclear which of the five broke. Prefer one focused assertion-group per test method, named for the specific behavior being verified.
- Forgetting to test the "empty" or "zero" edge cases (like the `IN_PROGRESS => 0` case above) and only testing the "happy path" with obviously present data.

## Best Practices

- Name test methods descriptively as full sentences of what is being verified (`testStatsByStatusFillsInZeroForStatusesWithNoTasks`), so a failing test's name alone tells you what broke.
- Keep unit tests fast by mocking or faking anything that would otherwise touch a database, filesystem, or network — a unit test suite with hundreds of tests should still run in a few seconds.
- Prefer `assertSame` over `assertEquals` by default — strict comparison catches type mismatches (like `"3"` vs `3`) that loose comparison would silently accept.
- Test one class's behavior per unit test file, mirroring the class under test's name (`TaskService` → `TaskServiceTest`), so tests stay easy to locate.

## Exercise

Write a unit test for a hypothetical pure method `TaskPriorityCalculator::isOverdueAndHighPriority(Task $task): bool`, which returns `true` only if a task's due date is in the past AND its priority is `HIGH`. Assume `Task` is a plain PHP object you can construct directly in the test (no database needed). Write at least one test asserting `true` and one asserting `false`.

## Solution

```php
<?php

namespace App\Tests\Unit\Service;

use App\Entity\Task;
use App\Entity\TaskPriority;
use App\Entity\User;
use App\Service\TaskPriorityCalculator;
use PHPUnit\Framework\TestCase;

class TaskPriorityCalculatorTest extends TestCase
{
    public function testReturnsTrueForOverdueHighPriorityTask(): void
    {
        $task = new Task(new User('owner@example.com', 'Owner'), 'Overdue task');
        $task->setPriority(TaskPriority::HIGH);
        $task->setDueDate(new \DateTimeImmutable('-1 day'));

        self::assertTrue((new TaskPriorityCalculator())->isOverdueAndHighPriority($task));
    }

    public function testReturnsFalseWhenPriorityIsNotHighEvenIfOverdue(): void
    {
        $task = new Task(new User('owner@example.com', 'Owner'), 'Overdue but low priority');
        $task->setPriority(TaskPriority::LOW);
        $task->setDueDate(new \DateTimeImmutable('-1 day'));

        self::assertFalse((new TaskPriorityCalculator())->isOverdueAndHighPriority($task));
    }
}
```

Both tests construct a real `Task` object directly (no database persistence involved) and call the calculator synchronously, which is exactly what keeps this a unit test: everything needed to run it exists in memory, in the test itself.

## Mini Quiz

```yaml
- question: "What specifically makes a test a 'unit test' rather than some other kind of test?"
  options:
    - "It uses the word 'unit' in its method name"
    - "It runs through a real HTTP request"
    - "It tests one small piece of code (typically a single class) in isolation, without a real database, HTTP layer, or other external dependencies"
    - "It is always the first test written for a feature"
  answer: 2
  explanation: "A unit test isolates a single unit of code, typically one class or method, from external dependencies like databases or HTTP, so it tests exactly one thing in a fast, deterministic way."

- question: "Why is $this->createMock(TaskRepository::class) used in the TaskServiceStatsTest example instead of a real TaskRepository?"
  options:
    - "createMock() is required by PHPUnit for every test"
    - "It avoids needing a real database connection, letting the test control exactly what the repository returns and stay fast and isolated"
    - "Real repositories cannot be instantiated in PHP"
    - "It makes the test run inside a full Symfony kernel"
  answer: 1
  explanation: "A mock lets the test simulate the repository's behavior without a real database, keeping the unit test focused purely on TaskService's own logic."

- question: "Why does assertSame(0, $stats[TaskStatus::IN_PROGRESS->value]) matter as a test case?"
  options:
    - "It doesn't matter; testing zero values is unnecessary"
    - "It verifies the specific business rule that statuses with no tasks still appear in the result with a count of zero, an easy-to-miss edge case"
    - "It tests that TaskService throws an exception"
    - "It only checks that the array is not empty"
  answer: 1
  explanation: "This assertion specifically verifies the edge case where a status has zero tasks but should still be present in the output with count 0, which is exactly the kind of case unit tests are good at pinning down."

- question: "Which of the following would make a test NOT a pure unit test anymore?"
  options:
    - "Using PHPUnit's TestCase base class"
    - "Asserting with assertSame instead of assertEquals"
    - "Booting the full Symfony kernel and connecting to a real database inside the test"
    - "Naming the test method descriptively"
  answer: 2
  explanation: "Involving a real kernel and database moves the test toward being a functional (or integration) test, since it is no longer isolating a single unit of code from its real dependencies."
```
