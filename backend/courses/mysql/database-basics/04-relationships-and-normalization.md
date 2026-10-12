---
slug: relationships-and-normalization
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: database-basics
chapterTitle: "Chapter 1 — Database Basics"
chapterOrder: 1
title: "Relationships and Normalization Basics"
difficulty: beginner
order: 4
estimatedMinutes: 10
tags: [mysql, normalization, design]
---

## Learning Objective

By the end of this lesson, you will understand the basic idea of "normalization" — organizing data to avoid repetition and inconsistency — and be able to recognize when a table design needs to be split into multiple related tables.

## Explanation

**Normalization** is the process of organizing a database's tables so that each piece of information is stored in exactly one place, and related information is linked together with primary/foreign keys instead of being copied over and over. It's a formal topic with named "levels" (First Normal Form, Second Normal Form, etc.), but as a beginner, you mostly need the core intuition: **don't repeat yourself in your data.**

Let's see why repetition is a problem with a concrete, "bad" design. Imagine we stored all order information in one giant table, one row per item purchased:

| order_id | customer_name | customer_email       | product_name | product_price |
|----------|----------------|-----------------------|---------------|-----------------|
| 1        | Alice Smith    | alice@example.com     | Coffee Mug    | 9.99            |
| 1        | Alice Smith    | alice@example.com     | Notebook      | 3.50            |
| 2        | Bob Jones      | bob@example.com       | Coffee Mug    | 9.99            |

Notice the problems:
- Alice's name and email are repeated on every item she bought. If she updates her email, you must update it in every single row — miss one, and now her data is inconsistent.
- "Coffee Mug" and its price `9.99` are duplicated every time anyone buys one. If the price changes, you'd have to update potentially thousands of historical rows, which is both slow and, for past orders, arguably *wrong* (an old order should keep the price the customer actually paid).
- There's no single, authoritative list of all customers or all products — you'd have to scan the whole table and de-duplicate to find out.

The fix is to **split** this into separate tables connected by primary/foreign keys — exactly what we've been doing across this chapter: a `users` table (one row per customer, storing the email once), a `products` table (one row per product, storing the price once), and an `orders` table that links a `user_id` to an order, with a separate table for the actual items purchased. Now each fact lives in exactly one place, and tables reference each other by ID.

This is normalization in action: instead of one wide, repetitive table, you get several narrow, focused tables, each describing one *kind* of thing (a customer, a product, an order), connected by keys.

## Why It Matters

Poorly normalized databases cause real, expensive bugs in production: inconsistent data (the same customer's email spelled two different ways in different rows), wasted storage, and painful, error-prone updates. Normalization isn't academic box-ticking — it's what keeps a growing application's data trustworthy as it scales from 10 rows to 10 million rows.

That said, normalization isn't "more tables always = better." Experienced developers sometimes deliberately duplicate a little data for performance reasons (called "denormalization") once they deeply understand the tradeoffs — but as a beginner, defaulting to well-normalized tables will save you from the vast majority of real-world data bugs, and it's exactly the structure that Symfony's Doctrine ORM expects when you define entities and relationships.

## Example

Let's look at our online store's design so far and confirm it follows this principle. We have:

- `categories` — one row per category, storing the name once.
- `products` — one row per product, storing the price once, linked to `categories` via `category_id`.
- `users` — one row per customer (assumed from the previous lesson).
- `orders` — one row per order, linked to `users` via `user_id`.

Each fact (a category's name, a product's price, a customer's email) lives in exactly one place. This is a normalized design.

## Code

```sql
-- A normalized "users" table: customer info stored once per customer.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Confirm the full, normalized shape of our online store so far.
SHOW TABLES;
```

## Code Explanation

- `CREATE TABLE IF NOT EXISTS users (...)` creates the `users` table only if it doesn't already exist, which is convenient when re-running setup scripts without errors.
- `username VARCHAR(50) NOT NULL UNIQUE` and `email VARCHAR(255) NOT NULL UNIQUE` both use the `UNIQUE` constraint, which tells MySQL that no two rows may share the same username or the same email — this is a normalization-friendly rule that prevents accidental duplicate accounts.
- `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` automatically records the exact moment a user row was created, without the application having to calculate or pass in that value — MySQL fills it in for you.
- `SHOW TABLES;` lists every table currently in the selected database, which is a quick way to review your overall design (`categories`, `products`, `users`, `orders`, and so on) at a glance.

## Common Mistakes

- **Storing the same fact in two tables "for convenience."** For example, storing a product's category *name* directly in the `products` table in addition to `category_id`. This seems like it saves a lookup, but it creates two places that can disagree if the category is ever renamed.
- **Cramming unrelated information into one table.** A "mega table" with customer info, product info, and order info all mixed together (like our bad example above) makes updates unsafe and the design hard to reason about.
- **Over-normalizing too early as a total beginner.** Splitting every tiny detail into its own table before you understand the tradeoffs can make simple queries unnecessarily complicated. Aim for "one table per real-world concept" (a customer, a product, an order) rather than obsessing over perfect textbook normal forms on day one.

## Best Practices

- Ask "if this fact changes, how many rows would I have to update?" If the answer is "more than one," that fact is probably duplicated and should be moved to its own table, referenced by a foreign key.
- Give each table a single, clear responsibility — one real-world "thing" per table (a product, a category, a customer, an order).
- Use `UNIQUE` constraints on columns that must never be duplicated in the real world (like `email` or `username`), letting MySQL enforce that rule instead of relying only on application code.

## Exercise

Look back at the "bad" repetitive order table shown earlier in this lesson (with `order_id`, `customer_name`, `customer_email`, `product_name`, `product_price` all mixed together). Name the three separate, normalized tables you would split it into, and briefly describe what column would link each pair of tables together.

## Solution

A normalized redesign would use:

1. **`users`** — one row per customer, with columns like `id`, `username`, `email`.
2. **`products`** — one row per product, with columns like `id`, `name`, `price`.
3. **`orders`** — one row per order, with columns like `id`, `order_date`, and a `user_id` foreign key linking to `users(id)`.

Linking the specific products bought in a specific order requires a fourth table (an "order items" table) that connects `orders` and `products` — we will build exactly this in Chapter 3, when we cover many-to-many relationships.

## Mini Quiz

```yaml
- question: "What is the core idea behind normalization?"
  options:
    - "Storing every fact in as many tables as possible"
    - "Storing each piece of information in exactly one place, linked by keys instead of duplicated"
    - "Making every column a primary key"
    - "Avoiding the use of foreign keys entirely"
  answer: 1
  explanation: "Normalization means each fact lives in exactly one place, with tables referencing each other via primary/foreign keys instead of duplicating data."

- question: "What is a concrete problem caused by storing a customer's email on every order row instead of once in a users table?"
  options:
    - "MySQL would refuse to store any email addresses"
    - "Updating the email would require changing it in every order row, risking inconsistency"
    - "It would make the orders table faster to query"
    - "There is no real problem with this approach"
  answer: 1
  explanation: "Duplicating the email across many rows means an update must touch every row, and missing one leaves inconsistent, untrustworthy data."

- question: "Which constraint would you use to make sure no two users can register with the same email address?"
  options:
    - "PRIMARY KEY only"
    - "UNIQUE"
    - "AUTO_INCREMENT"
    - "DEFAULT"
  answer: 1
  explanation: "UNIQUE ensures no two rows can have the same value in that column, which is exactly what's needed to prevent duplicate emails."
```
