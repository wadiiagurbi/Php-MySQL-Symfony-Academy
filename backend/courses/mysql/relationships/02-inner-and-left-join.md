---
slug: inner-and-left-join
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: relationships
chapterTitle: "Chapter 3 — Table Relationships"
chapterOrder: 3
title: "INNER JOIN and LEFT JOIN"
difficulty: intermediate
order: 2
estimatedMinutes: 13
tags: [mysql, joins, relationships]
---

## Learning Objective

By the end of this lesson, you will be able to combine data from two related tables in a single query using `INNER JOIN` and `LEFT JOIN`, and understand precisely how the two differ.

## Explanation

In the previous lesson, we split our data across multiple related tables (`products`, `categories`, `orders`, `order_items`) on purpose, to avoid duplication. But when we actually want to *display* something to a user, we usually need information from several of these tables combined — for example, "show me each product's name **and** its category's name," even though `name` lives in `products` and the category name lives in `categories`. A `JOIN` is how you combine rows from two (or more) tables based on a matching key, in a single query.

**`INNER JOIN`** returns only the rows that have a match in *both* tables:

```sql
SELECT products.name AS product_name, categories.name AS category_name
FROM products
INNER JOIN categories ON products.category_id = categories.id;
```

This reads as: "for every row in `products`, find the row in `categories` whose `id` matches this product's `category_id`, and combine them into one result row." The `ON` clause defines exactly how the two tables are matched up. If a product somehow had a `category_id` that didn't match any category (which shouldn't happen if we set up the foreign key constraint correctly), that product would simply be **left out** of an `INNER JOIN` result — hence "inner," it only keeps the overlapping part.

**`LEFT JOIN`** (short for "LEFT OUTER JOIN") returns *every* row from the left (first-listed) table, whether or not it has a match in the right table. When there's no match, the columns from the right table simply come back as `NULL`.

```sql
SELECT categories.name AS category_name, products.name AS product_name
FROM categories
LEFT JOIN products ON products.category_id = categories.id;
```

This returns every category — including ones that currently have **zero** products — with `product_name` showing as `NULL` for any category that has no products at all. An `INNER JOIN` here would have silently hidden empty categories from the result entirely, which is often not what you want for a report like "show me all categories."

The rule of thumb: use `INNER JOIN` when you only care about rows that definitely have a match on both sides; use `LEFT JOIN` when you want to keep every row from your "main" table even if the related table has nothing to offer for some of them.

## Why It Matters

Almost every meaningful screen in a real application needs data from more than one table at once: an order confirmation page needs the customer's name (from `users`) and the product names (from `products`), not just raw IDs. Writing this by hand as several separate queries and stitching the results together in PHP is slow and error-prone compared to letting MySQL do it in one `JOIN`, which it is specifically optimized for.

The `INNER` vs `LEFT` distinction also matters for correctness in reports: a "which categories have no products yet" report, or a "which customers have never placed an order" report, is *only* possible with a `LEFT JOIN` — an `INNER JOIN` would exclude exactly the rows you're trying to find. Choosing the wrong join type is a common, subtle bug that silently drops real data from a report.

## Example

The store wants two reports: (1) a catalog page listing every product alongside its category name, and (2) a report showing every category, including any that currently have no products at all, so the manager knows what needs new inventory.

## Code

```sql
USE online_store;

-- Report 1: every product with its category name (INNER JOIN).
SELECT products.name AS product_name, categories.name AS category_name
FROM products
INNER JOIN categories ON products.category_id = categories.id;

-- Report 2: every category, including empty ones (LEFT JOIN).
SELECT categories.name AS category_name, products.name AS product_name
FROM categories
LEFT JOIN products ON products.category_id = categories.id
WHERE products.id IS NULL;
```

## Code Explanation

