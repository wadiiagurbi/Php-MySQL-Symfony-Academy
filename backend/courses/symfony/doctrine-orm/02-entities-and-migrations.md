---
slug: entities-and-migrations
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: doctrine-orm
chapterTitle: "Chapter 6 — Doctrine ORM"
chapterOrder: 6
title: "Entities and Migrations"
difficulty: intermediate
order: 2
estimatedMinutes: 10
tags: [symfony, doctrine, entities, migrations]
---

## Learning Objective

By the end of this lesson you will be able to define a Doctrine **entity** using PHP 8 attributes, understand what a **migration** is, and know why teams use migrations instead of manually running `ALTER TABLE` statements.

## Explanation

An **entity** is a plain PHP class that represents a single row of a database table. Each instance of the class represents one row; each typed property on the class represents one column. What makes it a Doctrine entity (rather than just a regular PHP class) is a set of **mapping attributes** — PHP 8 attributes like `#[ORM\Entity]`, `#[ORM\Table]`, and `#[ORM\Column]` — that tell Doctrine exactly which table and columns this class corresponds to.

For example, a `Task` entity with a `#[ORM\Column(length: 140)] private string $title;` property tells Doctrine: "there is a `title` column, it's a string, max length 140, and it maps to this property." Doctrine reads these attributes to generate SQL and to hydrate (build) objects from query results — this is the mapping mentioned in the previous lesson.

Defining an entity class does not, by itself, create a database table. The entity is a description of a table's *intended* shape. To actually create or change the real table in the database, you need a **migration**.

A **migration** is a small, versioned PHP class containing "up" and "down" methods, each with SQL statements that change the schema — for example, `CREATE TABLE task (...)` in the "up" method, and `DROP TABLE task` in the "down" method (to reverse it). Symfony's DoctrineMigrationsBundle can compare your entity mappings against the actual database schema and *generate* a migration file automatically, listing exactly what changed.

Why not just run `ALTER TABLE` by hand? Because in any real project the schema changes over time, and those changes need to be applied consistently across every environment: your machine, your teammates' machines, staging, and production. Manually running SQL means:

- It's easy to forget to run it somewhere (a teammate pulls your entity change but never runs the matching `ALTER TABLE`, and their local app breaks in confusing ways).
- There's no reliable, ordered history of "what changed and when" that everyone can replay from scratch.
- There's no safe way to undo a bad schema change on production without someone remembering the exact reverse SQL.

Migrations solve this by turning schema changes into files, committed to the same git repository as the code that needs them, applied in a fixed order, and tracked (via a `migration_versions` table Doctrine creates) so that every environment always knows exactly which migrations have already run.

## Why It Matters

In a real team, a developer adds a new column to an entity, generates a migration, and commits both the entity change and the migration file together. When a teammate pulls that commit and runs `php bin/console doctrine:migrations:migrate`, their local database is brought up to date automatically — no verbal "hey, don't forget to run this SQL" required. The same migration file runs unmodified against staging and production later, so what was tested locally is exactly what gets deployed. This traceability is often a hard requirement in professional environments with audits, rollbacks, or multiple deployment environments.

## Example

Suppose the Task Manager application needs a new `TaskCategory` entity/table to group tasks (this table already exists in the reference project). You would: (1) write the `TaskCategory` PHP class with mapping attributes, (2) run `php bin/console make:migration` (or `doctrine:migrations:diff`) to generate a migration file that Doctrine computes by comparing your entities to the current database, (3) review the generated SQL to make sure it's what you expect, and (4) run `php bin/console doctrine:migrations:migrate` to actually apply it.

## Code

```php
<?php

namespace App\Entity;

use App\Repository\TaskCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskCategoryRepository::class)]
#[ORM\Table(name: 'task_category')]
#[ORM\UniqueConstraint(name: 'uniq_category_owner_name', columns: ['owner_id', 'name'])]
class TaskCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 80)]
    private string $name;

    public function __construct(User $owner, string $name)
    {
        $this->owner = $owner;
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
```

## Code Explanation

