---
slug: prepared-statements
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: php-mysql
chapterTitle: "Chapter 4 — PHP + MySQL"
chapterOrder: 4
title: "Prepared Statements"
difficulty: intermediate
order: 2
estimatedMinutes: 12
tags: [php, pdo, security]
---

## Learning Objective

By the end of this lesson, you will understand exactly why building SQL queries by concatenating strings is dangerous (SQL injection), and how to use PDO prepared statements to safely include user-provided values in a query.

## Explanation

Imagine a login page, or a product search box, where PHP takes a value the user typed and builds a SQL query by simply gluing that value directly into the query string:

```php
<?php
// DANGEROUS — never do this.
$search = $_GET['search']; // e.g. user typed: lamp
$sql = "SELECT * FROM products WHERE name LIKE '%$search%'";
$results = $pdo->query($sql);
```

This looks like it works for a normal search like "lamp." But what if someone types something malicious into that search box instead, like `%' OR '1'='1`? The final query becomes:

```sql
SELECT * FROM products WHERE name LIKE '%%' OR '1'='1%'
```

The condition `'1'='1'` is always true, so this returns *every single product in the table*, regardless of the intended filter. This category of attack is called **SQL injection**: an attacker crafts input that changes the actual *structure* of your SQL query, not just the value being searched for. In real, more severe cases, SQL injection has been used to bypass login checks entirely, read other users' private data, or even delete entire tables — it has been one of the most damaging and common web vulnerabilities for decades.

The fix is **prepared statements**. Instead of building the final SQL string yourself with the user's raw input glued in, you write the query with **placeholders** where values will go, "prepare" it with MySQL first, and then supply the actual values separately, in a second step:

```php
<?php
$stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :search');
$stmt->execute(['search' => '%' . $search . '%']);
$results = $stmt->fetchAll();
```

Here, `:search` is a **named placeholder**. When you call `->execute([...])`, PDO sends the value separately from the query's structure. MySQL treats the placeholder's value strictly as *data* — never as part of the SQL syntax itself — so even if a user types `%' OR '1'='1`, it is only ever treated as a literal search string to look for, never as SQL code that changes the query's meaning. This completely closes off the SQL injection technique shown above.

PDO also supports unnamed, "positional" placeholders using `?`:

```php
<?php
$stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = ? AND price < ?');
$stmt->execute([1, 50]);
```

Both styles work; named placeholders (`:search`) are often preferred for readability once you have more than one or two parameters.

## Why It Matters

SQL injection is a real, well-documented, extremely common vulnerability that has caused serious data breaches at real companies — it consistently appears near the top of industry vulnerability lists (like the OWASP Top 10) year after year. Using prepared statements for *every* query that includes any value coming from outside your own code (form input, URL parameters, cookies, even data from another API) is not optional best practice — it is a baseline, non-negotiable requirement for writing safe PHP database code.

This is also exactly the protection Symfony's Doctrine ORM gives you automatically when you use its query builder or entity methods correctly — but you must still understand *why* it matters, because it's easy to accidentally bypass that protection by dropping down to raw SQL fragments carelessly, even within Doctrine.

## Example

The store's product search feature needs to safely accept a keyword typed by any visitor and filter products by name, without ever allowing that input to alter the query's actual structure.

## Code

```php
<?php

function searchProducts(PDO $pdo, string $keyword): array
{
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE name LIKE :keyword');
    $stmt->execute(['keyword' => '%' . $keyword . '%']);

    return $stmt->fetchAll();
}

// Even a malicious-looking input is handled safely:
$results = searchProducts($pdo, "%' OR '1'='1");
```

## Code Explanation

