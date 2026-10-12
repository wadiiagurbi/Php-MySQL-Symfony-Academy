---
slug: database-and-entity-design
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Database and Entity Design"
difficulty: advanced
order: 2
estimatedMinutes: 12
tags: [final-project, task-manager, database, doctrine]
---

## Learning Objective

By the end of this lesson, you will be able to sketch the Task Manager's relational schema — three tables and the foreign keys between them — and justify each design decision (nullability, cascade behavior, unique constraints) the way the real project's entities do.

## Explanation

Turning the requirements from the previous lesson into a schema means asking, for each piece of data: what table does it live in, what type is each column, and what happens to related rows when a parent row is deleted?

The project has three tables:

**`app_user`** — one row per registered account: `id`, `email` (unique), `display_name`, `password` (hashed, never plaintext), `roles`, `created_at`, and `last_lesson_slug` (unrelated to tasks — it's used elsewhere in this platform, a reminder that a real `User` table often serves more than one feature).

**`task_category`** — one row per user-defined category: `id`, `name`, and `owner_id`. Crucially, the uniqueness constraint is on the *pair* `(owner_id, name)`, not on `name` alone — two different users can both have a category named "Backend" without conflict, but the same user can't create "Backend" twice.

**`task`** — the core entity: `id`, `title`, `description` (nullable text), `status`, `priority`, `due_date` (nullable), `created_at`, `updated_at`, `owner_id` (foreign key to `app_user`, **not nullable** — a task always has an owner), and `category_id` (foreign key to `task_category`, **nullable** — a task doesn't need a category).

The relationships and their cascade behavior matter as much as the columns:

- `task.owner_id -> app_user.id`, `ON DELETE CASCADE`: if a user account is deleted, all of their tasks disappear with it. There's no scenario where an orphaned task with no owner makes sense.
- `task.category_id -> task_category.id`, `ON DELETE SET NULL`: if a category is deleted, tasks that referenced it aren't deleted — they simply lose their category. Losing a label is not a reason to lose the task itself.
- `task_category.owner_id -> app_user.id`, `ON DELETE CASCADE`: categories are per-user data, so they die with the user too.

## Why It Matters

Cascade behavior is a design decision, not a Doctrine default you can ignore. Picking `CASCADE` vs `SET NULL` vs `RESTRICT` for every foreign key is exactly the kind of decision that's cheap to get right in a migration and expensive to fix once production data exists. Real Symfony teams review these choices in code review just as carefully as they review business logic — a wrong `ON DELETE` clause causes silent data loss or a account-deletion feature that suddenly can't ship.

## Example

Reading the relationships as English sentences before writing them as Doctrine attributes is a useful sanity check:

- "A user owns many tasks; a task belongs to exactly one user." → `User` 1—* `Task`, non-nullable owner.
- "A user owns many categories; a task optionally belongs to one category, and a category can be reused by many tasks." → `User` 1—* `TaskCategory`, `TaskCategory` 1—* `Task`, nullable category on `Task`.
- "Two different users can each have their own category called the same name, but one user can't have two categories with the same name." → composite unique constraint on `(owner_id, name)`, not a plain unique on `name`.

## Code

```php
<?php
// Adapted from src/Entity/Task.php and src/Entity/TaskCategory.php —
// the two mapping decisions that encode the schema described above.

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\ManyToOne(targetEntity: TaskCategory::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    private ?TaskCategory $category = null;
}

#[ORM\Entity(repositoryClass: TaskCategoryRepository::class)]
#[ORM\Table(name: 'task_category')]
#[ORM\UniqueConstraint(name: 'uniq_category_owner_name', columns: ['owner_id', 'name'])]
class TaskCategory
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    private User $owner;
}
```

## Code Explanation

`Task::$owner`'s `JoinColumn` sets `nullable: false` — Doctrine will refuse to persist a `Task` without an owner, and the generated migration adds a `NOT NULL` constraint at the database level too, so the rule holds even if some future code path bypasses the entity. `onDelete: 'CASCADE'` is passed straight through into the SQL foreign key definition, so the *database itself* enforces cleanup, not application code that could be forgotten in some corner case.

`Task::$category`'s `JoinColumn` is the mirror image: `nullable: true` and `onDelete: 'SET NULL'`, matching "a task can exist without a category, and losing a category shouldn't destroy the task."

`TaskCategory`'s class-level `#[ORM\UniqueConstraint(columns: ['owner_id', 'name'])]` is a *composite* constraint spanning two columns — this is different from putting `unique: true` on the `name` column alone, which would make category names globally unique across every user, breaking the per-user scoping the requirements call for.

## Common Mistakes

- Making a foreign key `nullable: false` in the entity but forgetting to also cascade-delete it, leaving orphaned rows that violate referential integrity once a parent is removed outside of Doctrine (e.g. a manual `DELETE` in a database console).
- Putting a unique constraint on a single column (`name`) when the real uniqueness rule is scoped to a relationship (`owner_id + name`).
- Choosing `CASCADE` everywhere by default without asking whether the child data should actually survive the parent's deletion — categories surviving on their own with no owner would be a bug, but tasks silently vanishing on category deletion would be an even worse one.

## Best Practices

- Write out cascade behavior in plain English for every foreign key before deciding the Doctrine attribute — "if the parent goes away, should the child go away, be nulled, or block the delete?"
- Model uniqueness at the same granularity the business rule actually applies to (per-user, per-tenant, global) rather than defaulting to a single-column unique index.
- Keep nullability on the join column consistent with nullability on the PHP property type (`?TaskCategory $category` mirrors `nullable: true`) so the type system and the schema never disagree.

## Exercise

The project doesn't currently support sub-tasks. Design (on paper — no code yet) the schema change needed to let a `Task` optionally have a `parentTask`, so a task can be a child of another task. Decide: is the foreign key nullable? What's the `onDelete` behavior if the parent task is deleted? Does this change any existing unique constraint?

## Solution

Add a nullable `parent_task_id` column on `task`, a self-referencing foreign key to `task.id`. It must be nullable, since most tasks have no parent. For `onDelete`, `CASCADE` is a reasonable choice — deleting a parent task should delete its children rather than leave dangling sub-tasks pointing at nothing, though `SET NULL` (promoting children to top-level tasks) is also defensible and would need to be a deliberate product decision, not an accident. No existing unique constraint changes, since `(owner_id, name)` on categories and the primary key on `task` are unaffected by this new relationship.

## Mini Quiz

```yaml
- question: "Why is the unique constraint on task_category defined on (owner_id, name) instead of on name alone?"
  options:
    - "Doctrine requires at least two columns in every unique constraint"
    - "Category names must be globally unique across the whole app"
    - "Two different users should be able to each have a category with the same name"
    - "SQLite does not support single-column unique indexes"
  answer: 2
  explanation: "Categories are scoped per user, so uniqueness must also be scoped per user — the composite constraint allows 'Backend' to exist once per owner, not once globally."

- question: "What happens to a Task's category field when that TaskCategory row is deleted, given onDelete: 'SET NULL'?"
  options:
    - "The Task row is deleted too"
    - "The delete is blocked by the database"
    - "The Task's category_id becomes NULL and the task itself remains"
    - "Nothing happens until the next Doctrine flush"
  answer: 2
  explanation: "SET NULL means the foreign key column is nulled out automatically by the database when the referenced row is removed, while the referencing row (the task) survives."

- question: "Why is Task::$owner's join column set to nullable: false with onDelete: CASCADE?"
  options:
    - "Because tasks are optional and should be cleaned up automatically"
    - "Because a task must always have exactly one owner, and deleting that owner should delete their tasks too"
    - "Because Doctrine requires every ManyToOne relation to cascade"
    - "Because SQLite does not support nullable foreign keys"
  answer: 1
  explanation: "A task without an owner would be meaningless in this app's model, so the column is required, and CASCADE ensures no orphaned tasks are left behind if a user account is removed."
```
