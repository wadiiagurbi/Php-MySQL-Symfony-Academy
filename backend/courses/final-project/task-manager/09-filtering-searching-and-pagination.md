---
slug: filtering-searching-and-pagination
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Filtering, Searching, and Pagination"
difficulty: advanced
order: 9
estimatedMinutes: 14
tags: [final-project, task-manager, querybuilder, pagination]
---

## Learning Objective

By the end of this lesson, you will be able to trace how a single `GET /api/tasks` request with query-string parameters becomes a filtered, searched, sorted, and paginated Doctrine `QueryBuilder` query, and explain the safety measures that keep every part of that query bounded and owner-scoped.

## Explanation

`GET /api/tasks` supports six independent query-string options: `status`, `priority`, `categoryId`, `search`, `sortBy`/`sortDir`, and `page`/`perPage`. Cramming all of that as loose method arguments would be unreadable, so the project groups them into a single typed object, `TaskListQuery`, built directly from the request:

```php
final class TaskListQuery
{
    public function __construct(
        public readonly ?TaskStatus $status = null,
        public readonly ?TaskPriority $priority = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $search = null,
        public readonly string $sortBy = 'createdAt',
        public readonly string $sortDir = 'DESC',
        public readonly int $page = 1,
        public readonly int $perPage = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        return new self(
            status: $status ? TaskStatus::tryFrom($status) : null,
            priority: $priority ? TaskPriority::tryFrom($priority) : null,
            categoryId: $request->query->has('categoryId') ? (int) $request->query->get('categoryId') : null,
            search: $request->query->get('search') ?: null,
            sortBy: $request->query->get('sortBy', 'createdAt'),
            sortDir: $request->query->get('sortDir', 'DESC'),
            page: max(1, (int) $request->query->get('page', 1)),
            perPage: min(50, max(1, (int) $request->query->get('perPage', 10))),
        );
    }
}
```

Two defensive details matter here. First, `TaskStatus::tryFrom($status)` — not `from()` — is used, which returns `null` for an invalid value instead of throwing; an unrecognized `?status=whatever` is silently ignored rather than crashing the request. Second, `perPage` is clamped with `min(50, max(1, ...))`: no client can ever request more than 50 rows in a single page (protecting the server from an accidentally-or-maliciously huge query) or fewer than 1.

`TaskRepository::findByQuery` then builds the actual query, adding each filter only when it applies:

```php
public function findByQuery(User $owner, TaskListQuery $query): array
{
    $qb = $this->createQueryBuilder('t')
        ->andWhere('t.owner = :owner')
        ->setParameter('owner', $owner);

    if (null !== $query->status) {
        $qb->andWhere('t.status = :status')->setParameter('status', $query->status);
    }
    // ...priority, categoryId follow the same pattern

    if (null !== $query->search && '' !== $query->search) {
        $qb->andWhere('LOWER(t.title) LIKE :search OR LOWER(t.description) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($query->search).'%');
    }

    $total = (int) (clone $qb)->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

    $sortField = in_array($query->sortBy, self::SORTABLE_FIELDS, true) ? $query->sortBy : 'createdAt';
    $sortDir = 'ASC' === strtoupper($query->sortDir) ? 'ASC' : 'DESC';

    $qb->orderBy('t.'.$sortField, $sortDir)
        ->setFirstResult(($query->page - 1) * $query->perPage)
        ->setMaxResults($query->perPage);

    return ['items' => $qb->getQuery()->getResult(), 'total' => $total];
}
```

`t.owner = :owner` is the very first clause added, before any optional filter — every single query this method can ever build is scoped to the current user, no matter what else the client asks for. That's the same ownership guarantee from earlier lessons, enforced at the query level instead of after the fact.

## Why It Matters

This is the standard shape of a real, production filterable-list endpoint: a typed query object parsed once from the request, a repository method that builds the query incrementally and defensively, and a total count computed *before* pagination is applied (so `total` reflects the full filtered result set, not just the current page). Every safety measure here — `tryFrom` over `from`, clamped `perPage`, a whitelist for `sortBy`, `owner` always applied first — is there because query parameters are attacker-controlled input, and a list endpoint is exactly the kind of feature that quietly turns into a SQL injection or denial-of-service vector if built carelessly.

## Example

`GET /api/tasks?status=DONE&search=backend&sortBy=priority&sortDir=asc&page=2&perPage=5` becomes, conceptually:

```sql
SELECT t.* FROM task t
WHERE t.owner_id = :owner
  AND t.status = 'DONE'
  AND (LOWER(t.title) LIKE '%backend%' OR LOWER(t.description) LIKE '%backend%')
ORDER BY t.priority ASC
LIMIT 5 OFFSET 5
```

Note `sortBy=priority` is accepted because `'priority'` is in `SORTABLE_FIELDS`; a request with `sortBy=owner` or `sortBy=password` would silently fall back to `'createdAt'` instead of sorting by (or leaking information through timing about) an arbitrary column.

## Code