- `$pdo->prepare('SELECT id, name, price FROM products WHERE name LIKE :keyword')` sends the query's *structure* to MySQL first, with `:keyword` marked as a placeholder — MySQL parses and plans the query shape before ever seeing the actual search value.
- `$stmt->execute(['keyword' => '%' . $keyword . '%'])` supplies the real value for `:keyword` as pure data, in a second, separate step. We build the `%...%` wildcard pattern in PHP first, then pass the whole resulting string as one data value — it is never re-interpreted as SQL syntax, no matter what characters it contains.
- Because the value is always treated as data, calling `searchProducts($pdo, "%' OR '1'='1")` simply searches for a product whose name literally contains that odd string (and finds nothing) — it cannot break out of the intended `LIKE` condition or alter the query's logic.
- `$stmt->fetchAll()` retrieves all matching rows as an array of associative arrays (because we set `PDO::ATTR_DEFAULT_FETCH_MODE` to `PDO::FETCH_ASSOC` in the previous lesson).

## Common Mistakes

- **Concatenating any user input directly into a SQL string**, even "just once," even for something that "seems safe" like a numeric ID from the URL — attackers can still manipulate numeric-looking parameters, and it's simply not worth the risk when prepared statements are just as easy to write.
- **Believing `addslashes()` or manual escaping is "good enough."** Manual escaping is error-prone and has repeatedly been bypassed in real-world attacks through encoding tricks; prepared statements are the industry-standard, reliable solution, and there is no good reason to escape manually instead.
- **Mixing named and positional placeholders in the same query.** PDO does not support combining `:name` and `?` styles together in one statement — pick one style per query.
- **Forgetting that prepared statements protect *values*, not SQL keywords or identifiers.** You cannot use a placeholder for a table name or column name (e.g., `ORDER BY :column` does not work) — those need to be validated against a safe allow-list in your own PHP code instead.

## Best Practices

- Use a prepared statement for every single query that includes any value not hardcoded by you, with no exceptions — treat it as a fixed habit, not a case-by-case judgment call.
- Prefer named placeholders (`:keyword`) for queries with multiple parameters, since they're far easier to read and less error-prone than tracking positional `?` order.
- If you ever need to vary a column or table name dynamically, validate it strictly against a hardcoded allow-list of known-safe names in PHP — never insert it via a placeholder or through unsanitized concatenation.

## Exercise

Write a PHP function `findUserByEmail(PDO $pdo, string $email): array|false` that safely looks up a single row from the `users` table where the `email` column matches the given email, using a prepared statement, and returns the row using `fetch()`.

## Solution

```php
<?php

function findUserByEmail(PDO $pdo, string $email): array|false
{
    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);

    return $stmt->fetch();
}
```

The `:email` placeholder ensures the provided email address is always treated as plain data, no matter what characters it contains, and `fetch()` (rather than `fetchAll()`) returns just one row, or `false` if no user matches.

## Mini Quiz

```yaml
- question: "What is SQL injection?"
  options:
    - "A MySQL performance optimization technique"
    - "An attack where malicious input changes the structure/meaning of a SQL query built by string concatenation"
    - "A type of database backup"
    - "A PHP syntax error"
  answer: 1
  explanation: "SQL injection happens when untrusted input is concatenated directly into a SQL string, allowing an attacker to alter the query's actual logic rather than just supplying a value."

- question: "How do prepared statements prevent SQL injection?"
  options:
    - "They automatically delete any suspicious input"
    - "They send the query structure and the data values separately, so values are always treated as data, never as SQL syntax"
    - "They encrypt the entire database"
    - "They only allow queries typed by an administrator"
  answer: 1
  explanation: "Because the query's structure is sent and prepared before the values are supplied, the database can never reinterpret a value as part of the query's syntax."

- question: "Which of these is a placeholder-safe way to filter by a user-provided ID in PDO?"
  options:
    - "$pdo->query(\"SELECT * FROM products WHERE id = $id\");"
    - "$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id'); $stmt->execute(['id' => $id]);"
    - "$pdo->query('SELECT * FROM products WHERE id = ' . $id);"
    - "eval(\"SELECT * FROM products WHERE id = $id\");"
  answer: 1
  explanation: "Using prepare() with a named placeholder and passing the value through execute() keeps the value separate from the SQL structure, which is the safe pattern."
```
