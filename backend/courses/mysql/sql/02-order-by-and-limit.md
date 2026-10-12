---
slug: order-by-and-limit
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "ORDER BY and LIMIT"
difficulty: beginner
order: 2
estimatedMinutes: 8
tags: [mysql, sql, sorting]
---

## Learning Objective

By the end of this lesson, you will be able to sort query results with `ORDER BY` (ascending or descending, and by multiple columns) and restrict how many rows come back using `LIMIT`.

## Explanation

By default, when you run a `SELECT`, MySQL does not promise any particular order for the rows it returns — it might come back in the order they were inserted, or it might not, especially as tables grow and MySQL optimizes how it reads data. If the order matters to you (and it usually does — think "newest orders first" or "cheapest products first"), you need to say so explicitly with `ORDER BY`.

```sql
SELECT name, price FROM products ORDER BY price;
```

This sorts the results by `price`, from smallest to largest — that's the default direction, called **ascending**, and you can make it explicit with `ASC`. To sort from largest to smallest instead (**descending**), add `DESC`:

```sql
SELECT name, price FROM products ORDER BY price DESC;
```

You can also sort by multiple columns. MySQL sorts by the first column, and only uses the second column to break ties where the first column's values are equal:

```sql
SELECT name, price FROM products ORDER BY category_id ASC, price DESC;
```

This groups products by category (ascending), and within each category, shows the most expensive products first.

Sometimes you only want a limited number of rows back — for example, "the 5 cheapest products" or "the 10 most recent orders." That's what `LIMIT` does:

```sql
SELECT name, price FROM products ORDER BY price ASC LIMIT 5;
```

This returns only the first 5 rows of the sorted result. `LIMIT` is almost always combined with `ORDER BY`, because "the first 5 rows" is meaningless unless you've defined what order they're in.

You can also skip a number of rows before starting to return results, using `LIMIT offset, count` (or the equivalent `LIMIT count OFFSET offset`) — this is the basis of **pagination**, showing "page 2" of a list:

```sql
SELECT name, price FROM products ORDER BY price ASC LIMIT 5, 5; -- skip 5, then take 5 (page 2)
```

## Why It Matters

Virtually every list a user sees in a real application is sorted and limited: "newest posts first," "top 10 best-selling products," "your 20 most recent orders." Getting the sort order right is a functional requirement, not a cosmetic detail — showing orders in a random or wrong order can genuinely confuse or mislead users (imagine a bank statement not sorted by date!).

`LIMIT` is also a critical performance and usability tool. Returning all 2 million rows of a table to display "the 10 newest" would be enormously wasteful; `LIMIT` (combined with `ORDER BY`) lets MySQL fetch and return only what's actually needed, and it's the foundation of pagination features you'll build in nearly every Symfony application.

## Example

The store wants to show a homepage widget: "Top 3 most expensive products," so customers can browse the premium catalog.

## Code

```sql
USE online_store;

SELECT name, price
FROM products
ORDER BY price DESC
LIMIT 3;
```

## Code Explanation

- `ORDER BY price DESC` sorts all products from the highest price down to the lowest.
- `LIMIT 3` then keeps only the first 3 rows of that sorted list — meaning the 3 most expensive products overall.
- The order of clauses matters: `FROM` comes first, then `WHERE` (if used), then `ORDER BY`, then `LIMIT`, always in that sequence — MySQL will raise a syntax error if they're rearranged.

## Common Mistakes

- **Using `LIMIT` without `ORDER BY`.** Without a defined sort order, "the first 3 rows" is arbitrary and can even change between runs of the exact same query — never rely on it for anything meaningful.
- **Forgetting `DESC` and assuming "newest first."** `ORDER BY created_at` sorts oldest first by default (ascending); to get the newest first, you need `ORDER BY created_at DESC`.
- **Sorting by a column not included in `SELECT`.** This is actually allowed in MySQL (you can `ORDER BY` a column you didn't select), but it can confuse readers of the query — it's often clearer to include the sort column in the selected list.
- **Off-by-one errors with `LIMIT offset, count` for pagination.** Remember `offset` is zero-based — `LIMIT 0, 10` is page 1, `LIMIT 10, 10` is page 2, and so on.

## Best Practices

- Always pair `LIMIT` with an explicit `ORDER BY` so the results are predictable and meaningful.
- When sorting dates or timestamps for "most recent first" lists, use `DESC` and double-check the result visually against real data at least once.
- For large tables, prefer sorting by an indexed column (like a primary key or a column with an index) — sorting large amounts of unindexed data can be slow. We'll cover indexes more in later chapters, but keep it in mind as your tables grow.

## Exercise

Write a query that returns the `id`, `order_date`, and `user_id` of the 5 most recent orders (assume "most recent" means the largest/latest `order_date`).

## Solution

```sql
SELECT id, order_date, user_id
FROM orders
ORDER BY order_date DESC
LIMIT 5;
```

Sorting by `order_date DESC` puts the latest dates first, and `LIMIT 5` keeps only the top 5 — the 5 most recently placed orders.

## Mini Quiz

```yaml
- question: "What is the default sort direction for ORDER BY if you don't specify ASC or DESC?"
  options:
    - "Descending (largest/newest first)"
    - "Ascending (smallest/oldest first)"
    - "Random order"
    - "Alphabetical by table name"
  answer: 1
  explanation: "ORDER BY defaults to ascending order unless DESC is explicitly specified."

- question: "Why should LIMIT almost always be paired with ORDER BY?"
  options:
    - "LIMIT does not work at all without ORDER BY"
    - "Without a defined order, which rows LIMIT returns is arbitrary and unpredictable"
    - "ORDER BY makes LIMIT run faster in all cases"
    - "MySQL requires the two keywords to always appear together syntactically"
  answer: 1
  explanation: "LIMIT just takes the first N rows of whatever order the results happen to be in; without ORDER BY, that order is not guaranteed or meaningful."

- question: "Which query returns the 3 cheapest products?"
  options:
    - "SELECT name, price FROM products ORDER BY price DESC LIMIT 3;"
    - "SELECT name, price FROM products ORDER BY price ASC LIMIT 3;"
    - "SELECT name, price FROM products LIMIT 3;"
    - "SELECT name, price FROM products WHERE price = LOWEST LIMIT 3;"
  answer: 1
  explanation: "Sorting price in ascending order puts the cheapest first, and LIMIT 3 then keeps only those top 3 cheapest rows."
```