- `#[ORM\Entity(repositoryClass: TaskCategoryRepository::class)]` marks this class as a Doctrine entity and tells Doctrine which repository class handles its database queries (covered in the next lesson).
- `#[ORM\Table(name: 'task_category')]` sets the exact table name. If omitted, Doctrine derives a table name from the class name — being explicit avoids surprises.
- `#[ORM\UniqueConstraint(...)]` declares a database-level unique constraint spanning two columns (`owner_id`, `name`) — a category name must be unique per owner, enforced by the database itself, not just application code.
- `#[ORM\Id]` marks `$id` as the primary key; `#[ORM\GeneratedValue]` tells Doctrine the database auto-generates it (an `AUTO_INCREMENT` column in MySQL).
- `#[ORM\Column]` with no arguments still maps the property to a column, inferring type from the PHP type (`?int` becomes an integer column).
- `#[ORM\ManyToOne(...)]` and `#[ORM\JoinColumn(...)]` define a foreign key relationship to `User` — you'll cover relationships fully in Chapter 7, but note here how a foreign key is expressed directly on the property.
- None of this creates the table. A migration generated from this mapping is what actually issues `CREATE TABLE task_category (...)` against the database.

## Common Mistakes

- **Editing the entity and forgetting to generate/run a migration.** The PHP code will look correct, but the database table won't match, causing errors like "Unknown column" at runtime.
- **Hand-editing a migration's SQL without understanding it.** Auto-generated migrations should be reviewed, not blindly trusted — Doctrine can sometimes produce destructive changes (like dropping and recreating a column) that you should adjust manually to preserve data.
- **Running migrations out of order or skipping the migration table.** Never manually `ALTER TABLE` to "fix" something outside the migration system — it desyncs the recorded migration history from the real schema, causing future migrations to fail or double-apply changes.
- **Forgetting that `#[ORM\Column]` type must match the PHP property type.** A mismatch (e.g., a `string` property mapped as an integer column) causes confusing hydration errors.

## Best Practices

- Always generate migrations from entity changes (`make:migration` or `doctrine:migrations:diff`) rather than writing schema SQL from scratch.
- Commit the migration file in the same commit as the entity change that caused it.
- Review generated migration SQL before running it, especially in production — check for accidental data loss (dropped columns, changed nullability).
- Never edit an already-applied (and especially already-deployed) migration file; write a new migration instead, the same way you'd never rewrite git history that others have already pulled.
- Keep entity classes focused on describing the *shape* of data — don't leak dozens of unrelated behaviors into an entity class.

## Exercise

You need to add a new `dueDateReminder` boolean column to the `Task` entity (defaulting to `false`) so users can optionally get reminders. Describe, step by step, everything you would do from editing the entity to the database actually having the new column — including what command generates the migration and what command applies it.

## Solution

1. Add the property to the `Task` entity: `#[ORM\Column(options: ['default' => false])] private bool $dueDateReminder = false;` plus a getter/setter.
2. Run `php bin/console make:migration` (or `doctrine:migrations:diff`). Doctrine compares the updated entity mapping against the current database schema and generates a new migration file containing an `ALTER TABLE task ADD due_date_reminder TINYINT(1) DEFAULT 0 NOT NULL` (or equivalent) statement in its `up()` method, and the reverse `DROP COLUMN` in `down()`.
3. Open the generated migration file and review the SQL to confirm it matches expectations and won't unexpectedly affect existing rows.
4. Run `php bin/console doctrine:migrations:migrate` to apply it to the local database. Doctrine records this migration's version in its internal migration-tracking table so it won't run again.
5. Commit both the entity change and the migration file together, so teammates and other environments apply the exact same schema change when they pull and run migrations.

## Mini Quiz

```yaml
- question: "What is a Doctrine entity?"
  options:
    - "A SQL file that creates a table"
    - "A plain PHP class, annotated with mapping attributes, that represents a row of a database table"
    - "A Symfony controller that handles database requests"
    - "A configuration file listing all database columns"
  answer: 1
  explanation: "An entity is a PHP class whose properties map, via attributes like #[ORM\\Column], to columns of a table; each instance represents one row."
- question: "Why do teams use migrations instead of manually running ALTER TABLE statements?"
  options:
    - "Migrations are the only way Doctrine allows database access"
    - "Migrations run faster than raw SQL"
    - "Migrations provide a versioned, repeatable, and shareable record of schema changes that every environment can apply consistently"
    - "Migrations remove the need to design entities"
  answer: 2
  explanation: "Migrations turn schema changes into ordered, trackable files that can be safely replayed across every environment (local, staging, production), avoiding manual and inconsistent ALTER TABLE runs."
- question: "After you generate a migration for a new column, what must still happen for the database to actually have that column?"
  options:
    - "Nothing, generating the migration file already changes the database"
    - "You must run the migration (e.g. doctrine:migrations:migrate) to apply it"
    - "You must restart the web server"
    - "You must manually copy the SQL into MySQL Workbench"
  answer: 1
  explanation: "Generating a migration only creates the file describing the change; the change is applied to the actual database only when the migration is run/executed."
```