- `FROM products INNER JOIN categories ON products.category_id = categories.id` combines each product with its matching category row, based on the foreign key relationship set up in Chapter 1. Every product has a valid `category_id` (enforced by the foreign key constraint), so no products are lost here.
- `SELECT products.name AS product_name, categories.name AS category_name` qualifies each column with its table name (`products.name`, `categories.name`) because both tables happen to have a column called `name` — without qualifying, MySQL wouldn't know which `name` you mean, and would raise an "ambiguous column" error.
- `FROM categories LEFT JOIN products ON ...` keeps every row from `categories` (the left/first table), attaching matching `products` rows where they exist, and filling in `NULL` for products columns where a category has no products.
- `WHERE products.id IS NULL` in Report 2 filters the joined result down to only the categories that had **no** matching product row at all — remember from Chapter 2 that `NULL` requires `IS NULL`, never `= NULL`. This is a classic, powerful pattern: `LEFT JOIN` + `WHERE right_table.id IS NULL` finds "rows in the left table with no match in the right table."

## Common Mistakes

- **Using `INNER JOIN` when you actually need to include unmatched rows.** This silently drops rows that have no partner — for example, hiding categories with zero products, or customers with zero orders — which can make a report look "complete" when it's actually missing data.
- **Forgetting to qualify column names that exist in both tables.** `SELECT name FROM products INNER JOIN categories ON ...` errors with "column 'name' in field list is ambiguous" because both tables have a `name` column.
- **Mixing up the "left" table.** In `A LEFT JOIN B`, `A` is guaranteed to keep all its rows; `B` may have `NULL`s. Writing the tables in the wrong order changes the entire meaning of the query.
- **Forgetting the `ON` clause entirely**, which (in older, comma-style join syntax) can accidentally produce a "cross join" — pairing every row in one table with every row in the other, producing far more rows than intended.

## Best Practices

- Always qualify column names with the table name (or a short alias, like `p.name`) whenever a column name could exist in more than one joined table — it prevents ambiguity errors and makes queries easier to read.
- Choose `INNER JOIN` vs `LEFT JOIN` deliberately based on the real question you're answering: "only show me matched pairs" vs. "show me everything from this table, matched or not."
- When you specifically want to find "rows with no match" (like categories with no products), remember the `LEFT JOIN ... WHERE right_table.column IS NULL` pattern — it's one of the most useful idioms in SQL.

## Exercise

Write a query that lists every user's `username` alongside the `order_date` of any orders they've placed — but make sure customers who have never placed an order still appear in the result (with `NULL` for the order date).

## Solution

```sql
SELECT users.username, orders.order_date
FROM users
LEFT JOIN orders ON orders.user_id = users.id;
```

Using `LEFT JOIN` with `users` as the left table guarantees every user appears in the result at least once, even if they have no matching row in `orders` — in which case `orders.order_date` simply shows as `NULL` for that user.

## Mini Quiz

```yaml
- question: "What is the key difference between INNER JOIN and LEFT JOIN?"
  options:
    - "There is no real difference; they always return identical results"
    - "INNER JOIN only returns rows with a match in both tables; LEFT JOIN returns all rows from the left table even without a match"
    - "LEFT JOIN only works on the left half of a table"
    - "INNER JOIN can only be used with two tables that have no foreign key"
  answer: 1
  explanation: "INNER JOIN keeps only rows that match in both tables, while LEFT JOIN keeps every row from the left table, filling in NULL for the right table's columns when there's no match."

- question: "Which pattern finds categories that currently have zero products?"
  options:
    - "INNER JOIN products ON ... WHERE products.id IS NULL"
    - "LEFT JOIN products ON ... WHERE products.id IS NULL"
    - "INNER JOIN products ON ... ORDER BY products.id"
    - "LEFT JOIN products ON ... WHERE products.id IS NOT NULL"
  answer: 1
  explanation: "A LEFT JOIN keeps every category even without a matching product, and filtering WHERE products.id IS NULL isolates exactly the categories that had no match at all."

- question: "Why must you qualify a column name like name with its table (e.g., products.name) in a JOIN query?"
  options:
    - "It is only a style preference with no functional effect"
    - "Because if both joined tables have a column with that name, MySQL cannot tell which one you mean without qualification"
    - "MySQL requires all column names to include a table prefix, always"
    - "Qualifying column names makes joins run in a different order"
  answer: 1
  explanation: "When both tables in a join have a column with the same name, referencing it unqualified is ambiguous and causes an error; the table name (or alias) resolves the ambiguity."
```
