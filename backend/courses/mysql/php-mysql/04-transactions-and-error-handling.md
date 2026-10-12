---
slug: transactions-and-error-handling
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: php-mysql
chapterTitle: "Chapter 4 — PHP + MySQL"
chapterOrder: 4
title: "Transactions and Error Handling"
difficulty: intermediate
order: 4
estimatedMinutes: 13
tags: [php, pdo, transactions]
---

## Learning Objective

By the end of this lesson, you will understand what a database transaction is, why some operations must be "all or nothing," and how to use PDO's `beginTransaction()`, `commit()`, and `rollBack()` to keep your data consistent even when something goes wrong midway through.

## Explanation

Some real-world actions actually require *several* separate SQL statements to all succeed together, or not happen at all. Consider placing an order in our online store: we need to (1) insert a new row into `orders`, (2) insert one or more rows into `order_items` for each product purchased, and (3) reduce `stock_quantity` in `products` for each item bought. If step 1 and step 2 succeed but step 3 fails halfway through (say, the server loses power, or a bug throws an unexpected error) — we'd be left with an order recorded, but stock counts left wrong, or partially updated. That's a broken, inconsistent database state.

A **transaction** groups multiple SQL statements into a single "all or nothing" unit. You start one with `beginTransaction()`, run all the statements that belong together, and then either `commit()` (permanently save every change made since the transaction began) or `rollBack()` (undo every change made since the transaction began, as if none of it ever happened). If any statement in the middle fails, you can catch the error and roll back, guaranteeing the database never ends up half-updated.

```php
<?php

try {
    $pdo->beginTransaction();

    // Statement 1
    $pdo->prepare('INSERT INTO orders (order_date, user_id) VALUES (:date, :user_id)')
        ->execute(['date' => date('Y-m-d'), 'user_id' => 5]);
    $orderId = (int) $pdo->lastInsertId();

    // Statement 2
    $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :qty)')
        ->execute(['order_id' => $orderId, 'product_id' => 1, 'qty' => 2]);

    // Statement 3
    $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :product_id')
        ->execute(['qty' => 2, 'product_id' => 1]);

    $pdo->commit(); // Only now are all three changes made permanent.
} catch (PDOException $e) {
    $pdo->rollBack(); // Undo everything since beginTransaction().
    error_log('Order failed, rolled back: ' . $e->getMessage());
    throw new RuntimeException('Could not place the order. Please try again.');
}
```

Because we set `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` back in the first lesson of this chapter, any failed statement inside the `try` block throws a `PDOException` immediately — jumping straight to the `catch` block, where we call `rollBack()` before re-throwing a safe, generic error for the caller.

## Why It Matters

Transactions are the mechanism that keeps money, inventory, and any multi-step business process trustworthy in real applications. Bank transfers (subtract from one account, add to another), e-commerce checkouts (create order, reduce stock, charge payment), and booking systems (reserve seat, charge customer) all require this "all or nothing" guarantee — without it, a crash or bug at the wrong moment could create money out of thin air, oversell inventory, or double-book a seat. This is one of the most consequential concepts in this entire track precisely because getting it wrong causes real financial and data-integrity damage in production systems.

Symfony's Doctrine ORM also supports transactions directly (`$entityManager->wrapInTransaction(...)` or manual `beginTransaction()`/`flush()`/`commit()`), built on exactly the same underlying PDO mechanism you're learning here — so this knowledge transfers directly.

## Example

A customer buys 2 units of the Coffee Mug. The store needs to record the order, record the line item, and reduce stock — as a single atomic transaction, so a mid-way failure can never leave the store's inventory in an inconsistent state.

## Code

```php
<?php

function placeOrder(PDO $pdo, int $userId, int $productId, int $quantity): int
{
    try {
        $pdo->beginTransaction();

        $pdo->prepare('INSERT INTO orders (order_date, user_id) VALUES (:date, :user_id)')
            ->execute(['date' => date('Y-m-d'), 'user_id' => $userId]);
        $orderId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :qty)'
        )->execute(['order_id' => $orderId, 'product_id' => $productId, 'qty' => $quantity]);

        $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :product_id')
            ->execute(['qty' => $quantity, 'product_id' => $productId]);

        $pdo->commit();

        return $orderId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Order placement failed: ' . $e->getMessage());
        throw new RuntimeException('Could not place the order. Please try again.');
    }
}
```

## Code Explanation

