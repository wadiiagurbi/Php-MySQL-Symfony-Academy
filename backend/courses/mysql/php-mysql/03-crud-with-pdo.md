---
slug: crud-with-pdo
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: php-mysql
chapterTitle: "Chapter 4 — PHP + MySQL"
chapterOrder: 4
title: "Building CRUD with PDO"
difficulty: intermediate
order: 3
estimatedMinutes: 13
tags: [php, pdo, crud]
---

## Learning Objective

By the end of this lesson, you will be able to write PHP functions that Create, Read, Update, and Delete (CRUD) rows in a MySQL table using PDO prepared statements, tying together everything from the previous two lessons.

## Explanation

**CRUD** stands for Create, Read, Update, Delete — the four basic operations almost every application performs on stored data. We already met their SQL equivalents in Chapter 2: `INSERT` (Create), `SELECT` (Read), `UPDATE` (Update), and `DELETE` (Delete). Now we'll wrap each one in a small, reusable PHP function using PDO and prepared statements, so the rest of an application can call simple functions like `createProduct(...)` instead of writing raw SQL everywhere.

**Create** — use `INSERT`, then retrieve the new row's auto-generated ID with `lastInsertId()`:

```php
<?php
function createProduct(PDO $pdo, string $name, float $price, int $stock, int $categoryId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO products (name, price, stock_quantity, category_id) VALUES (:name, :price, :stock, :category_id)'
    );
    $stmt->execute([
        'name' => $name,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $categoryId,
    ]);

    return (int) $pdo->lastInsertId();
}
```

**Read** — use `SELECT`, and choose `fetch()` for a single row or `fetchAll()` for many rows:

```php
<?php
function findProductById(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->fetch();
}
```

**Update** — use `UPDATE` with a `WHERE`, and check `rowCount()` to see how many rows were actually affected:

```php
<?php
function updateProductStock(PDO $pdo, int $id, int $newStock): bool
{
    $stmt = $pdo->prepare('UPDATE products SET stock_quantity = :stock WHERE id = :id');
    $stmt->execute(['stock' => $newStock, 'id' => $id]);

    return $stmt->rowCount() > 0;
}
```

**Delete** — use `DELETE` with a `WHERE`, again checking `rowCount()`:

```php
<?php
function deleteProduct(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}
```

Notice the pattern repeats every time: `prepare()` the SQL with named placeholders, `execute()` with the real values, then use the right method to get back what you need (`lastInsertId()`, `fetch()`/`fetchAll()`, or `rowCount()`).

## Why It Matters

Nearly every feature of a real web application boils down to some combination of these four operations: registering a user is a Create, viewing a profile is a Read, editing settings is an Update, and removing an item from a cart is a Delete. Wrapping each SQL operation in a small, named, reusable PHP function (rather than writing raw SQL scattered across many files) is exactly the discipline that makes code maintainable — and it is precisely the gap that an ORM like Symfony's Doctrine fills for you automatically once your project grows, by generating functions like these behind methods such as `$entityManager->persist()` and `$repository->find()`.

Understanding CRUD at this raw PDO level means that when you eventually work with Doctrine, you will recognize exactly what it's doing for you under the hood, and you'll be equipped to write custom queries when Doctrine's built-in tools aren't quite enough.

## Example

The store needs to let an admin panel add a new product, look up a product by ID, adjust stock after a sale, and remove a discontinued product — the four CRUD operations applied to our `products` table.

## Code

```php
<?php

function createProduct(PDO $pdo, string $name, float $price, int $stock, int $categoryId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO products (name, price, stock_quantity, category_id) VALUES (:name, :price, :stock, :category_id)'
    );
    $stmt->execute([
        'name' => $name,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $categoryId,
    ]);

    return (int) $pdo->lastInsertId();
}

$newId = createProduct($pdo, 'Desk Lamp', 24.00, 45, 3);
echo "Created product with ID: $newId";
```

## Code Explanation

