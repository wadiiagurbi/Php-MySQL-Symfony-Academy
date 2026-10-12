---
slug: joins-in-depth
track: mysql
trackTitle: "MySQL & SQL"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "JOINs in Depth"
difficulty: beginner
order: 6
estimatedMinutes: 12
tags: [mysql, sql, joins]
---

## Learning Objective

By the end of this lesson, you will be able to combine rows from two tables with `INNER JOIN` and `LEFT JOIN` and explain how each handles non-matching rows.

## Explanation

Real databases split data across tables: `customers` holds people, `orders` holds purchases, linked by a shared key such as `orders.customer_id`. A **JOIN** brings matching rows from two tables together into one result based on that link, expressed in the `ON` clause.

An **INNER JOIN** returns only rows with a match on both sides — customers who actually placed orders. A **LEFT JOIN** returns every row from the left (first) table plus matching right-table rows, filling missing matches with `NULL`, a special marker meaning "no value here." That makes LEFT JOIN the tool for questions like "all customers and their orders, if any."

Table **aliases** (short names like `c` for `customers`) keep such queries readable: `FROM customers c JOIN orders o ON o.customer_id = c.id`.

## Why It Matters

Almost no interesting question lives in a single table: order lists need customer names, product pages need category titles, invoices need line items plus prices. JOINs turn a normalized, well-organized database back into the combined views applications actually display.

## Example

An online store wants two reports: customers with their orders (only buyers), and all customers with order totals where non-buyers show `NULL`.

## Code

```sql
SELECT c.name, o.id AS order_id, o.total
FROM customers c
INNER JOIN orders o ON o.customer_id = c.id
ORDER BY c.name;
```

## Code Explanation

- `FROM customers c` reads the customers table under the short alias `c` for the rest of the query.
- `INNER JOIN orders o ON o.customer_id = c.id` attaches each order whose foreign key equals the customer's primary key.
- `SELECT c.name, o.id AS order_id, o.total` picks the customer name plus each order's id (renamed for clarity) and total.
- `ORDER BY c.name` sorts the combined rows alphabetically; swapping `INNER` for `LEFT` would additionally list customers with `NULL` order columns.

## Common Mistakes

- **Forgetting the `ON` clause.** A JOIN without a condition pairs every row with every row (a runaway result called a cross product).
- **Joining on the wrong columns.** The condition must link the foreign key to the referenced primary key, not two unrelated fields.
- **Expecting `NULL` from an INNER JOIN.** Inner joins drop non-matches silently; only outer joins like LEFT JOIN preserve them as `NULL`.

## Best Practices

- Alias tables consistently (`c`, `o`) and prefix every column with its alias in multi-table queries.
- Choose INNER when you want only matches and LEFT when you must keep all left-side rows.
- Filter joined results with `WHERE` after the JOIN and verify row counts against expectations.

## Exercise

Using tables `authors (id, name)` and `books (id, author_id, title)`, list every author with their book titles, including authors who have written no books yet. Which JOIN do you need?

## Solution

```sql
SELECT a.name, b.title
FROM authors a
LEFT JOIN books b ON b.author_id = a.id
ORDER BY a.name;
```

A LEFT JOIN keeps every author row, pairing bookless authors with a `NULL` title, which is exactly the requested report.

## Mini Quiz

```yaml
- question: "What does INNER JOIN return?"
  options:
    - "Every row from both tables regardless of matches"
    - "Only rows with a matching partner on both sides"
    - "Only rows with no match anywhere"
    - "A backup of the database"
  answer: 1
  explanation: "INNER JOIN keeps pairs whose ON condition holds and drops everything without a partner."
- question: "How does LEFT JOIN handle left-side rows with no match?"
  options:
    - "It deletes them permanently"
    - "It keeps them, filling right-side columns with NULL"
    - "It duplicates them ten times"
    - "It raises a syntax error"
  answer: 1
  explanation: "LEFT JOIN preserves every left-table row, using NULL markers where no right-table partner exists."
- question: "What is the ON clause in a JOIN?"
  options:
    - "The sort order of the results"
    - "The condition linking the two tables, usually foreign key equals primary key"
    - "The database password"
    - "The limit on returned rows"
  answer: 1
  explanation: "ON states how rows pair up across tables; without it the database cannot know which rows belong together."
```