- `$pdo->beginTransaction();` tells MySQL to start tracking every change from this point on as a single group, without permanently applying any of them yet.
- The three `prepare()`/`execute()` calls run exactly as they would outside a transaction — the only difference is that MySQL holds their effects in a pending state rather than making them immediately final.
- `$pdo->commit();` is only reached if all three statements ran successfully with no exception — at that point, all three changes become permanent together, atomically.
- If any one of the three statements throws a `PDOException` (say, the `order_items` insert fails because of a constraint violation), execution jumps straight to `catch`, where `$pdo->rollBack();` undoes the `orders` insert that already succeeded — leaving the database exactly as if `placeOrder()` had never been called at all.

## Common Mistakes

- **Forgetting to call `rollBack()` in the `catch` block.** Without it, a failed transaction can be left "open," and depending on the driver and connection reuse, this can cause confusing behavior for subsequent queries on the same connection.
- **Committing too early, or splitting related statements across separate transactions.** If the `orders` insert and `order_items` insert are committed independently, a failure in between still leaves inconsistent data — the whole point of a transaction is to group *all* related statements before committing.
- **Wrapping read-only `SELECT` queries in a transaction unnecessarily.** Transactions matter for statements that *change* data together; a simple, single `SELECT` almost never needs one.
- **Not testing the rollback path.** It's easy to only test the "happy path" where everything succeeds — deliberately testing what happens when one of the middle statements fails is important to confirm your rollback logic actually works.

## Best Practices

- Use a transaction whenever a single logical action requires more than one `INSERT`/`UPDATE`/`DELETE` to succeed together, especially when money, inventory, or any consistency-critical data is involved.
- Always pair `beginTransaction()` with both a `commit()` on the success path and a `rollBack()` in the `catch` block — never leave a transaction started without a clear way to end it either way.
- Keep transactions as short as reasonably possible — avoid slow, unrelated work (like calling an external API) in the middle of a transaction, since it can hold locks on rows for longer than necessary and slow down other users' requests.

## Exercise

Write a PHP function `transferStock(PDO $pdo, int $fromProductId, int $toProductId, int $quantity): void` that moves stock between two products (perhaps merging a discontinued variant into a replacement product): it should decrease `stock_quantity` on `$fromProductId` and increase it by the same amount on `$toProductId`, wrapped in a transaction so both changes happen together or not at all.

## Solution

```php
<?php

function transferStock(PDO $pdo, int $fromProductId, int $toProductId, int $quantity): void
{
    try {
        $pdo->beginTransaction();

        $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id')
            ->execute(['qty' => $quantity, 'id' => $fromProductId]);

        $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id')
            ->execute(['qty' => $quantity, 'id' => $toProductId]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Stock transfer failed: ' . $e->getMessage());
        throw new RuntimeException('Could not transfer stock between products.');
    }
}
```

Both `UPDATE` statements are grouped inside one transaction: if the second one fails for any reason (e.g., an invalid `$toProductId`), `rollBack()` undoes the first `UPDATE` too, so stock is never lost from `$fromProductId` without being correctly added to `$toProductId`.

## Mini Quiz

```yaml
- question: "What is the purpose of a database transaction?"
  options:
    - "To make queries run faster by skipping validation"
    - "To group multiple SQL statements so they either all succeed together (commit) or all get undone together (rollback)"
    - "To permanently delete old data automatically"
    - "To convert a SELECT query into an UPDATE query"
  answer: 1
  explanation: "A transaction guarantees a group of statements is applied as a single all-or-nothing unit, preventing the database from being left in a half-updated, inconsistent state."

- question: "In the placeOrder() example, what happens if the UPDATE to products fails after the INSERT into orders has already succeeded?"
  options:
    - "The order row stays in the database permanently regardless of the failure"
    - "PHP crashes with no way to recover"
    - "The catch block calls rollBack(), which undoes the earlier INSERT into orders as well"
    - "MySQL automatically retries the UPDATE forever"
  answer: 2
  explanation: "Because both statements are inside the same transaction, rollBack() undoes every change made since beginTransaction(), including the earlier successful INSERT."

- question: "Which PDO method permanently saves all changes made since beginTransaction() was called?"
  options:
    - "rollBack()"
    - "commit()"
    - "execute()"
    - "prepare()"
  answer: 1
  explanation: "commit() finalizes and permanently applies every change made during the transaction; rollBack() would instead undo them."
```
