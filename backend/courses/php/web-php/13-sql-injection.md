---
slug: sql-injection
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "SQL Injection and How to Prevent It"
difficulty: intermediate
order: 13
estimatedMinutes: 12
tags: [php, security, sql, pdo]
---

## Learning Objective

By the end of this lesson, you will be able to explain how SQL injection works, recognize vulnerable query code, and prevent it with PDO prepared statements.

## Explanation

**SQL injection** is an attack in which a visitor's input changes the meaning of a database query. It happens when user input is glued directly into an SQL string. Consider this vulnerable code:

`$sql = "SELECT * FROM users WHERE email = '$email'";`

If `$email` holds a normal address, the query behaves. But if an attacker submits `' OR '1'='1`, the final string becomes `WHERE email = '' OR '1'='1'` — a condition that is true for every row — so the query returns all users instead of one. Attackers use this trick to read, modify, or delete data they should never reach.

The defense is a **prepared statement**: a query template with **placeholders** (named slots like `:email`) sent to the database separately from the values. The database parses the template first, then plugs in the values as pure data that can never alter the query's structure. In **PDO** (PHP Data Objects, the standard database library), you build this with `prepare()` and run it with `execute()`.

## Why It Matters

Injection is consistently one of the most damaging web vulnerabilities because a single vulnerable query can expose an entire database: passwords, emails, orders, everything. Prepared statements eliminate the whole class of bug with barely more code, which is why every database lesson from here on uses them without exception.

## Example

A login script looks up a user by email address submitted through a form. The vulnerable version interpolates the address directly; the safe version below uses a `:email` placeholder so hostile input stays harmless data.

## Code

```php
<?php

$pdo = new PDO(
    'mysql:host=localhost;dbname=online_store;charset=utf8mb4',
    'app_user',
    'secret_password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$email = $_POST['email'] ?? '';

$stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo $user ? 'Found: ' . htmlspecialchars($user['email']) : 'No such user.';
```

## Code Explanation

- The PDO connection enables exception error mode so database failures raise catchable errors instead of failing silently.
- `$pdo->prepare(...)` sends the query template with the `:email` placeholder to MySQL, which parses its structure before seeing any user data.
- `$stmt->execute(['email' => $email])` supplies the raw form input as the placeholder's value; MySQL treats it strictly as data, so quotes inside it cannot break out of the query.
- `fetch(PDO::FETCH_ASSOC)` returns the matching row as an associative array, and `htmlspecialchars()` keeps the echoed email safe for HTML display.

## Common Mistakes

- **Concatenating input into SQL even "just for integers".** Attackers abuse numeric fields too; every variable in a query needs a placeholder.
- **Quoting placeholders like `':email'`.** Placeholders must appear bare in the template; PDO adds quoting itself.
- **Using `query()` with interpolated variables instead of `prepare()`.** `query()` is only safe for fully fixed SQL with no user input at all.

## Best Practices

- Use prepared statements for every query containing any variable, without exceptions.
- Validate input (correct format, expected length) in addition to using placeholders, since validation catches mistakes placeholders cannot.
- Give the database account only the permissions the application needs, so even a bug exposes less.

## Exercise

Rewrite this vulnerable line safely: `$pdo->query("SELECT * FROM products WHERE name = '$search'")`. Use a named placeholder `:search` and fetch all matching rows.

## Solution

```php
<?php

$stmt = $pdo->prepare('SELECT * FROM products WHERE name = :search');
$stmt->execute(['search' => $search]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

The template fixes the query structure, the input travels separately as data, and `fetchAll()` collects every matching product row.

## Mini Quiz

```yaml
- question: "What is SQL injection?"
  options:
    - "A way to make queries run faster"
    - "An attack where crafted input alters the structure of a database query"
    - "A tool for backing up MySQL databases"
    - "A PHP function for formatting dates"
  answer: 1
  explanation: "Injection happens when input is concatenated into SQL so that hostile characters change what the query actually does."
- question: "How do prepared statements prevent SQL injection?"
  options:
    - "They hide the database password"
    - "They send the query structure and the values separately so values can never change the query logic"
    - "They encrypt the entire database"
    - "They block all users from logging in"
  answer: 1
  explanation: "Because placeholders are parsed as structure first and values arrive later as pure data, input cannot break out into SQL commands."
- question: "Which PDO methods form a safe parameterized query?"
  options:
    - "query() with string concatenation"
    - "prepare() followed by execute() with bound values"
    - "exec() with interpolated variables"
    - "fetch() with no query at all"
  answer: 1
  explanation: "prepare() defines the template with placeholders and execute() supplies the values safely as data."
```
