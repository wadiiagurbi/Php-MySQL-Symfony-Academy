---
slug: what-is-an-orm
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: doctrine-orm
chapterTitle: "Chapter 6 — Doctrine ORM"
chapterOrder: 6
title: "What is an ORM?"
difficulty: intermediate
order: 1
estimatedMinutes: 8
tags: [symfony, doctrine, orm]
---

## Learning Objective

By the end of this lesson you will understand what an ORM (Object-Relational Mapper) is, what problem it solves compared to writing raw SQL by hand, and how Doctrine — the ORM used by Symfony — fits into an application's architecture.

## Explanation

So far, if you wanted data from a MySQL database in a PHP application, you would write SQL by hand: open a connection, write a `SELECT` statement, execute it, and manually turn each row of the result set into something your PHP code can use — an array, or maybe a simple object you build yourself. You'd repeat this pattern for every table, and again for every `INSERT`, `UPDATE`, and `DELETE`.

This works, but it has real costs as an application grows:

- The same "turn a database row into a PHP object" logic gets duplicated everywhere.
- SQL strings are scattered across the codebase, mixed with business logic, making them hard to find and change safely.
- Building queries by concatenating strings (`"WHERE id = " . $id`) is exactly how SQL injection vulnerabilities happen if you're not careful with escaping.
- Keeping the PHP object shape and the SQL table shape in sync by hand is tedious and error-prone.

An **ORM (Object-Relational Mapper)** is a library that sits between your PHP code and your database and automates this translation. Instead of writing SQL to fetch a task, you ask an object store for a `Task` object by its ID, and the ORM generates and runs the SQL for you, then builds the PHP object from the result. Instead of writing an `UPDATE` statement, you change a property on a PHP object and tell the ORM "save this" — it figures out which SQL statement is needed.

