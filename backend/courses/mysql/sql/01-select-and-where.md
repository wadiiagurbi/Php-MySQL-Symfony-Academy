---
slug: select-and-where
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "SELECT and WHERE"
difficulty: beginner
order: 1
estimatedMinutes: 9
tags: [mysql, sql, select]
---

## Learning Objective

By the end of this lesson, you will be able to write `SELECT` statements to retrieve data from a table, choose specific columns, and filter rows using `WHERE` with comparison operators.

## Explanation

Now that our online store's tables exist (`categories`, `products`, `users`, `orders` from Chapter 1), it's time to actually ask MySQL questions about the data inside them. The command for "give me data" is `SELECT`.

The basic shape of a `SELECT` statement is:

```
SELECT column1, column2 FROM table_name;
```

This means: "from the table named `table_name`, give me the values in `column1` and `column2`, for every row." If you want *all* columns without typing each name, you can use the wildcard `*`:

```
SELECT * FROM products;
```

This returns every column and every row in `products` — useful for quickly inspecting a table, but in real applications it's usually better to name only the columns you actually need (more on that in Best Practices).

Often you don't want *every* row — you want only the ones matching some condition. That's what the `WHERE` clause is for:

```
SELECT name, price FROM products WHERE price < 10;
```

This returns only the `name` and `price` of products whose `price` is less than `10`. `WHERE` supports familiar comparison operators:

- `=` equal to
- `!=` or `<>` not equal to
- `<`, `>`, `<=`, `>=` less than, greater than, and their "or equal to" versions
- `AND` / `OR` to combine multiple conditions (e.g., `WHERE price < 10 AND stock_quantity > 0`)

MySQL evaluates the condition for every row and keeps only the rows where it's true — rows where it's false are simply left out of the result.

## Why It Matters

`SELECT ... WHERE` is, without exaggeration, the single most-used piece of SQL in the entire language. Every time a web page shows "products under $20," "orders placed by this logged-in user," or "search results matching this keyword," there's a `SELECT ... WHERE` behind the scenes, often generated for you by an ORM like Symfony's Doctrine — but understanding the raw SQL is what lets you read Doctrine's generated queries, debug slow pages, and write custom queries when the ORM's tools aren't flexible enough.

Filtering correctly also matters for basic safety and correctness: a `SELECT` without a needed `WHERE` clause can return far more data than intended (imagine accidentally showing one customer another customer's orders), so this is a place where precision really counts.

## Example

The store's customer support team wants to look up any product priced under $10 that still has stock available, so they can highlight it in a "budget picks" promotion.

## Code

```sql
USE online_store;

-- Assume some sample rows already exist in products.
SELECT name, price, stock_quantity
FROM products
WHERE price < 10 AND stock_quantity > 0;
```

## Code Explanation

- `SELECT name, price, stock_quantity` lists exactly the three columns we want back, instead of every column in the table — keeping the result focused on what the support team actually needs.
- `FROM products` specifies which table to read from.
- `WHERE price < 10 AND stock_quantity > 0` keeps only rows where *both* conditions are true: the price is under $10, **and** there is at least one unit in stock. If a product costs $8 but has `stock_quantity = 0`, it will correctly be excluded, because `AND` requires both sides to hold.
- The order of columns in `SELECT` controls the order they appear in the result — it doesn't need to match the order columns were defined in the table.

## Common Mistakes

- **Using `SELECT *` in real application code.** It's fine for quick exploration, but in an app it wastes bandwidth (pulling columns you'll never use) and breaks silently if someone adds a new column later that your code wasn't expecting.
- **Confusing `=` with `==`.** SQL uses a single `=` for equality checks (unlike PHP's `==`/`===`). Writing `WHERE price == 10` is invalid SQL.
- **Forgetting that string comparisons need quotes.** `WHERE name = Coffee Mug` is invalid SQL and will error — text values must be quoted: `WHERE name = 'Coffee Mug'`. Numbers don't need quotes: `WHERE price = 9.99`.
- **Mixing up `AND`/`OR` precedence.** `WHERE price < 10 OR stock_quantity > 0 AND category_id = 1` can evaluate differently than you expect; when combining both, use parentheses to make your intent explicit, e.g. `WHERE (price < 10 OR stock_quantity > 0) AND category_id = 1`.

## Best Practices

- Always name the specific columns you need instead of `SELECT *`, especially in application code — it's clearer, faster, and safer against future schema changes.
- Use parentheses around combined `AND`/`OR` conditions to remove any ambiguity about how they're grouped.
- When filtering on user-provided values (like a search box), never build the SQL by directly pasting the user's input into the query string — we'll cover the safe way (prepared statements) in Chapter 4.

## Exercise

Write a `SELECT` statement against the `orders` table (columns: `id`, `order_date`, `user_id`) that returns the `id` and `order_date` of every order placed by the user with `user_id = 5`.

## Solution

```sql
SELECT id, order_date
FROM orders
WHERE user_id = 5;
```

This returns only the `id` and `order_date` columns, and only for rows where `user_id` equals `5` — exactly the orders belonging to that one customer.

## Mini Quiz

```yaml
- question: "What does the following SQL do? SELECT name FROM products WHERE stock_quantity = 0;"
  options:
    - "Deletes every out-of-stock product"
    - "Returns the name of every product that has zero units in stock"
    - "Returns every column for every product"
    - "Updates stock_quantity to 0 for all products"
  answer: 1
  explanation: "SELECT retrieves data without modifying it; this query returns only the name column, filtered to rows where stock_quantity equals 0."

- question: "Which SQL correctly finds all products priced at exactly 19.99?"
  options:
    - "SELECT * FROM products WHERE price == 19.99;"
    - "SELECT * FROM products WHERE price = 19.99;"
    - "SELECT * FROM products WHERE price := 19.99;"
    - "SELECT * FROM products WHERE price -> 19.99;"
  answer: 1
  explanation: "SQL uses a single = for equality comparisons in WHERE clauses, not ==."

- question: "In WHERE price < 10 AND stock_quantity > 0, when is a row included in the result?"
  options:
    - "When either condition is true"
    - "Only when both conditions are true"
    - "Only when neither condition is true"
    - "Always, regardless of the conditions"
  answer: 1
  explanation: "AND requires both conditions to be true for a row to be included in the result set."
```
