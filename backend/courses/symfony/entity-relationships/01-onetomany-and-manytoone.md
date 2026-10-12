---
slug: onetomany-and-manytoone
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: entity-relationships
chapterTitle: "Chapter 7 — Entity Relationships"
chapterOrder: 7
title: "OneToMany and ManyToOne Relationships"
difficulty: intermediate
order: 1
estimatedMinutes: 12
tags: [symfony, doctrine, relationships, entities]
---

## Learning Objective

By the end of this lesson you will be able to model a one-to-many / many-to-one relationship between two Doctrine entities — like a `TaskCategory` having many `Task` entities, and each `Task` belonging to one `TaskCategory` — using the correct attributes, and understand the meaning of `mappedBy` and `inversedBy`.

## Explanation

You already know from SQL/MySQL that a foreign key expresses "many rows in table A each point to one row in table B" — for example, many rows in `task` each have a `category_id` pointing to one row in `task_category`. In Doctrine, this same relationship is expressed from *both* sides as PHP properties, using two matching attributes:

- On the "many" side (`Task`), you use `#[ORM\ManyToOne]` — "many tasks point to one category." This side owns the actual foreign key column, declared with `#[ORM\JoinColumn]`.
- On the "one" side (`TaskCategory`), you use `#[ORM\OneToMany]` — "one category has many tasks." This side does **not** have a database column; it's a PHP-only convenience so you can write `$category->getTasks()` and get a collection of related `Task` objects.

Because the relationship is expressed on both entities, Doctrine needs to know which side is the "real," database-owning side (called the **owning side**) and which side is just a convenience mirror (called the **inverse side**). Two attribute options connect them:

- `mappedBy` goes on the inverse side (`OneToMany`) and names the property on the *other* entity that owns the relationship — e.g., `#[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'category')]` means "look at the `category` property over on `Task` to find the actual foreign key."
- `inversedBy` goes on the owning side (`ManyToOne`), and names the property on the *other* entity that mirrors this relationship back — e.g., `#[ORM\ManyToOne(targetEntity: TaskCategory::class, inversedBy: 'tasks')]` means "when this changes, keep the `tasks` collection on `TaskCategory` in sync in memory."

A collection property (like `TaskCategory::$tasks`) is typed as Doctrine's `Collection` interface, backed by an `ArrayCollection` — think of it as an array-like object of related entities that Doctrine can lazily load from the database only when you actually access it (this is called **lazy loading**, which you'll use more directly in the next lesson).

## Why It Matters

Almost every real-world data model has one-to-many relationships: an author has many blog posts, a category has many tasks, a user has many orders. Getting the owning/inverse side right matters because Doctrine only writes to the database based on the *owning* side. If you only set the inverse side in your PHP code (e.g., only push a task into `$category->getTasks()`) and never set `$task->setCategory($category)` on the owning side, Doctrine will **not** save the foreign key — a very common and confusing bug for developers new to Doctrine. Understanding which side is "real" avoids a whole class of "why didn't my relationship save" issues.

## Example

The reference Task Manager application already has this exact relationship: a `Task` belongs to one `TaskCategory` (nullable — a task can have no category), and a `TaskCategory` has many `Task` entities.

```php
$category = $categoryRepository->find($categoryId);
$task->setCategory($category); // owning side — this is what Doctrine persists
$entityManager->flush();

// Reading the other direction (inverse side), Doctrine lazily queries for it:
foreach ($category->getTasks() as $task) {
    echo $task->getTitle();
}
```

## Code

```php
<?php

namespace App\Entity;

use App\Repository\TaskCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskCategoryRepository::class)]
#[ORM\Table(name: 'task_category')]
class TaskCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name;

    /**
     * The inverse side of the relationship: no database column here.
     * mappedBy points to the `category` property on Task, which owns the foreign key.
     *
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'category')]
    private Collection $tasks;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }
}
```

```php
<?php
// Excerpt from App\Entity\Task — the owning side of the relationship

#[ORM\ManyToOne(targetEntity: TaskCategory::class, inversedBy: 'tasks')]
#[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
private ?TaskCategory $category = null;

public function getCategory(): ?TaskCategory
{
    return $this->category;
}

public function setCategory(?TaskCategory $category): static
{
    $this->category = $category;

    return $this;
}
```

## Code Explanation

