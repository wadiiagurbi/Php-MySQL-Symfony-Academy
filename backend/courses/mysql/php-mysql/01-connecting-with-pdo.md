---
slug: connecting-with-pdo
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: php-mysql
chapterTitle: "Chapter 4 — PHP + MySQL"
chapterOrder: 4
title: "Connecting to MySQL with PDO"
difficulty: intermediate
order: 1
estimatedMinutes: 10
tags: [php, pdo, mysql]
---

## Learning Objective

By the end of this lesson, you will be able to open a connection from a PHP script to a MySQL database using PDO (PHP Data Objects), understand the connection string ("DSN"), and handle connection errors gracefully.

## Explanation

Everything up to this chapter has been about talking to MySQL directly, through a command line or admin tool. But real applications talk to MySQL *from code* — in our case, from PHP. **PDO** (PHP Data Objects) is PHP's standard, built-in library for connecting to databases. It works with MySQL and several other database systems using a consistent, unified interface, which is why it's the recommended way to access databases from plain PHP (and it's also what Symfony's Doctrine ultimately uses under the hood to talk to the database).

To open a connection, you create a new `PDO` object, giving it three things:

1. A **DSN** (Data Source Name) — a connection string describing which database driver, host, and database name to use, e.g. `mysql:host=localhost;dbname=online_store;charset=utf8mb4`.
2. A **username** and **password** for the MySQL account.
3. Optional **options**, configuring how PDO should behave (we'll use one very important option below).

```php
<?php

$dsn = 'mysql:host=localhost;dbname=online_store;charset=utf8mb4';
$username = 'app_user';
$password = 'secret_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

Connecting can fail for many reasons — wrong password, MySQL server not running, wrong database name — so PDO signals failures by *throwing an exception* (`PDOException`) rather than returning `false` or a similar quiet failure value. That's why the connection attempt is wrapped in a `try`/`catch` block: if something goes wrong, we catch the exception and handle it, instead of letting the script crash with an unhandled error (or, worse, silently continue as if nothing happened).

One crucial option to always set is the **error mode**. By default in some PHP versions, PDO can silently ignore errors on individual queries (not just the initial connection) unless you tell it to throw exceptions for those too:

```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];
$pdo = new PDO($dsn, $username, $password, $options);
```

With this option set, *every* PDO operation that fails — not just connecting, but every query afterward — throws a `PDOException` you can catch, instead of failing silently and leaving you to debug a mysterious blank page.

## Why It Matters

The database connection is the foundation of every dynamic PHP page that reads or writes real data — nothing else in your application works without it. Handling connection failures properly (with `try`/`catch`, rather than letting a fatal, uncaught error dump database credentials or a raw stack trace onto the page) is a basic but important security and reliability practice: an unhandled `PDOException` can leak your DSN, hostname, or other configuration details into an error page shown to real users.

Setting `PDO::ERRMODE_EXCEPTION` consistently is what makes every later lesson in this chapter (prepared statements, CRUD, transactions) predictable and debuggable — without it, a broken query might fail silently, and you'd have no idea why your data isn't behaving as expected.

## Example

Our online store's PHP backend needs a small, reusable piece of code that opens a connection to the `online_store` database whenever a script needs to read or write data — for example, when a customer views the product catalog.

## Code

```php
<?php

function connectToDatabase(): PDO
{
    $dsn = 'mysql:host=127.0.0.1;dbname=online_store;charset=utf8mb4';
    $username = 'app_user';
    $password = 'secret_password';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Unable to connect to the database.');
    }
}

$pdo = connectToDatabase();
echo "Connected to the online_store database.";
```

## Code Explanation

- `function connectToDatabase(): PDO` wraps the connection logic in a reusable function that returns a ready-to-use `PDO` object, so the rest of the application doesn't need to repeat the DSN and credentials everywhere.
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` makes every future database operation on this connection throw exceptions on failure, which we'll rely on throughout the rest of this chapter.
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` tells PDO that, by default, query results should come back as associative arrays (e.g. `['id' => 1, 'name' => 'Coffee Mug']`) rather than PHP's default mixed numeric+associative arrays — this makes result data much more readable in your code.
- Inside the `catch` block, we log the real error with `error_log()` (useful for developers looking at server logs) but throw a *generic*, safe `RuntimeException` instead of exposing the raw database error message to whatever code called this function — this prevents sensitive details (like part of the DSN) from ever reaching an end user.

## Common Mistakes

- **Hardcoding credentials directly in version-controlled code.** Database passwords should come from environment variables or a configuration file excluded from version control, never committed directly into a PHP file.
- **Letting a `PDOException` bubble all the way up to the browser.** An uncaught exception can display a full stack trace to users in some server configurations, potentially revealing your database host, username, or file paths — always catch connection errors and show a generic message instead.
- **Forgetting `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.** Without it, some query failures return `false` silently instead of throwing, and beginners often spend a long time debugging "nothing happens" bugs that a thrown exception would have made obvious immediately.
- **Reconnecting on every single query.** Opening a new `PDO` connection for every query in a loop is wasteful; create one connection and reuse it for multiple queries within the same request.

## Best Practices

- Always set `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` on every connection you create.
- Keep the DSN, username, and password out of your source code — load them from environment variables (in Symfony, this is exactly what the `.env` file and `DATABASE_URL` are for).
- Wrap connection attempts in `try`/`catch`, log the detailed error for developers, and show only a safe, generic message to the end user.

## Exercise

Write a PHP function `connectToDatabase()` for a hypothetical `blog` database running on host `localhost`, using username `blog_user` and password `blog_pass`, that sets both the exception error mode and the associative fetch mode, and safely handles a connection failure by throwing a generic `RuntimeException`.

## Solution

```php
<?php

function connectToDatabase(): PDO
{
    $dsn = 'mysql:host=localhost;dbname=blog;charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO($dsn, 'blog_user', 'blog_pass', $options);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Unable to connect to the database.');
    }
}
```

This mirrors the online store example: it builds a DSN for the `blog` database, sets the two important options, and safely converts any `PDOException` into a generic error for callers, while logging the real cause for developers.

## Mini Quiz

```yaml
- question: "What does PDO do when a database connection or query fails, if PDO::ATTR_ERRMODE is set to PDO::ERRMODE_EXCEPTION?"
  options:
    - "It silently returns false"
    - "It throws a PDOException that can be caught with try/catch"
    - "It automatically retries the connection forever"
    - "It crashes the entire PHP process with no way to recover"
  answer: 1
  explanation: "With ERRMODE_EXCEPTION set, PDO throws a catchable PDOException on failure, which is why connection code is typically wrapped in try/catch."

- question: "Why is it a bad idea to let a raw PDOException message reach the end user in a browser?"
  options:
    - "It has no downside at all"
    - "It can leak sensitive details like database host, username, or file paths"
    - "PHP does not allow displaying exception messages"
    - "It always crashes the web server"
  answer: 1
  explanation: "Raw database error messages can expose configuration details that should stay private; catching the exception and showing a generic message avoids this leak."

- question: "What does the DSN string 'mysql:host=127.0.0.1;dbname=online_store;charset=utf8mb4' specify?"
  options:
    - "Only the username to connect with"
    - "The database driver (mysql), host, database name, and character set to use for the connection"
    - "A SQL query to run immediately after connecting"
    - "The PHP version required to run PDO"
  answer: 1
  explanation: "The DSN tells PDO which driver to use and how to reach the specific database — host, database name, and character encoding — but not credentials, which are passed separately."
```
