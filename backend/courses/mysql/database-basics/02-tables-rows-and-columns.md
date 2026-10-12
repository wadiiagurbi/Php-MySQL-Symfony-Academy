---
slug: tables-rows-and-columns
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: database-basics
chapterTitle: "Chapter 1 — Database Basics"
chapterOrder: 1
title: "Tables, Rows, and Columns"
difficulty: beginner
order: 2
estimatedMinutes: 9
tags: [mysql, tables, basics]
---

## Learning Objective

By the end of this lesson, you will understand exactly what a table, a row, and a column are, how to create a table in MySQL with `CREATE TABLE`, and how to pick appropriate data types for your columns.

## Explanation

In the last lesson we compared a database to a folder, and said it holds **tables**. Let's now zoom into a single table.

A **table** is a structured list of similar things, organized like a spreadsheet. Picture an Excel sheet named "Products":

| id | name         | price | stock_quantity |
|----|--------------|-------|-----------------|
| 1  | Coffee Mug   | 9.99  | 120             |
| 2  | Notebook     | 3.50  | 300             |
| 3  | Desk Lamp    | 24.00 | 45              |

- Each **column** is a category of information that every entry in the table has: `id`, `name`, `price`, `stock_quantity`. Every column also has a **data type**, which tells MySQL what kind of value is allowed in it (a whole number, a decimal number, text, a date, etc.).
- Each **row** is one single entry — one specific product. In the example above there are three rows: the Coffee Mug, the Notebook, and the Desk Lamp. A row is sometimes also called a "record."
- The table as a whole (`products`) is the entire spreadsheet: all the columns and all the rows together.

To create a table in MySQL, you use the `CREATE TABLE` statement, listing each column's name and data type. Common MySQL data types you'll use constantly as a beginner:

- `INT` — a whole number (e.g., `120`, `-5`, `0`). Good for counts, quantities, IDs.
- `VARCHAR(n)` — text with a maximum length of `n` characters (e.g., `VARCHAR(100)` for a product name). Use this for short-to-medium text.
- `TEXT` — text with no practical length limit, for long content like a product description or blog post body.
- `DECIMAL(p, s)` — an exact decimal number, ideal for money (e.g., `DECIMAL(10,2)` allows numbers up to 10 digits total, with 2 after the decimal point, like `9999999.99`). Never use floating-point types for money — more on that below.
- `DATE` / `DATETIME` — calendar dates, optionally with a time.
- `BOOLEAN` — true/false values (MySQL actually stores these as a tiny integer, `0` or `1`).

## Why It Matters

Choosing the right table structure and data types up front saves enormous pain later. If you store a price as `VARCHAR` (text) instead of `DECIMAL`, you cannot reliably do math on it (like calculating a total), and someone could accidentally type letters into a price field. If you make a column too short (like `VARCHAR(10)` for a customer's full name), real data will get truncated or rejected in production — a bug that is embarrassing and hard to trace after the fact.

This is exactly the kind of decision a Symfony developer makes constantly when defining Doctrine entities: every property on an entity class maps to a column with a specific type, and getting it right the first time avoids painful database migrations later.

## Example

For our running "online store" example, the very first table we need is `products` — the items the store sells. Every product needs a unique identifier, a name, a price, and how many are in stock.

## Code

```sql
-- Make sure we're working inside our example database.
USE online_store;

-- Create the products table with four columns.
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0
);
```

## Code Explanation

- `CREATE TABLE products ( ... );` tells MySQL to create a new table named `products`, with the columns listed inside the parentheses.
- `id INT AUTO_INCREMENT PRIMARY KEY` creates a column named `id` that holds whole numbers. `AUTO_INCREMENT` means MySQL automatically fills it in with the next available number (1, 2, 3, ...) every time a new row is added, so you never have to think of an ID yourself. `PRIMARY KEY` marks this column as the table's unique identifier — we'll dive deeper into what that means in the next lesson.
- `name VARCHAR(150) NOT NULL` creates a text column that can hold up to 150 characters. `NOT NULL` means this column can never be left empty — every product must have a name.
- `price DECIMAL(10, 2) NOT NULL` stores an exact price, like `24.00`, with up to 10 total digits and exactly 2 after the decimal point. This is the correct type for money, because it avoids the tiny rounding errors that floating-point types (`FLOAT`/`DOUBLE`) can introduce.
- `stock_quantity INT NOT NULL DEFAULT 0` stores how many units are in stock. `DEFAULT 0` means that if you insert a new product without specifying a quantity, MySQL automatically sets it to `0` instead of leaving it undefined.

## Common Mistakes

- **Using `FLOAT`/`DOUBLE` for money.** These types store approximate values, so calculations like `0.1 + 0.2` can produce tiny rounding errors — unacceptable when money is involved. Always use `DECIMAL` for prices, totals, and balances.
- **Making `VARCHAR` too small.** A `VARCHAR(20)` for an email address will reject many real, valid email addresses. When unsure, err on the generous side (e.g., `VARCHAR(255)` for emails).
- **Forgetting `NOT NULL` on required fields.** Without it, MySQL will happily let someone create a product with no name at all, leading to confusing blank entries later.

## Best Practices

- Give tables plural, lowercase, snake_case names (`products`, not `Product` or `product`), and columns the same style (`stock_quantity`, not `StockQuantity`).
- Always add a primary key column (commonly `id INT AUTO_INCREMENT PRIMARY KEY`) to every table, even if it seems unnecessary at first — you will almost always need it later to reference this row from another table.
- Pick the narrowest, most specific data type that fits the data (e.g., `DECIMAL` for money, `DATE` for dates, not `VARCHAR` for everything) — this catches bad data early and keeps queries fast.

## Exercise

Design and write the `CREATE TABLE` statement for a `categories` table in our online store, with:

- `id`: auto-incrementing primary key.
- `name`: a required text column, up to 100 characters (e.g., "Electronics", "Books").

## Solution

```sql
USE online_store;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
```

This creates a simple `categories` table where every category has a unique `id` and a required `name`. We'll connect this table to `products` in the very next lesson, once we understand primary and foreign keys.

## Mini Quiz

```yaml
- question: "In a database table, what does a single row represent?"
  options:
    - "One category of information shared by every entry"
    - "The entire table"
    - "One specific entry or record, like one product"
    - "A data type"
  answer: 2
  explanation: "A row (record) represents one specific entry in the table — one product, one user, one order, etc."

- question: "Which MySQL data type should you use to store a product's price?"
  options:
    - "VARCHAR"
    - "FLOAT"
    - "DECIMAL"
    - "BOOLEAN"
  answer: 2
  explanation: "DECIMAL stores exact decimal values, which is essential for money, avoiding the rounding errors that FLOAT/DOUBLE can introduce."

- question: "What does AUTO_INCREMENT do on an id column?"
  options:
    - "It deletes the row automatically after use"
    - "It automatically fills in the next available number for each new row"
    - "It converts the column to text"
    - "It makes the column allow NULL values"
  answer: 1
  explanation: "AUTO_INCREMENT automatically assigns the next sequential number to the id column each time a new row is inserted."
```
