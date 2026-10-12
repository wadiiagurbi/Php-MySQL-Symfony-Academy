---
slug: like-in-between-and-null
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "LIKE, IN, BETWEEN, and NULL"
difficulty: beginner
order: 4
estimatedMinutes: 10
tags: [mysql, sql, filtering]
---

## Learning Objective

By the end of this lesson, you will be able to write more expressive `WHERE` conditions using `LIKE` for partial text matches, `IN` for matching a list of values, `BETWEEN` for ranges, and understand how MySQL's special `NULL` value behaves differently from an empty string or zero.

## Explanation

The previous lessons used simple comparisons like `=` and `<`. Real filtering needs are often more flexible than that. MySQL gives us several extra tools for `WHERE` clauses.

**`LIKE`** matches text against a pattern using wildcards. `%` matches any sequence of characters (including none), and `_` matches exactly one character.

```sql
SELECT name FROM products WHERE name LIKE 'Coffee%';   -- starts with "Coffee"
SELECT name FROM products WHERE name LIKE '%mug%';      -- contains "mug" anywhere
```

**`IN`** checks if a value matches any item in a list, which is much shorter than writing several `OR` conditions:

```sql
SELECT name FROM products WHERE category_id IN (1, 3, 5);
-- equivalent to: WHERE category_id = 1 OR category_id = 3 OR category_id = 5
```

**`BETWEEN`** checks if a value falls within a range (inclusive of both ends):

```sql
SELECT name FROM products WHERE price BETWEEN 10 AND 50;
-- equivalent to: WHERE price >= 10 AND price <= 50
```

**`NULL`** is a special marker in SQL meaning "this value is unknown or missing" — it is *not* the same as an empty string `''` or the number `0`. A product with no description yet might have `description = NULL`, meaning "we simply don't have this information," which is conceptually different from `description = ''` (we know it's blank on purpose).

Because `NULL` represents "unknown," you cannot check for it using `=`. `WHERE description = NULL` will never match anything, even rows that are actually `NULL` — this surprises almost every SQL beginner. Instead, use the special operators `IS NULL` and `IS NOT NULL`:

```sql
SELECT name FROM products WHERE description IS NULL;
SELECT name FROM products WHERE description IS NOT NULL;
```

## Why It Matters

These tools map directly onto extremely common real features: a search box ("find products containing this text") needs `LIKE`; a filter with checkboxes ("show me products in these 3 categories") needs `IN`; a price-range slider needs `BETWEEN`; and checking whether optional data (like a middle name, a discount code, or a shipping note) was ever provided needs `IS NULL`/`IS NOT NULL`.

Misunderstanding `NULL` is one of the most common sources of subtle, hard-to-find bugs for SQL beginners — a query that silently returns fewer rows than expected, or a report that mysteriously excludes some customers, is very often a `NULL`-related mistake. Understanding it precisely now will save you hours of confusion later.

## Example

The store wants a search feature (matching product names containing a keyword), a way to filter by several specific categories at once, a price-range filter, and a report of any products still missing a description.

## Code

```sql
USE online_store;

-- Search: product names containing "lamp" (case-insensitive by default in MySQL).
SELECT name FROM products WHERE name LIKE '%lamp%';

-- Filter: products in the Electronics (1) or Books (2) categories.
SELECT name, category_id FROM products WHERE category_id IN (1, 2);

-- Range: products priced between $10 and $50, inclusive.
SELECT name, price FROM products WHERE price BETWEEN 10 AND 50;

-- Data quality check: products with no description recorded yet.
SELECT name FROM products WHERE description IS NULL;
```

## Code Explanation

- `WHERE name LIKE '%lamp%'` matches any product name containing "lamp" anywhere in the string, because `%` on both sides means "any characters, then 'lamp', then any characters." MySQL's default comparisons are usually case-insensitive for text like this, so "Desk Lamp" and "desk lamp" both match.
- `WHERE category_id IN (1, 2)` returns rows where `category_id` equals `1` OR `2` — much more compact than writing out both conditions with `OR`.
- `WHERE price BETWEEN 10 AND 50` includes products priced at exactly `10` or exactly `50`, as well as everything in between — `BETWEEN` is inclusive on both ends.
- `WHERE description IS NULL` finds rows where no description has ever been set — using `=NULL` here would incorrectly return zero rows even if such products exist, because `NULL` never equals anything, including itself.

## Common Mistakes

- **Writing `WHERE description = NULL`.** This is a very common beginner bug: it always returns zero rows, even when matching rows exist, because `NULL` is never "equal" to anything under SQL's three-valued logic. Always use `IS NULL` / `IS NOT NULL`.
- **Forgetting `%` on both sides for a "contains" search.** `LIKE 'lamp'` (no `%`) only matches the exact string "lamp," not "Desk Lamp." You need `LIKE '%lamp%'` for a true "contains" search.
- **Assuming `BETWEEN` is exclusive.** `price BETWEEN 10 AND 50` includes both `10` and `50` exactly — it is not the same as `price > 10 AND price < 50`.
- **Using many `OR`s instead of `IN`.** `WHERE category_id = 1 OR category_id = 2 OR category_id = 3` works but is harder to read and maintain than `WHERE category_id IN (1, 2, 3)`.

## Best Practices

- Use `IN` instead of long `OR` chains whenever you're comparing the same column against multiple possible values — it's clearer and less error-prone.
- Be deliberate about `NULL` versus empty string when designing tables: decide whether "we don't know this value" (`NULL`) is meaningfully different from "this value is intentionally blank" (`''`) for each column, and be consistent.
- Be cautious with a leading `%` in `LIKE '%something'` on large tables — patterns that start with `%` generally cannot use a standard index efficiently and can be slow on big tables; a trailing-only pattern like `LIKE 'something%'` performs much better.

## Exercise

Write a query that returns the `name` and `price` of every product priced strictly between $20 and $100 (inclusive), belonging to category `2` or category `4`, whose name contains the word "Pro."

## Solution

```sql
SELECT name, price
FROM products
WHERE price BETWEEN 20 AND 100
  AND category_id IN (2, 4)
  AND name LIKE '%Pro%';
```

This combines all three tools: `BETWEEN` for the inclusive price range, `IN` for the two allowed categories, and `LIKE '%Pro%'` to match "Pro" appearing anywhere in the product name.

## Mini Quiz

```yaml
- question: "Which condition correctly finds all products with no description set?"
  options:
    - "WHERE description = NULL"
    - "WHERE description IS NULL"
    - "WHERE description = ''"
    - "WHERE description != NULL"
  answer: 1
  explanation: "NULL represents an unknown value and can never be compared with =. IS NULL is the correct way to test for it."

- question: "What does WHERE category_id IN (1, 2, 3) mean?"
  options:
    - "category_id must equal all three values at once"
    - "category_id must equal 1, 2, or 3"
    - "category_id must be between 1 and 3"
    - "category_id must not equal 1, 2, or 3"
  answer: 1
  explanation: "IN checks whether the column's value matches any one of the listed values — it is shorthand for chained OR conditions."

- question: "Is price BETWEEN 10 AND 50 inclusive or exclusive of 10 and 50?"
  options:
    - "Exclusive of both 10 and 50"
    - "Inclusive of both 10 and 50"
    - "Inclusive of 10 only"
    - "Inclusive of 50 only"
  answer: 1
  explanation: "BETWEEN in SQL is inclusive on both ends, equivalent to >= 10 AND <= 50."
```
