---
slug: querybuilder-and-dql-basics
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: doctrine-orm
chapterTitle: "Chapter 6 — Doctrine ORM"
chapterOrder: 6
title: "QueryBuilder and DQL Basics"
difficulty: intermediate
order: 4
estimatedMinutes: 12
tags: [symfony, doctrine, querybuilder, dql]
---

## Learning Objective

By the end of this lesson you will be able to write custom, filtered, sorted queries in a repository using Doctrine's QueryBuilder, understand what DQL (Doctrine Query Language) is, and know when to reach for these tools instead of the simple `findBy()` methods from the previous lesson.

## Explanation

The default repository methods you saw in the last lesson — `find()`, `findAll()`, `findBy(['status' => 'done'])` — cover simple cases well, but real features need more: filtering by several optional criteria at once, searching text, sorting by a dynamic field, paginating, or counting grouped results. For these, Doctrine gives you two related tools.

**DQL (Doctrine Query Language)** is a query language that looks like SQL but is written in terms of your *entities and their properties*, not raw tables and columns. For example: `SELECT t FROM App\Entity\Task t WHERE t.status = :status`. Notice `t.status` refers to the `Task` entity's `$status` property, not necessarily a column with that exact name — Doctrine translates DQL into real SQL against the real table and column names based on the entity mapping.

The **QueryBuilder** is a fluent, object-oriented PHP API for constructing DQL queries piece by piece, method call by method call, instead of writing a DQL string directly. It's the tool you'll use most often in practice, because it lets you build a query conditionally — for example, adding a `WHERE` clause only if a filter was actually provided — which is awkward to do by concatenating raw DQL strings safely.

Every repository (since it extends `ServiceEntityRepository`) has access to `$this->createQueryBuilder('t')` — the `'t'` here is an *alias*, a short name you choose to refer to the entity throughout the rest of the query, exactly like aliasing a table in SQL (`FROM task t`).

A QueryBuilder call chain typically looks like:

```php
$this->createQueryBuilder('t')
    ->andWhere('t.status = :status')
    ->setParameter('status', $status)
    ->orderBy('t.createdAt', 'DESC')
    ->setMaxResults(20)
    ->getQuery()
    ->getResult();
```

