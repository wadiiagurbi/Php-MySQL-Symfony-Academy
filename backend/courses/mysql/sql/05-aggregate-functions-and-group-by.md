---
slug: aggregate-functions-and-group-by
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "Aggregate Functions and GROUP BY"
difficulty: beginner
order: 5
estimatedMinutes: 11
tags: [mysql, sql, aggregation]
---

## Learning Objective

By the end of this lesson, you will be able to summarize data using aggregate functions (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`) and group rows into categories with `GROUP BY`, including filtering groups with `HAVING`.

## Explanation

So far every query has returned individual rows. Sometimes, though, you want a *summary* instead — "how many products do we have?", "what's the total value of all orders?", "what's the average price?" These summary calculations are called **aggregate functions**:

- `COUNT(*)` — counts how many rows match.
- `SUM(column)` — adds up all the values in a column.
- `AVG(column)` — calculates the average of a column's values.
- `MIN(column)` / `MAX(column)` — finds the smallest / largest value.

```sql
SELECT COUNT(*) FROM products;          -- total number of products
SELECT AVG(price) FROM products;        -- average product price
SELECT MAX(price) FROM products;        -- most expensive product's price
```

These give you one summary number across the *entire* table. But often you want a summary *per group* — "how many products are in each category?" rather than one grand total. That's what **`GROUP BY`** does: it splits the rows into groups based on a column's value, then applies the aggregate function separately within each group.

```sql
SELECT category_id, COUNT(*) AS product_count
FROM products
GROUP BY category_id;
```

This returns one row per distinct `category_id`, each showing how many products belong to it. The `AS product_count` part just gives the calculated column a friendly name in the result.

Once you're grouping, you sometimes want to filter the *groups themselves* based on the aggregate result — for example, "only show categories with more than 5 products." You can't use `WHERE` for this, because `WHERE` filters individual rows *before* grouping happens. Instead, you use **`HAVING`**, which filters groups *after* aggregation:

```sql
SELECT category_id, COUNT(*) AS product_count
FROM products
GROUP BY category_id
HAVING COUNT(*) > 5;
```

The correct clause order in a full query is: `SELECT ... FROM ... WHERE ... GROUP BY ... HAVING ... ORDER BY ... LIMIT ...`.

## Why It Matters

Dashboards, reports, and analytics screens are built almost entirely on aggregate queries: "total revenue this month," "average order value," "top-selling category," "how many new users signed up this week." Any time a business wants a number *about* its data rather than the raw data itself, you reach for `COUNT`, `SUM`, `AVG`, and `GROUP BY`.

This is also foundational for performance: computing these summaries inside the database (which is optimized for exactly this kind of bulk calculation) is drastically faster and less error-prone than pulling every row into PHP and looping over them by hand to add things up yourself.

## Example

The store's manager wants three reports: the total number of products, the number of products per category, and which categories have more than 2 products (to know which categories are well-stocked with variety).

## Code

```sql
USE online_store;

-- Total number of products in the store.
SELECT COUNT(*) AS total_products FROM products;

-- Number of products per category.
SELECT category_id, COUNT(*) AS product_count
FROM products
GROUP BY category_id;

-- Categories with more than 2 distinct products.
SELECT category_id, COUNT(*) AS product_count
FROM products
GROUP BY category_id
HAVING COUNT(*) > 2;
```

## Code Explanation

- `SELECT COUNT(*) AS total_products FROM products;` counts every row in the entire table and labels the result column `total_products` for readability — without `GROUP BY`, this treats the whole table as one single group.
- `SELECT category_id, COUNT(*) AS product_count FROM products GROUP BY category_id;` splits all rows into groups sharing the same `category_id`, then counts how many rows fall into each group — the result has exactly one row per distinct category.
- `HAVING COUNT(*) > 2` runs *after* the grouping and counting are complete, discarding any category group whose count is 2 or fewer — leaving only well-stocked categories in the result.
- Note that `category_id` appears in both `SELECT` and `GROUP BY` — any non-aggregated column you select alongside an aggregate function generally must also appear in `GROUP BY`, otherwise MySQL wouldn't know which single value to show for that column within each group.

## Common Mistakes

- **Using `WHERE` when you mean `HAVING`.** `WHERE COUNT(*) > 2` is invalid — `WHERE` filters rows before aggregation happens, and the aggregate result doesn't exist yet at that point. Use `HAVING` to filter based on an aggregate.
- **Selecting a non-grouped, non-aggregated column.** `SELECT category_id, name, COUNT(*) FROM products GROUP BY category_id;` is ambiguous — which product's `name` should represent the whole group? MySQL may allow this in some modes but it produces unpredictable results; only select columns that are either in `GROUP BY` or wrapped in an aggregate function.
- **Confusing `COUNT(*)` with `COUNT(column_name)`.** `COUNT(*)` counts all rows regardless of `NULL`s. `COUNT(column_name)` only counts rows where that specific column is *not* `NULL` — these can give different results if the column has missing values.
- **Forgetting that `AVG`/`SUM` ignore `NULL` values**, rather than treating them as zero — which is usually what you want, but worth knowing explicitly.

## Best Practices

- Always give aggregate result columns a clear alias with `AS` (e.g., `AS product_count`) so the output is self-explanatory rather than a cryptic `COUNT(*)` column header.
- Reach for `GROUP BY` any time you catch yourself thinking "I need a total/average/count *per* something" (per category, per customer, per day).
- Remember the clause order: `WHERE` filters rows first, `GROUP BY` groups what's left, then `HAVING` filters the resulting groups — this order reflects how MySQL actually processes the query.

## Exercise

Write a query against the `orders` table (columns: `id`, `order_date`, `user_id`) that shows each `user_id` along with how many orders they've placed, but only for customers who have placed more than 1 order.

## Solution

```sql
SELECT user_id, COUNT(*) AS order_count
FROM orders
GROUP BY user_id
HAVING COUNT(*) > 1;
```

This groups all orders by `user_id`, counts how many orders fall into each group, and then uses `HAVING` to keep only the customers whose order count exceeds 1 — repeat customers.

## Mini Quiz

```yaml
- question: "What does GROUP BY category_id do to the rows of a table?"
  options:
    - "It deletes rows with duplicate category_id values"
    - "It splits rows into groups sharing the same category_id, so aggregate functions apply separately within each group"
    - "It sorts rows by category_id, nothing else"
    - "It removes the category_id column from the result"
  answer: 1
  explanation: "GROUP BY groups rows that share the same value in the specified column(s), and any aggregate function in the SELECT is then calculated per group rather than across the whole table."

- question: "Why can't you write WHERE COUNT(*) > 5 to filter groups?"
  options:
    - "COUNT(*) is not valid SQL"
    - "WHERE filters individual rows before aggregation happens, so the aggregate result isn't available to it yet"
    - "You must always use LIMIT instead"
    - "WHERE only works with text columns"
  answer: 1
  explanation: "WHERE runs before grouping/aggregation, so aggregate results aren't available there. HAVING runs after grouping and can filter on aggregate results."

- question: "What is the difference between COUNT(*) and COUNT(description)?"
  options:
    - "There is no difference; they always return the same number"
    - "COUNT(*) counts all rows; COUNT(description) counts only rows where description is not NULL"
    - "COUNT(description) counts all rows; COUNT(*) counts only non-NULL rows"
    - "COUNT(description) is invalid SQL"
  answer: 1
  explanation: "COUNT(*) counts every row regardless of NULLs, while COUNT(column) only counts rows where that specific column has a non-NULL value."
```