**Doctrine ORM** is the library Symfony applications use for this. Doctrine works by knowing the *mapping* between a PHP class (called an **entity** — you'll meet that term properly in the next lesson) and a database table: which class maps to which table, which property maps to which column, and how relationships between classes (like a `Task` belonging to a `User`) map to foreign keys. Once that mapping is defined, Doctrine can generate the correct SQL automatically for reading, inserting, updating, and deleting rows, and it manages a layer called the **EntityManager** that tracks which objects have changed and need saving.

It's important to understand what Doctrine does *not* do: it doesn't replace your database, and it doesn't hide SQL from you forever. You still design tables and relationships, you can still write raw SQL or Doctrine's own query language (DQL) when you need precise control, and understanding SQL/MySQL (which you already do) is what makes Doctrine's behavior predictable rather than "magic."

## Why It Matters

In real Symfony projects, almost all database access goes through Doctrine, not raw PDO or mysqli calls. Teams choose this because:

- **Consistency**: every developer fetches and saves data the same way, through entities and the EntityManager, rather than everyone writing slightly different SQL.
- **Safety by default**: Doctrine uses parameterized queries internally, which is a strong default protection against SQL injection compared to hand-built query strings.
- **Refactoring support**: if you rename a PHP property, tools and your IDE can find every usage, because it's a real class property — not a "magic string" key in an array pulled from a database row.
- **Database portability**: the same entity mapping can target MySQL, PostgreSQL, or SQLite with minimal changes, because Doctrine generates dialect-specific SQL underneath.

Recruiters and codebases will assume you know Doctrine if you say you know Symfony — it's the de facto standard persistence layer, similar to how Eloquent is to Laravel.

## Example

Imagine you need to load the task with ID `42` and print its title. Without an ORM, you might write:

```sql
SELECT id, title, status FROM task WHERE id = 42;
```

...and then manually map columns to variables. With Doctrine, once a `Task` entity is mapped to the `task` table, the equivalent PHP code looks like this instead — no SQL text anywhere:

```php
$task = $entityManager->getRepository(Task::class)->find(42);
echo $task->getTitle();
```

Doctrine translates that `find(42)` call into a `SELECT ... WHERE id = ?` query, runs it, and constructs a fully-formed `Task` object for you.

## Code

```php
<?php

namespace App\Controller\Api;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskLookupController extends AbstractController
{
    #[Route('/api/tasks/{id}', name: 'api_task_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // No SQL written here. Doctrine translates this into a SELECT query
        // and hydrates (builds) a Task PHP object from the resulting row.
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (null === $task) {
            return $this->json(['error' => ['message' => 'Task not found.']], 404);
        }

        return $this->json([
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus(),
        ]);
    }
}
```

## Code Explanation

- `EntityManagerInterface $entityManager` is injected via Symfony's service container (which you already know from earlier chapters). The EntityManager is Doctrine's central object: it knows how to talk to the database and how to build/save entities.
- `$entityManager->getRepository(Task::class)` returns the repository responsible for `Task` objects — you'll cover repositories in depth in a later lesson, but for now think of it as "the object in charge of fetching tasks."
- `->find($id)` asks Doctrine for the `Task` row whose primary key equals `$id`. Behind the scenes, Doctrine builds a `SELECT * FROM task WHERE id = ?` query, executes it with `$id` as a bound parameter (safe against SQL injection), and either returns a fully built `Task` object or `null` if no row matched.
- The controller never constructs SQL, never manually reads a result set, and never manually instantiates `Task` from an array — Doctrine does all of that based on the entity's mapping.

## Common Mistakes

- **Assuming Doctrine "replaces" SQL knowledge.** You still need to understand what SQL query Doctrine will generate, especially for performance (see the QueryBuilder/DQL lesson later in this chapter). Doctrine is a tool for generating SQL correctly and consistently — not a reason to stop thinking about your database.
- **Mixing raw SQL and Doctrine carelessly.** It's possible to run raw SQL alongside Doctrine, but doing so without care can leave Doctrine's internal object tracking out of sync with the database.
- **Expecting instant results without configuration.** Doctrine needs an accurate mapping (via attributes, as you'll see next lesson) to work — a missing or wrong mapping produces confusing errors, not silently wrong data.

## Best Practices

- Learn to read the SQL Doctrine generates (Symfony's profiler toolbar shows every query per request) so ORM behavior never feels like a black box.
- Keep using your SQL/MySQL knowledge to design good table structure and indexes — Doctrine maps to that structure, it doesn't invent a better one for you.
- Reach for Doctrine's query tools (repositories, QueryBuilder, DQL) before reaching for raw SQL; drop to raw SQL only for cases Doctrine genuinely can't express efficiently.

## Exercise

In your own words (a few sentences), explain to a teammate who only knows raw SQL and PDO why a Symfony project uses Doctrine instead of hand-written queries. Mention at least two concrete problems Doctrine avoids.

## Solution

A strong answer covers: (1) Doctrine removes the need to manually map database rows to PHP objects and back, which would otherwise be duplicated in every part of the app that touches the database; (2) Doctrine uses parameterized queries internally, which protects against SQL injection far more reliably than manually built query strings; (3) because entities are real PHP classes with real properties, renaming or refactoring is safer and IDE-assisted, unlike working with raw associative arrays keyed by column name; (4) the same entity mapping can be adapted to different database engines with minimal change, which raw, dialect-specific SQL cannot do without rewriting.

## Mini Quiz

```yaml
- question: "What is the main problem an ORM like Doctrine solves?"
  options:
    - "It makes MySQL run faster than raw SQL queries"
    - "It automates translating between PHP objects and database rows/tables, instead of you writing that mapping and SQL by hand"
    - "It replaces the need for a database entirely"
    - "It removes the need to design database tables"
  answer: 1
  explanation: "Doctrine's core job is mapping PHP objects to database rows and generating the SQL for you, not making queries faster or removing the database."
- question: "In the example controller, what does entityManager->getRepository(Task::class)->find(42) do?"
  options:
    - "It runs a raw SQL string you must write yourself"
    - "It deletes the task with ID 42"
    - "It asks Doctrine to build and run a SELECT query for the row with primary key 42, and returns a Task object or null"
    - "It only works if you also write the SELECT statement manually"
  answer: 2
  explanation: "find() asks Doctrine to fetch by primary key; Doctrine generates the SQL and hydrates the result into a Task object automatically, or returns null if nothing matched."
- question: "Which statement about Doctrine and SQL knowledge is true?"
  options:
    - "Once you use Doctrine you never need to think about the SQL it generates"
    - "Doctrine hides the database completely so SQL/MySQL knowledge becomes irrelevant"
    - "Doctrine still benefits from understanding SQL, especially for performance and debugging"
    - "Doctrine only works with SQLite, not MySQL"
  answer: 2
  explanation: "Doctrine automates writing SQL, but understanding what SQL it produces is essential for debugging and performance (e.g. spotting N+1 queries later in this course)."
```
