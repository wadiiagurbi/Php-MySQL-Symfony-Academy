---
slug: task-entity-and-relationships
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "The Task Entity and Relationships"
difficulty: advanced
order: 4
estimatedMinutes: 12
tags: [final-project, task-manager, doctrine, entities]
---

## Learning Objective

By the end of this lesson, you will be able to explain how the `Task` entity models its fields with backed enums, how it enforces its own `updatedAt` timestamp internally, and how its `ManyToOne` relationships to `User` and `TaskCategory` are wired.

## Explanation

`Task` is the central entity of this project, and its design makes several deliberate choices worth studying line by line.

**Enums instead of strings.** `status` and `priority` aren't plain strings — they're backed enums, `TaskStatus` and `TaskPriority`:

```php
enum TaskStatus: string { case TODO = 'TODO'; case IN_PROGRESS = 'IN_PROGRESS'; case DONE = 'DONE'; }
enum TaskPriority: string { case LOW = 'LOW'; case MEDIUM = 'MEDIUM'; case HIGH = 'HIGH'; }
```

Doctrine maps these with `enumType:` on the column, which means invalid values (like `"todo"` lowercase, or `"URGENT"`) can never be persisted — PHP itself rejects them the moment you try to construct a case that doesn't exist. This pushes a whole category of validation ("is this a real status?") out of application code and into the type system.

**Constructor-enforced invariants.** `Task::__construct(User $owner, string $title)` requires an owner and a title up front — there is no way to create a `Task` object without both. Everything else (`status`, `priority`, `createdAt`, `updatedAt`) gets a sensible default inside the constructor (`TaskStatus::TODO`, `TaskPriority::MEDIUM`, "now").

**Self-maintaining `updatedAt`.** Every setter that changes meaningful data — `setTitle`, `setDescription`, `setStatus`, `setPriority`, `setDueDate`, `setCategory` — calls a private `touch()` method that refreshes `updatedAt`. This guarantees the timestamp is accurate no matter which setter a caller uses, without the caller having to remember to update it manually.