- The `INSERT` statement uses four named placeholders (`:name`, `:price`, `:stock`, `:category_id`), matched to an associative array passed into `execute()` — PDO matches each placeholder to the array key of the same name, regardless of the order they appear in the array.
- `$pdo->lastInsertId()` asks MySQL for the auto-incremented `id` value that was just generated for this new row — this only works reliably immediately after the `INSERT` that created it, on the same connection.
- We cast the result with `(int)` because `lastInsertId()` returns a string by default in PDO, and our function's return type is declared as `int` for cleaner, more predictable code elsewhere in the application.
- Calling `createProduct($pdo, 'Desk Lamp', 24.00, 45, 3)` inserts a new product and immediately tells us its new ID, which a real application would typically use to redirect to a "product created" confirmation page.

## Common Mistakes

- **Forgetting `WHERE` on `updateProductStock`/`deleteProduct`.** Just like in raw SQL (Chapter 2), an `UPDATE`/`DELETE` PDO statement without a `WHERE` clause affects every row in the table — always double-check the SQL string inside `prepare()`.
- **Assuming `execute()` returning `true` means a row was actually changed.** `execute()` returns `true` even if zero rows matched the `WHERE` clause (as long as the SQL itself was valid) — use `rowCount()` to check how many rows were genuinely affected.
- **Calling `lastInsertId()` on the wrong connection or after other queries ran in between.** It reflects the most recent auto-increment value generated *on that same PDO connection*, so call it immediately after the relevant `INSERT`.
- **Not validating input before it reaches these functions.** PDO prevents SQL injection, but it does not check business rules — e.g., nothing here stops someone from passing a negative `price`; that validation belongs in your application logic before calling these functions.

## Best Practices

- Keep each CRUD function focused on exactly one operation, with a clear, descriptive name (`createProduct`, not a generic `saveData`).
- Return meaningful values from these functions (`lastInsertId()` for creates, a boolean for updates/deletes based on `rowCount()`) so calling code can react appropriately (e.g., show an error if `deleteProduct()` returns `false` because the ID didn't exist).
- Keep these data-access functions separate from your presentation code (HTML output) — a function like `createProduct()` should only handle the database interaction, not print anything to the page.

## Exercise

Write a PHP function `findAllProductsInCategory(PDO $pdo, int $categoryId): array` that returns every product row (as an array of associative arrays) belonging to a given category, ordered by name alphabetically, using a prepared statement.

## Solution

```php
<?php

function findAllProductsInCategory(PDO $pdo, int $categoryId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, price, stock_quantity FROM products WHERE category_id = :category_id ORDER BY name ASC'
    );
    $stmt->execute(['category_id' => $categoryId]);

    return $stmt->fetchAll();
}
```

This combines a prepared statement (safe filtering by `category_id`) with `ORDER BY name ASC` from Chapter 2, and `fetchAll()` to return every matching row as a PHP array, ready to loop over and display.

## Mini Quiz

```yaml
- question: "Which PDO method retrieves the auto-generated ID of a row just inserted with INSERT?"
  options:
    - "rowCount()"
    - "fetch()"
    - "lastInsertId()"
    - "prepare()"
  answer: 2
  explanation: "lastInsertId() returns the most recent AUTO_INCREMENT value generated on the current connection, typically called right after an INSERT."

- question: "Why is checking rowCount() important after an UPDATE or DELETE with PDO?"
  options:
    - "execute() throws an exception whenever zero rows are matched, so rowCount() is never needed"
    - "execute() can return true even if the WHERE clause matched zero rows, so rowCount() tells you how many rows were actually affected"
    - "rowCount() is required to prevent SQL injection"
    - "rowCount() automatically undoes the operation if it affected the wrong rows"
  answer: 1
  explanation: "execute() returning true only confirms the SQL ran without error, not that any specific row was changed; rowCount() reports the actual number of affected rows."

- question: "In the createProduct() function, why is the return value of lastInsertId() cast with (int)?"
  options:
    - "Because lastInsertId() always throws an error otherwise"
    - "Because lastInsertId() returns a string by default in PDO, and the function's declared return type is int"
    - "Because MySQL requires IDs to be cast before insertion"
    - "Casting has no real purpose here"
  answer: 1
  explanation: "PDO's lastInsertId() returns a string, so casting to int matches the function's declared return type and keeps downstream code predictable."
```
