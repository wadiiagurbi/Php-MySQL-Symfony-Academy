---
slug: primary-and-foreign-keys
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: database-basics
chapterTitle: "Chapter 1 — Database Basics"
chapterOrder: 1
title: "Primary Keys and Foreign Keys"
difficulty: beginner
order: 3
estimatedMinutes: 10
tags: [mysql, keys, basics]
---

## Learning Objective

By the end of this lesson, you will understand what a primary key is, what a foreign key is, and how the two work together to link tables — the foundation of every relational database.

## Explanation

A **primary key** is a column (or small set of columns) in a table whose value uniquely identifies each row, and can never be duplicated or left empty. Think of it like a Social Security Number, a passport number, or a library card number: no two people share one, and everyone has exactly one. In our `products` table from the previous lesson, the `id` column is the primary key — product #1 is always product #1, no matter how many other products exist, and no two products ever share the same `id`.

Why not just use the product's `name` as the identifier? Because names can repeat (two different products could both be called "Notebook") and can change (a product might get renamed). A primary key must be stable and unique, so we almost always use a dedicated auto-incrementing number for it, rather than reusing "real" data.

A **foreign key** is a column in one table that stores the primary key value of a row in *another* table, creating a link between them. This is how relational databases connect related information without duplicating it everywhere.

Imagine our `products` table also needs to know which category each product belongs to (like "Electronics" or "Books"), and we already have a separate `categories` table (from the previous lesson) with its own `id` primary key. Instead of writing the category name directly into every product row (which would be repeated hundreds of times and hard to keep consistent if a category is renamed), we add a `category_id` column to `products` that simply stores the `id` of the matching row in `categories`. That `category_id` column is a foreign key — it "points to" a row in another table.

MySQL can enforce this link with a `FOREIGN KEY` constraint, which prevents you from ever pointing to a category that doesn't exist, and can control what happens if a category is deleted while products still reference it.

## Why It Matters

Primary and foreign keys are the mechanism that makes relational databases powerful instead of just being a pile of disconnected spreadsheets. Without them, you'd have to copy the full category name into every single product row, and if that category's name ever changed, you'd need to update every single row — a maintenance nightmare and a common source of inconsistent data ("Electronics" in some rows, "electronics" in others, "Electronic" typo'd in yet another).

In professional development, foreign keys are also what protects your data's **integrity**: MySQL will refuse to let you insert a product with a `category_id` that doesn't exist in `categories`, catching bugs in your application before they corrupt your data. When you later use Symfony's Doctrine ORM, every `ManyToOne`/`OneToMany` relationship you define between entities is implemented under the hood using exactly this primary key / foreign key mechanism.

## Example

Let's connect our `products` table to the `categories` table we designed in the previous lesson. Each product will belong to exactly one category, but each category can have many products — a relationship pattern called "one-to-many," which we'll study in depth in Chapter 3.

## Code

```sql
USE online_store;

-- Recreate products with a foreign key link to categories.
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

## Code Explanation

- `DROP TABLE IF EXISTS products;` deletes the old version of `products` from the previous lesson (if it exists) so we can recreate it with the new `category_id` column. `IF EXISTS` prevents an error if the table wasn't there yet.
- `category_id INT NOT NULL` adds a new column that will hold the `id` of a row from the `categories` table. It's an `INT` because `categories.id` is also an `INT` — a foreign key column must match the data type of the primary key it points to.
- `FOREIGN KEY (category_id) REFERENCES categories(id)` is the constraint that tells MySQL: "every value in `products.category_id` must exist as an `id` in the `categories` table." If you try to insert a product with `category_id = 99` but no category with `id = 99` exists, MySQL will reject the insert with an error, protecting you from broken links.
- Because of this constraint, the `categories` table must already exist and have at least the referenced rows before you insert products that point to them — MySQL checks the relationship at insert/update time.

## Common Mistakes

- **Forgetting to create the referenced table first.** You cannot add a foreign key pointing to a table that doesn't exist yet — `categories` must exist before `products` can reference it.
- **Mismatched data types.** If `categories.id` is `INT` but you make `products.category_id` a `VARCHAR`, MySQL will refuse to create the foreign key constraint.
- **Deleting a row that's still referenced elsewhere.** If you try to delete a category that still has products pointing to it, MySQL will block the deletion by default (to prevent "orphaned" foreign keys) unless you've configured a specific `ON DELETE` behavior.
- **Confusing primary key with foreign key.** A primary key uniquely identifies rows *in its own table*; a foreign key *references* a primary key *in another table*. A table can have only one primary key, but it can have several foreign keys.

## Best Practices

- Name foreign key columns after the table and column they reference, using a clear pattern like `category_id` (referencing `categories.id`) — this makes relationships obvious at a glance.
- Always add an actual `FOREIGN KEY` constraint in MySQL rather than just "trusting" your application code to keep IDs consistent — constraints catch bugs that application code might miss.
- Index your foreign key columns (MySQL does this automatically when you declare a `FOREIGN KEY` constraint) so that queries joining the two tables stay fast even as data grows — we'll see why this matters in Chapter 3.

## Exercise

Our online store also needs an `orders` table to record purchases. Each order is placed by exactly one customer (from a `users` table, which has an `id` primary key). Write the `CREATE TABLE` statement for a simple `orders` table with:

- `id`: auto-incrementing primary key.
- `order_date`: a `DATE` column, required.
- `user_id`: a foreign key referencing `users(id)`, required.

(Assume a `users` table with an `id INT AUTO_INCREMENT PRIMARY KEY` already exists.)

## Solution

```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATE NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

This creates an `orders` table where every order has its own unique `id`, a required `order_date`, and a `user_id` that must match an existing row in the `users` table — guaranteeing every order is linked to a real customer.

## Mini Quiz

```yaml
- question: "What must be true about a primary key column's values?"
  options:
    - "They can repeat as long as the row is old enough"
    - "They must be unique across the table and never empty"
    - "They must always be text"
    - "They are optional and can be left blank"
  answer: 1
  explanation: "A primary key uniquely identifies every row, so its values must be unique and never NULL."

- question: "What does a foreign key column store?"
  options:
    - "A copy of every column from another table"
    - "The primary key value of a related row in another table"
    - "A random unique string"
    - "The table's own name"
  answer: 1
  explanation: "A foreign key stores the primary key value of a row in another table, creating a link between the two tables."

- question: "What happens if you try to insert a product with a category_id that does not exist in the categories table?"
  options:
    - "MySQL silently creates a new category"
    - "MySQL rejects the insert because of the foreign key constraint"
    - "MySQL inserts it with category_id set to NULL automatically"
    - "Nothing — MySQL does not check foreign keys by default"
  answer: 1
  explanation: "A FOREIGN KEY constraint forces MySQL to reject any insert or update that would reference a non-existent row in the related table."
```