**Relationships.** `Task` has a required `ManyToOne` to `User` (every task has exactly one owner) and an optional `ManyToOne` to `TaskCategory` (`inversedBy: 'tasks'`, matching `TaskCategory`'s `OneToMany` back-reference). Both relationships were designed in the previous lesson; here we see them expressed as PHP.

## Why It Matters

Encoding "which strings are valid statuses" as an enum instead of a string plus a validation constraint is a pattern every modern PHP/Symfony codebase should reach for. It collapses an entire class of bugs (typos in status strings scattered across the codebase) into a single, exhaustively-checkable type. Similarly, moving "keep `updatedAt` fresh" into the entity itself — rather than relying on every controller or service to remember to set it — is what makes an entity actually *own* its invariants instead of being a passive data bag that callers can misuse.

## Example

Because `status` is a backed enum, matching on it exhaustively is a compile-time-checkable operation:

```php
match ($task->getStatus()) {
    TaskStatus::TODO => 'Not started',
    TaskStatus::IN_PROGRESS => 'In progress',
    TaskStatus::DONE => 'Complete',
};
```

If a new case were ever added to `TaskStatus` without updating this `match`, static analysis tools (and PHP itself, at runtime, with `match`'s "UnhandledMatchError") would catch the gap — something a plain string comparison never would.

## Code

```php
<?php
// Adapted from src/Entity/Task.php

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 140)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 140)]
    private string $title;

    #[ORM\Column(length: 20, enumType: TaskStatus::class)]
    private TaskStatus $status;

    #[ORM\Column(length: 10, enumType: TaskPriority::class)]
    private TaskPriority $priority;

    #[ORM\ManyToOne(targetEntity: TaskCategory::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    private ?TaskCategory $category = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $owner, string $title)
    {
        $this->owner = $owner;
        $this->title = $title;
        $this->status = TaskStatus::TODO;
        $this->priority = TaskPriority::MEDIUM;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

## Code Explanation

`#[ORM\Column(length: 20, enumType: TaskStatus::class)]` tells Doctrine to store the enum's backing value (`'TODO'`, `'IN_PROGRESS'`, `'DONE'`) as a `VARCHAR(20)`, and to hydrate it back into a `TaskStatus` instance — not a plain string — every time the row is loaded. `setStatus(TaskStatus $status)` accepts a `TaskStatus` instance, not a string, so a caller can't accidentally pass an invalid value; the enum type itself is the validation. `touch()` is private — it's an internal implementation detail, not something outside code should call directly, reinforcing that "keeping the timestamp fresh" is the entity's own responsibility, triggered automatically by every mutating setter.

## Common Mistakes

- Mapping `status`/`priority` as plain `string` columns and validating allowed values separately (e.g. with `Assert\Choice` only) — this leaves the *type system* unaware of what's valid, so any code path that bypasses validation can still write garbage into the column.
- Forgetting to call `touch()` (or an equivalent) in every setter, leading to a `updatedAt` that's stale for whichever field the developer forgot to wire up.
- Making the `User $owner` property optional or defaulted, which would let a `Task` exist momentarily (or permanently, if a bug slips through) without an owner.

## Best Practices

- Use backed enums for any field with a small, fixed set of valid values, and map them with Doctrine's `enumType` so the database and the type system agree.
- Centralize side effects like timestamp updates inside the entity via a small private helper, rather than repeating `$this->updatedAt = new \DateTimeImmutable();` in every setter.
- Require everything an object cannot function without (like `owner`) as constructor arguments, and give sensible defaults only to fields that truly have one (like a new task always starting as `TODO`/`MEDIUM`).

## Exercise

Add a new `TaskPriority::URGENT` case. List every place in the codebase (entities, DTOs, repository) that would need to change for this new case to be fully supported end-to-end, based on what you've read so far.

## Solution

Four places: (1) `TaskPriority` enum itself — add `case URGENT = 'URGENT';`. (2) `TaskRequest::$priority`'s `Assert\Choice(choices: [...])` constraint — add `'URGENT'` to the allowed list, or the DTO layer will reject valid input before it ever reaches the entity. (3) Any `match`/`switch` statements over `TaskPriority` elsewhere in the app (none exist yet in this project, but a future UI-facing "priority label" helper would need one). (4) A new Doctrine migration is *not* strictly required, since the column is already a `VARCHAR(10)` string large enough to hold `'URGENT'` — but it's worth double-checking column length limits whenever a new enum case is added, since `enumType` columns don't auto-grow.

## Mini Quiz

```yaml
- question: "What does mapping Task::$status with enumType: TaskStatus::class prevent, compared to a plain string column?"
  options:
    - "It prevents the column from ever being NULL"
    - "It prevents invalid status values from being constructed or persisted at all"
    - "It prevents the field from being updated after creation"
    - "It automatically translates the status into multiple languages"
  answer: 1
  explanation: "A backed enum can only ever hold one of its declared cases, so Doctrine's enumType mapping means the database and PHP both reject any status value that isn't TODO, IN_PROGRESS, or DONE."

- question: "Why is touch() called inside setStatus, setPriority, setDueDate, and every other mutating setter?"
  options:
    - "To satisfy a PHPUnit code coverage requirement"
    - "To keep updatedAt accurate automatically, regardless of which setter a caller uses"
    - "To trigger a Doctrine event listener"
    - "To prevent concurrent writes to the same task"
  answer: 1
  explanation: "Centralizing the timestamp refresh in a private touch() method means every setter updates it consistently without each one repeating the same line of code."

- question: "Why must Task::__construct require both a User $owner and a string $title?"
  options:
    - "Doctrine requires all constructor arguments to be scalar"
    - "Because a task without an owner or title would violate the entity's own invariants"
    - "To avoid needing a database migration"
    - "Because PHP does not allow optional constructor parameters"
  answer: 1
  explanation: "Owner and title are the two things a Task cannot meaningfully exist without in this project's design, so they are required at construction time rather than being optional or defaulted."
```
