---
slug: group-by-having
track: mysql
trackTitle: "MySQL & SQL"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "GROUP BY and HAVING in Depth"
difficulty: beginner
order: 7
estimatedMinutes: 11
tags: [mysql, sql, aggregation]
---

## Learning Objective

By the end of this lesson, you will be able to summarize rows per category with `GROUP BY`, compute aggregates like `COUNT` and `SUM`, and filter groups with `HAVING`.

## Explanation

An **aggregate function** collapses many values into one: `COUNT()` counts rows, `SUM()` adds a column, `AVG()` averages it, and `MIN()`/`MAX()` find extremes. Without grouping, aggregates describe the whole table — one number for everything.

`GROUP BY` splits rows into buckets first (one bucket per customer, per day, per status) and then computes one aggregate row per bucket. A **group** here simply means "all rows sharing the same value in the grouped column."

`HAVING` filters those finished groups, much like `WHERE` filters raw rows before grouping. Use `WHERE` to exclude rows early (failed payments) and `HAVING` to keep only interesting groups afterward (customers with more than three orders).

## Why It Matters

Dashboards and reports are built from grouped aggregates: revenue per month, orders per customer, signups per country. Knowing exactly where `WHERE` stops and `HAVING` starts is what separates a correct report from a subtly wrong one.

## Example

An online store wants, per customer, the number of orders and total spent — but only for customers with at least two orders, ranked by biggest spender first.

## Code

```sql
SELECT customer_id, COUNT(*) AS order_count, SUM(total) AS revenue
FROM orders
GROUP BY customer_id
HAVING COUNT(*) >= 2
ORDER BY revenue DESC;
```

## Code Explanation

- `COUNT(*) AS order_count` counts the rows in each customer bucket, and `SUM(total) AS revenue` adds their order totals; `AS` gives each result a readable name.
- `GROUP BY customer_id` creates one result row per customer instead of one grand total for the table.
- `HAVING COUNT(*) >= 2` keeps only groups with two or more orders; it must come after grouping because it tests per-group results.
- `ORDER BY revenue DESC` sorts the surviving groups from highest to lowest spender using the aliased aggregate.

## Common Mistakes

- **Using `WHERE` on an aggregate.** `WHERE COUNT(*) >= 2` is invalid because aggregates do not exist until after grouping; that filter belongs in `HAVING`.
- **Selecting ungrouped, non-aggregated columns.** Every selected column must either appear in `GROUP BY` or sit inside an aggregate, or the row's value is ambiguous.
- **Forgetting `NULL` groups.** Rows with a `NULL` grouping key form their own group, which surprises beginners scanning totals.

## Best Practices

- Filter raw rows with `WHERE` first for speed, then refine groups with `HAVING`.
- Alias every aggregate (`AS order_count`) so application code and `ORDER BY` stay readable.
- Test grouped queries against small hand-counted samples before trusting dashboard numbers.

## Exercise

From table `orders (id, customer_id, total, status)`, report per-customer order counts and revenue considering only `status = 'paid'` rows, keeping customers whose revenue exceeds 100.

## Solution

```sql
SELECT customer_id, COUNT(*) AS order_count, SUM(total) AS revenue
FROM orders
WHERE status = 'paid'
GROUP BY customer_id
HAVING SUM(total) > 100
ORDER BY revenue DESC;
```

`WHERE` discards unpaid rows before bucketing, grouping summarizes each customer, and `HAVING` keeps only the high-value groups.

## Mini Quiz

```yaml
- question: "What does GROUP BY do?"
  options:
    - "It sorts rows alphabetically"
    - "It buckets rows sharing a value so aggregates compute per bucket"
    - "It deletes duplicate tables"
    - "It creates a database backup"
  answer: 1
  explanation: "GROUP BY partitions rows into one bucket per distinct key, and each aggregate then produces one value per bucket."
- question: "When should HAVING be used instead of WHERE?"
  options:
    - "To filter raw rows before grouping"
    - "To filter finished groups on aggregate results after grouping"
    - "To join two tables together"
    - "To change the database password"
  answer: 1
  explanation: "WHERE runs before grouping on individual rows, while HAVING runs after grouping on per-group aggregates."
- question: "What does SELECT customer_id, COUNT(*) FROM orders GROUP BY customer_id return?"
  options:
    - "One row per order in the table"
    - "One row per customer with that customer's order count"
    - "A single grand total for all orders"
    - "An empty result always"
  answer: 1
  explanation: "Grouping by customer_id collapses each customer's rows into one summary row holding that customer's count."
```