```php
<?php
// Adapted from src/Repository/TaskRepository.php

private const SORTABLE_FIELDS = ['createdAt', 'updatedAt', 'dueDate', 'priority', 'title'];

public function findByQuery(User $owner, TaskListQuery $query): array
{
    $qb = $this->createQueryBuilder('t')
        ->andWhere('t.owner = :owner')
        ->setParameter('owner', $owner);

    if (null !== $query->categoryId) {
        $qb->andWhere('t.category = :categoryId')->setParameter('categoryId', $query->categoryId);
    }

    $total = (int) (clone $qb)
        ->select('COUNT(t.id)')
        ->getQuery()
        ->getSingleScalarResult();

    $sortField = in_array($query->sortBy, self::SORTABLE_FIELDS, true) ? $query->sortBy : 'createdAt';
    $sortDir = 'ASC' === strtoupper($query->sortDir) ? 'ASC' : 'DESC';

    $qb->orderBy('t.'.$sortField, $sortDir)
        ->setFirstResult(($query->page - 1) * $query->perPage)
        ->setMaxResults($query->perPage);

    return ['items' => $qb->getQuery()->getResult(), 'total' => $total];
}
```

## Code Explanation

`(clone $qb)->select('COUNT(t.id)')` is the key trick for correct pagination: cloning the query builder *before* adding `orderBy`/`setFirstResult`/`setMaxResults` means the count query includes every `WHERE` filter applied so far but ignores sorting and paging entirely, giving an accurate "how many results total" figure. `SORTABLE_FIELDS` is a private whitelist, and `in_array($query->sortBy, self::SORTABLE_FIELDS, true)` (note the strict `true` third argument) is what prevents a client from sorting by an arbitrary, unintended column — string interpolation into `orderBy('t.'.$sortField, ...)` would otherwise be a real risk if `$sortField` weren't checked against a fixed list first. `setFirstResult` / `setMaxResults` translate `page`/`perPage` into SQL's `OFFSET`/`LIMIT`.

## Common Mistakes

- Computing `total` *after* `setFirstResult`/`setMaxResults` have been applied to the same query builder instance — that would count only the current page's rows, not the full filtered set.
- Interpolating `$query->sortBy` directly into `orderBy()` without validating it against a whitelist first — even though Doctrine's QueryBuilder parameterizes values, column/field names in `orderBy` are not parameters and must be validated separately.
- Leaving `perPage` unclamped, letting a client request `perPage=999999` and force the database to materialize and transfer a huge result set in one request.
- Forgetting `t.owner = :owner` as the very first condition — every other filter is optional and conditionally added, but ownership scoping must never be optional.

## Best Practices

- Always clone a query builder before diverging it for a count query, rather than building the count and the paged query independently (which risks the two falling out of sync as filters evolve).
- Whitelist any client-controlled value that ends up as a column/field name, direction, or table name — never validate those with the same mechanism (parameter binding) used for values.
- Clamp pagination bounds server-side regardless of what a client requests; never trust a `perPage` value blindly.
- Parse and validate query parameters into a typed object once (`TaskListQuery::fromRequest`), rather than scattering `$request->query->get(...)` calls through the repository or service.

## Exercise

Add support for filtering by a due-date range: `?dueBefore=2026-12-31&dueAfter=2026-01-01`. What would you add to `TaskListQuery` and `TaskRepository::findByQuery`, and how would you validate the incoming date strings safely?

## Solution

Add `public readonly ?\DateTimeImmutable $dueBefore = null` and `?\DateTimeImmutable $dueAfter = null` to `TaskListQuery`. In `fromRequest`, parse them defensively — e.g. `$dueBefore = $request->query->get('dueBefore'); $dueBefore = $dueBefore ? \DateTimeImmutable::createFromFormat('Y-m-d', $dueBefore) ?: null : null;` — so a malformed date string becomes `null` (silently ignored) rather than throwing. In `findByQuery`, add two more conditional `andWhere` blocks following the exact same pattern as `status`/`priority`: `if (null !== $query->dueBefore) { $qb->andWhere('t.dueDate <= :dueBefore')->setParameter('dueBefore', $query->dueBefore); }`, and the mirror for `dueAfter`. No change to the count-query trick is needed, since these are just more optional `WHERE` clauses added before the count is cloned off.

## Mini Quiz

```yaml
- question: "Why is the task count computed from a clone of the query builder rather than the same instance used for the paged results?"
  options:
    - "Doctrine requires a new QueryBuilder instance for every query"
    - "So the count reflects all matching rows across every page, unaffected by orderBy/LIMIT/OFFSET applied afterward"
    - "Cloning improves query performance"
    - "The original query builder cannot be reused after calling getResult()"
  answer: 1
  explanation: "Cloning before applying sort and pagination means the count query keeps the same WHERE filters but ignores LIMIT/OFFSET, giving the correct total across the whole filtered set, not just the current page."

- question: "Why does the repository check $query->sortBy against a SORTABLE_FIELDS whitelist instead of using it directly in orderBy()?"
  options:
    - "Doctrine automatically rejects invalid field names"
    - "To prevent a client from sorting by, or probing information about, an arbitrary/unintended column"
    - "orderBy() only accepts numeric arguments"
    - "It's required to avoid a Doctrine caching bug"
  answer: 1
  explanation: "Column/field names used in orderBy are not parameterized like values are, so validating sortBy against a fixed whitelist prevents unintended or unsafe fields from being used to sort."

- question: "Why does TaskListQuery::fromRequest use TaskStatus::tryFrom() instead of TaskStatus::from() when parsing the status query parameter?"
  options:
    - "tryFrom() is faster than from()"
    - "from() does not exist on backed enums"
    - "tryFrom() returns null for an invalid value instead of throwing, letting an unrecognized filter be silently ignored"
    - "tryFrom() automatically validates against the database"
  answer: 2
  explanation: "Using tryFrom() means an invalid or unexpected status string in the query string results in null (filter ignored) rather than an uncaught ValueError crashing the request."
```