- `#[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'category')]` on `TaskCategory::$tasks` declares the inverse side. `mappedBy: 'category'` says "the real foreign key lives on `Task::$category`."
- `$tasks` is initialized to `new ArrayCollection()` in the constructor — this is required so that before anything is loaded from the database, calling `getTasks()` on a brand-new object never returns `null`, only an (initially empty) collection.
- On the `Task` side, `#[ORM\ManyToOne(targetEntity: TaskCategory::class, inversedBy: 'tasks')]` declares the owning side; `inversedBy: 'tasks'` links back to `TaskCategory::$tasks` so Doctrine can keep the in-memory collection consistent.
- `#[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]` is only meaningful on the owning side — it defines the real `category_id` foreign key column, that it's nullable (a task can have no category), and that deleting a category should set `category_id` to `NULL` on its tasks rather than blocking the deletion or deleting the tasks too.
- Note that `$category` is typed `?TaskCategory` (nullable) while `$tasks` on the other side is always a `Collection` (never null, though it can be empty) — this asymmetry is normal and expected for OneToMany/ManyToOne pairs.

## Common Mistakes

- **Only updating the inverse side.** Calling `$category->getTasks()->add($task)` without also calling `$task->setCategory($category)` will not persist anything — Doctrine only writes based on the owning side (`ManyToOne`/the side with `JoinColumn`).
- **Forgetting to initialize collections in the constructor.** Without `$this->tasks = new ArrayCollection()`, a freshly-created `TaskCategory` would have an uninitialized property, causing errors before it's ever loaded from the database.
- **Mixing up `mappedBy` and `inversedBy`.** A simple rule: `mappedBy` is used by the side *without* the join column (pointing at the owning property name); `inversedBy` is used by the side *with* the join column (pointing at the inverse collection property name).
- **Not setting `onDelete` behavior deliberately.** Leaving foreign key delete behavior to Doctrine/MySQL defaults can produce unexpected `RESTRICT` errors when trying to delete a category that still has tasks, if that's not the intended behavior.

## Best Practices

- When you set one side of a bidirectional relationship in application code, consider adding a small helper on the "many" entity to keep both sides in sync in memory (e.g., a `setCategory()` that could also update the old and new category's collections), especially if you're not immediately calling `flush()`.
- Always type the inverse (`OneToMany`) side as `Collection`, never as a plain PHP array.
- Be explicit about `nullable` and `onDelete` on every `JoinColumn` — don't rely on implicit defaults for something as consequential as delete behavior.
- Keep the direction of the relationship matching real-world meaning: the entity that "has one" of the other (`Task` has one `TaskCategory`) is the `ManyToOne`/owning side.

## Exercise

Model a `Project` entity that has many `Task` entities (a `Task` belongs to at most one `Project`, separate from its category). Write the relevant relationship attributes and properties on both `Project` (the "one" side) and `Task` (the "many" side), including a nullable foreign key with `onDelete: 'SET NULL'`.

## Solution

```php
// Project.php — inverse side
/** @var Collection<int, Task> */
#[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project')]
private Collection $tasks;

public function __construct(string $name)
{
    $this->name = $name;
    $this->tasks = new ArrayCollection();
}

public function getTasks(): Collection
{
    return $this->tasks;
}
```

```php
// Task.php — owning side
#[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
#[ORM\JoinColumn(name: 'project_id', nullable: true, onDelete: 'SET NULL')]
private ?Project $project = null;

public function getProject(): ?Project
{
    return $this->project;
}

public function setProject(?Project $project): static
{
    $this->project = $project;

    return $this;
}
```

`Task` is the owning side because it holds the real `project_id` foreign key column via `JoinColumn`. `Project` is the inverse side, using `mappedBy: 'project'` to point back at that owning property, and exposing an initialized `Collection` so `getTasks()` is always safe to call.

## Mini Quiz

```yaml
- question: "In a ManyToOne/OneToMany pair, which side actually owns the database foreign key column?"
  options:
    - "The OneToMany side, using mappedBy"
    - "The ManyToOne side, using JoinColumn and inversedBy"
    - "Both sides own separate columns"
    - "Neither side owns a column; Doctrine creates a separate join table"
  answer: 1
  explanation: "The ManyToOne side is the owning side and holds the real foreign key column via #[ORM\\JoinColumn]. The OneToMany side is just an in-memory convenience collection."
- question: "What does mappedBy on a OneToMany property refer to?"
  options:
    - "The database column name for the foreign key"
    - "The property name on the other entity that owns the relationship (has the JoinColumn)"
    - "The name of the join table"
    - "The default sort order for the collection"
  answer: 1
  explanation: "mappedBy names the property on the owning entity (the ManyToOne side) where the actual foreign key relationship is defined."
- question: "If you only call $category->getTasks()->add($task) and never call $task->setCategory($category), what happens when you flush()?"
  options:
    - "The relationship is saved correctly because Doctrine syncs both sides automatically"
    - "Nothing is saved to the database, because only the owning side (Task::category) is used when persisting the relationship"
    - "An exception is thrown immediately"
    - "The category is deleted"
  answer: 1
  explanation: "Doctrine only persists relationship changes based on the owning side. Updating only the inverse (OneToMany) collection in memory has no effect on the database."
```