Each method adds a piece of the query. `setParameter()` binds a value safely (never concatenate user input directly into the query string — that reopens the SQL injection risk Doctrine is supposed to protect against). `getQuery()` compiles everything into an actual `Query` object, and `getResult()` executes it and returns entities. Other terminal methods exist for different needs: `getSingleScalarResult()` for a single number (like a `COUNT`), `getArrayResult()` for plain arrays instead of entity objects (useful for aggregated/grouped data that doesn't map cleanly to one entity).

## Why It Matters

Real features almost always need more than "find all" or "find by one exact field." A task list screen with optional filters (status, category, search text), sorting, and pagination — like the Task Manager's task list endpint — needs a query that's built up conditionally based on which filters the user actually provided. Doing this safely and readably is exactly what QueryBuilder is for. It's also central to spotting and fixing performance problems: understanding QueryBuilder/DQL lets you see exactly what's being asked of the database, which is essential when you later learn to recognize and fix N+1 query problems in the relationships chapter.

## Example

Consider a task list feature where a user can optionally filter by status, by category, and by a search term, and results should be sorted and paginated. Writing this with `findBy()` alone isn't really possible, because the set of conditions changes based on user input — this is exactly the conditional-query use case QueryBuilder is built for.

## Code

```php
<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function findFiltered(User $owner, ?string $status, ?int $categoryId, ?string $search): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(50);

        // Each filter is added only when the caller actually provided it —
        // this conditional construction is exactly what QueryBuilder is for.
        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if (null !== $categoryId) {
            $qb->andWhere('t.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if (null !== $search && '' !== $search) {
            $qb->andWhere('LOWER(t.title) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(User $owner): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.status AS status, COUNT(t.id) AS total')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('t.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
```

## Code Explanation

- `$this->createQueryBuilder('t')` starts a query against `Task` (the entity this repository is bound to), aliased as `t` for the rest of the chain.
- `andWhere('t.owner = :owner')` adds a `WHERE` condition using a named placeholder `:owner`; `setParameter('owner', $owner)` binds the actual value safely — Doctrine handles quoting/escaping, avoiding SQL injection.
- Each `if` block conditionally calls `andWhere()` again — this is the core reason QueryBuilder exists: building up a query's shape based on runtime conditions, safely, without string concatenation.
- `orderBy('t.createdAt', 'DESC')` and `setMaxResults(50)` map directly to SQL's `ORDER BY` and `LIMIT`.
- `getQuery()->getResult()` compiles the QueryBuilder into a `Query`, executes it, and returns an array of hydrated `Task` objects.
- `countByStatus()` shows a different terminal call: `select('t.status AS status, COUNT(t.id) AS total')` overrides the default "select the whole entity" behavior to select an aggregate, `groupBy('t.status')` mirrors SQL's `GROUP BY`, and `getArrayResult()` returns plain PHP arrays (not `Task` objects) since the result isn't a full entity — it's aggregated data.

## Common Mistakes

- **String-concatenating user input into a DQL string.** Even though DQL isn't raw SQL, concatenating unescaped values (`"t.title = '" . $search . "'"`) is just as dangerous — always use `setParameter()`.
- **Forgetting `getQuery()` before a result method.** `getResult()`, `getSingleScalarResult()`, etc. are methods on the compiled `Query` object, not directly on the QueryBuilder.
- **Reusing a QueryBuilder instance for two different queries without cloning it.** If you need the same base filters for both a `SELECT *` and a `COUNT`, clone the builder (`clone $qb`) before diverging, otherwise mutating one affects the other.
- **Using `getResult()` when you only need a count.** Loading full entities just to call `count()` in PHP is wasteful — use `getSingleScalarResult()` with a `COUNT()` in the query instead, so the database does the counting.
- **Writing DQL that references a column name instead of a property name.** DQL is always in terms of entity property names (`t.dueDate`), even if the underlying database column is named differently.

## Best Practices

- Default to QueryBuilder for anything beyond a simple exact-match lookup; reach for raw DQL strings only when QueryBuilder's fluent API becomes genuinely awkward.
- Name conditional filter methods clearly (`findFiltered`, `findOverdueForOwner`) rather than exposing raw QueryBuilder objects outside the repository — keep the repository as the boundary that owns query construction.
- Always bind dynamic values with `setParameter()`, never string interpolation.
- When you only need a number or a small projection of fields, select exactly that instead of full entities — it reduces both the SQL work and the PHP object hydration work.

## Exercise

Add a method `findOverdue(User $owner): array` to `TaskRepository` that returns all of a given user's tasks where `dueDate` is in the past (`< now`) and `status` is not `'done'`, sorted by `dueDate` ascending (most overdue first).

## Solution

```php
public function findOverdue(User $owner): array
{
    return $this->createQueryBuilder('t')
        ->andWhere('t.owner = :owner')
        ->andWhere('t.dueDate < :now')
        ->andWhere('t.status != :done')
        ->setParameter('owner', $owner)
        ->setParameter('now', new \DateTimeImmutable())
        ->setParameter('done', 'done')
        ->orderBy('t.dueDate', 'ASC')
        ->getQuery()
        ->getResult();
}
```

This builds a query with three combined `WHERE` conditions (owner match, past due date, not done), binds all three values safely with `setParameter()`, sorts ascending so the most overdue tasks come first, and executes it with `getQuery()->getResult()` to return an array of `Task` entities.

## Mini Quiz

```yaml
- question: "What does DQL query language use to reference fields, unlike raw SQL?"
  options:
    - "Raw database column names, exactly like SQL"
    - "Entity property names (e.g. t.status), which Doctrine translates to the real column names"
    - "JSON paths"
    - "Table indexes"
  answer: 1
  explanation: "DQL is written against entity classes and their properties (t.status), and Doctrine translates that into the actual SQL against real table/column names based on the entity mapping."
- question: "Why is QueryBuilder often preferred over writing a raw DQL string by hand?"
  options:
    - "QueryBuilder queries run faster in the database"
    - "It allows building a query conditionally (adding clauses only when needed) in a safe, fluent, object-oriented way"
    - "Raw DQL strings are not supported by Doctrine at all"
    - "QueryBuilder automatically caches every query"
  answer: 1
  explanation: "QueryBuilder's main advantage is composing a query step by step, including conditionally, which is awkward and risky to do with string concatenation."
- question: "In the countByStatus() example, why is getArrayResult() used instead of getResult()?"
  options:
    - "getArrayResult() is required whenever groupBy() is used"
    - "The query selects aggregated data (status + COUNT), which doesn't correspond to full Task entities, so plain arrays are returned instead of objects"
    - "getResult() does not support parameters"
    - "There is no functional difference between the two"
  answer: 1
  explanation: "Because the SELECT produces aggregated, non-entity data (a status label and a count), getArrayResult() returns plain arrays instead of trying to hydrate incomplete Task objects."
- question: "What must always be used to insert a dynamic, user-provided value into a QueryBuilder condition?"
  options:
    - "String concatenation directly into the WHERE clause"
    - "setParameter() with a named placeholder"
    - "PHP's addslashes() function"
    - "It doesn't matter, Doctrine escapes concatenated strings automatically"
  answer: 1
  explanation: "setParameter() binds values safely as query parameters; concatenating raw values into the query string reintroduces SQL/DQL injection risk."
```
