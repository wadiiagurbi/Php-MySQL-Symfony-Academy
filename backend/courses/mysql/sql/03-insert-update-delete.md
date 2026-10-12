---
slug: insert-update-delete
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: sql
chapterTitle: "Chapter 2 — SQL"
chapterOrder: 2
title: "INSERT, UPDATE, and DELETE"
difficulty: beginner
order: 3
estimatedMinutes: 10
tags: [mysql, sql, crud]
---

## Learning Objective

By the end of this lesson, you will be able to add new rows with `INSERT`, modify existing rows with `UPDATE`, and remove rows with `DELETE` — while understanding why `WHERE` is critical for the last two.

## Explanation

So far we've only *read* data with `SELECT`. Now let's *write* data. There are three commands for this:

**`INSERT`** adds a brand-new row to a table:

```sql
INSERT INTO products (name, price, stock_quantity, category_id)
VALUES ('Coffee Mug', 9.99, 120, 1);
```

You name the columns you're filling in, then provide matching values in the same order. Any column you leave out (like our auto-incrementing `id`) will use its default behavior — MySQL fills `id` in automatically.

**`UPDATE`** changes the values of existing rows:

```sql
UPDATE products
SET stock_quantity = 115
WHERE id = 1;
```

This says: "in the `products` table, set `stock_quantity` to `115`, but only for the row where `id = 1`." The `WHERE` clause here is what limits the change to just that one product.

**`DELETE`** removes entire rows:

```sql
DELETE FROM products
WHERE id = 1;
```

This removes the row where `id = 1` from `products` entirely.

Here's the single most important thing to understand about `UPDATE` and `DELETE`: **if you omit the `WHERE` clause, the command applies to every single row in the table.** `UPDATE products SET price = 0;` with no `WHERE` would set every product's price to zero. `DELETE FROM products;` with no `WHERE` would erase the entire table's contents. Both are perfectly valid SQL — MySQL will not stop you, because sometimes you really do want to update or clear an entire table. This makes `WHERE` the single most important safety check you write.

## Why It Matters

`INSERT`, `UPDATE`, and `DELETE` are how every "Save," "Edit," and "Delete" button in a real web application actually works under the hood. When a customer places an order, an `INSERT` creates the order row. When they update their shipping address, an `UPDATE` changes it. When they cancel an account, a `DELETE` (or often, in practice, a "soft delete" that just marks the row as inactive) removes or hides it.

Forgetting a `WHERE` clause on `UPDATE` or `DELETE` is one of the most infamous, career-story-worthy mistakes in software engineering — it can wipe out or corrupt an entire production table in one keystroke. Professional teams protect against this with backups, code review, and sometimes database settings that require a `WHERE` clause — but the first and most important defense is simply always double-checking your `WHERE` clause before running `UPDATE` or `DELETE`, especially on a real/production database.

## Example

A customer just bought 5 units of the Coffee Mug (product `id = 1`), so the store needs to reduce its stock, and a new customer needs to be registered before placing an order.

## Code

```sql
USE online_store;

-- Add a new customer.
INSERT INTO users (username, email)
VALUES ('alice_smith', 'alice@example.com');

-- Reduce stock after a purchase of 5 units.
UPDATE products
SET stock_quantity = stock_quantity - 5
WHERE id = 1;

-- Remove a discontinued product.
DELETE FROM products
WHERE id = 999;
```

## Code Explanation

- `INSERT INTO users (username, email) VALUES (...)` creates one new row in `users`. Because `id` is `AUTO_INCREMENT` and `created_at` has a `DEFAULT`, we don't need to supply values for them — MySQL fills them in automatically.
- `UPDATE products SET stock_quantity = stock_quantity - 5 WHERE id = 1;` demonstrates that you can reference a column's *current* value on the right-hand side of `SET` — this reads the existing `stock_quantity`, subtracts 5, and saves the result back, all in one atomic step. The `WHERE id = 1` ensures only that one product row is affected.
- `DELETE FROM products WHERE id = 999;` removes exactly one row — the product with `id = 999` — leaving every other product untouched. If no row with that `id` exists, MySQL simply deletes zero rows and does not error.

## Common Mistakes

- **Running `UPDATE` or `DELETE` without `WHERE`.** As explained above, this affects every row in the table. Always write and double-check the `WHERE` clause before running either command, especially against real data.
- **Testing a `DELETE`/`UPDATE` by first running the equivalent `SELECT` with the same `WHERE`.** Skipping this "dry run" habit is a common mistake — running `SELECT * FROM products WHERE id = 1;` first, to confirm you're targeting the right row(s), costs seconds but prevents disasters.
- **Providing values in the wrong order in `INSERT` without naming columns.** `INSERT INTO products VALUES (...)` without listing column names requires you to supply a value for *every* column in the exact order they were defined — a fragile approach that breaks silently if the table structure ever changes. Always name your columns explicitly.
- **Forgetting foreign key order.** You cannot `INSERT` a product with a `category_id` that doesn't yet exist in `categories` — insert the "parent" row first.

## Best Practices

- Get in the habit of writing `SELECT ... WHERE <condition>` first to preview exactly which rows an `UPDATE`/`DELETE` will affect, before switching `SELECT ...` to `UPDATE ... SET ...` or `DELETE FROM ...`.
- Always name explicit columns in `INSERT` statements rather than relying on positional order.
- On any real/production database, prefer running data-changing commands inside a transaction when possible (covered in Chapter 4), so mistakes can be rolled back before they're made permanent.

## Exercise

A customer with `user_id = 5` wants to cancel their most recent order with `id = 42`. Write the SQL to delete that specific order from the `orders` table (be careful to target only that one order).

## Solution

```sql
DELETE FROM orders
WHERE id = 42;
```

Using the unique primary key `id = 42` in the `WHERE` clause guarantees that only that exact order row is deleted, regardless of which user placed it — this is safer and more precise than filtering by `user_id` alone, which could match multiple orders.

## Mini Quiz

```yaml
- question: "What happens if you run UPDATE products SET price = 0; without a WHERE clause?"
  options:
    - "MySQL refuses to run the command"
    - "Only the first row is updated"
    - "Every single row in the products table has its price set to 0"
    - "Nothing happens until you add LIMIT"
  answer: 2
  explanation: "Without a WHERE clause, UPDATE (and DELETE) applies to every row in the table — a common and costly mistake."

- question: "In INSERT INTO users (username, email) VALUES ('alice', 'a@example.com');, why don't we need to provide a value for id?"
  options:
    - "id does not exist in the users table"
    - "id is AUTO_INCREMENT, so MySQL fills it in automatically"
    - "MySQL sets id to NULL by default and that is fine"
    - "id values are provided by the browser"
  answer: 1
  explanation: "An AUTO_INCREMENT column is automatically assigned the next available number by MySQL, so it should be omitted from INSERT."

- question: "What is a good safety habit before running a DELETE or UPDATE with a WHERE clause on real data?"
  options:
    - "Run the same WHERE condition as a SELECT first to preview the affected rows"
    - "Never use WHERE at all"
    - "Always omit the WHERE clause to be safe"
    - "Run the DELETE twice to confirm it worked"
  answer: 0
  explanation: "Previewing with a SELECT using the same WHERE condition confirms exactly which rows will be affected before making an irreversible change."
```
