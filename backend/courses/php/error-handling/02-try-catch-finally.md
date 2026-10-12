---
slug: try-catch-finally
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: error-handling
chapterTitle: "Chapter 4 — Error Handling"
chapterOrder: 4
title: "try, catch, and finally"
difficulty: intermediate
order: 2
estimatedMinutes: 11
tags: [exceptions, try-catch, finally, error-handling]
---

## Learning Objective

By the end of this lesson, you will be able to write a full `try`/`catch`/`finally` block, catch different exception types separately, and understand why `finally` is useful for cleanup work such as closing files or database connections.

## Explanation

In the previous lesson, you saw that PHP represents problems as objects (`Exception`, `Error`, and other classes that implement `Throwable`), and that these objects can be **thrown** and **caught**. Now let's look at the full syntax for catching them.

The basic structure has three parts:

```
try {
    // code that might throw an exception or error
} catch (SomeExceptionType $e) {
    // code that runs if that specific type was thrown
} finally {
    // code that always runs, whether or not anything was thrown
}
```

- The **`try`** block contains the code you want to attempt. PHP runs it normally until either it finishes successfully, or something inside it throws a `Throwable`.
- The **`catch`** block only runs if something was thrown, and only if the type you wrote matches (or is a parent type of) what was actually thrown. The variable in parentheses, like `$e`, holds the exception object so you can inspect it — call `$e->getMessage()`, `$e->getCode()`, or `$e->getTraceAsString()` to get details about what happened.
- The **`finally`** block runs no matter what: whether the `try` block succeeded, whether an exception was caught, or even if the exception was NOT caught (it still runs before the exception continues propagating upward). This makes it the perfect place for cleanup code, like closing an open file handle or a database connection, that absolutely must happen either way.

You are also allowed to write **multiple `catch` blocks** in a row, each for a different exception type. PHP checks them in order, top to bottom, and runs the first one whose type matches. This lets you react differently depending on exactly what went wrong:

```
try {
    // ...
} catch (\InvalidArgumentException $e) {
    // handle bad input specifically
} catch (\DivisionByZeroError $e) {
    // handle a math problem specifically
} catch (\Throwable $e) {
    // catch absolutely anything else as a fallback
}
```

Order matters here: always put more specific exception types before more general ones. If you put `\Throwable` first, it would catch everything and the more specific `catch` blocks below it would never run, because PHP only ever runs the first matching block.

You can also catch several types with one block using the pipe symbol, like `catch (\InvalidArgumentException | \TypeError $e)`, when you want to treat two different exception types the same way.

## Why It Matters

Real applications constantly deal with unreliable things: user input that might be malformed, files that might be missing, network requests that might time out, database queries that might fail. Without `try`/`catch`, a single one of these problems would crash the entire request and show the visitor a broken page. With `try`/`catch`, you can catch the specific problem, show a helpful message ("Please enter a valid email address"), and let the rest of the application keep running normally.

`finally` matters just as much in professional code. Imagine you open a file or a database connection at the start of a `try` block. If an exception happens halfway through, and you forget to close that resource, you can leak file handles or database connections — over time this can slow down or even crash a server under load. Putting the cleanup code in `finally` guarantees it always runs, so resources are never left open by accident, regardless of whether things succeeded or failed.

## Example

Consider a small utility that parses a numeric string typed by a user and divides another number by it. Two things can go wrong: the string might not be a valid number, or the number might be zero. We will catch each situation separately, and use `finally` to always print a line confirming the operation finished.

## Code

```php
<?php

function parseNumber(string $value): float
{
    if (!is_numeric($value)) {
        throw new \InvalidArgumentException("'{$value}' is not a valid number");
    }

    return (float) $value;
}

function divide(float $numerator, string $userInput): void
{
    try {
        $denominator = parseNumber($userInput);
        $result = $numerator / $denominator; // may throw DivisionByZeroError
        echo "Result: {$result}" . PHP_EOL;
    } catch (\InvalidArgumentException $e) {
        echo "Input error: " . $e->getMessage() . PHP_EOL;
    } catch (\DivisionByZeroError $e) {
        echo "Math error: cannot divide by zero" . PHP_EOL;
    } finally {
        echo "Finished attempt to divide {$numerator} by '{$userInput}'." . PHP_EOL;
    }
}

divide(10, "5");     // Result: 2
divide(10, "abc");   // Input error: 'abc' is not a valid number
divide(10, "0");     // Math error: cannot divide by zero
```

## Code Explanation

- `parseNumber()` checks the input with `is_numeric()`. If the string is not a valid number (like `"abc"`), it throws an `\InvalidArgumentException` — a built-in exception type meant exactly for "you gave me a bad argument."
- Inside `divide()`, the `try` block first calls `parseNumber()`, which might throw the exception above, and then performs the division, which might throw `\DivisionByZeroError` if the parsed number is `0`.
- The first `catch (\InvalidArgumentException $e)` block only runs if the string could not be parsed as a number at all.
- The second `catch (\DivisionByZeroError $e)` block only runs if parsing succeeded but the resulting number was zero. Note these are two completely different problems, handled with two completely different messages.
- The `finally` block runs after every single call to `divide()`, regardless of which path was taken — successful division, invalid input, or division by zero — which is why you see the "Finished attempt..." line print all three times in the output.
- Because each problem is caught, the script never crashes and moves on to try the next call.

## Common Mistakes

- **Catching `\Exception` (or `\Throwable`) as the very first and only catch block.** This "catches everything the same way," hiding real bugs (like a `TypeError` from a coding mistake) behind a generic message meant for expected problems like bad user input.
- **Putting broad catch types before narrow ones.** If `catch (\Throwable $e)` appears before `catch (\InvalidArgumentException $e)`, the narrower block becomes dead code — it will never execute.
- **Doing cleanup inside the `try` block only, and forgetting the exception path.** If you close a file handle as the last line of `try`, and an exception is thrown earlier in that same block, the close line never runs. Putting it in `finally` avoids this.
- **Swallowing exceptions silently** — writing an empty `catch` block that does nothing. This hides real problems from developers, making bugs almost impossible to diagnose later. At minimum, log the message even if you choose not to show it to the user.

## Best Practices

- Catch the most specific exception type you can reasonably expect, and only fall back to `\Throwable` as a last safety net, usually paired with logging (covered in a later lesson).
- Use `finally` for anything that must always happen: closing files, releasing database connections, or releasing locks.
- Keep `try` blocks focused on the risky operation itself, rather than wrapping large chunks of unrelated code, so it stays clear exactly what could fail and why.

## Exercise

Write a function `readConfigValue(array $config, string $key): string` that looks up `$key` in the `$config` array and returns its value as a string. If the key does not exist, throw an `\OutOfBoundsException` with the message `"Missing config key: {$key}"`. If the value exists but is an empty string, throw a `\RuntimeException` with the message `"Config key '{$key}' is empty"`. Write calling code with a `try` block that has separate `catch` blocks for each exception type, plus a `finally` block that always prints `"Config lookup finished."`.

## Solution

```php
<?php

function readConfigValue(array $config, string $key): string
{
    if (!array_key_exists($key, $config)) {
        throw new \OutOfBoundsException("Missing config key: {$key}");
    }

    $value = $config[$key];

    if ($value === '') {
        throw new \RuntimeException("Config key '{$key}' is empty");
    }

    return $value;
}

$config = ['app_name' => 'MyApp', 'api_key' => ''];

foreach (['app_name', 'api_key', 'db_host'] as $key) {
    try {
        echo readConfigValue($config, $key) . PHP_EOL;
    } catch (\OutOfBoundsException $e) {
        echo "Missing setting: " . $e->getMessage() . PHP_EOL;
    } catch (\RuntimeException $e) {
        echo "Bad setting: " . $e->getMessage() . PHP_EOL;
    } finally {
        echo "Config lookup finished." . PHP_EOL;
    }
}
```

This loops over three keys: `app_name` exists and has a value, so it prints normally. `api_key` exists but is an empty string, so `\RuntimeException` is thrown and caught by the second `catch` block. `db_host` does not exist at all, so `\OutOfBoundsException` is thrown and caught by the first `catch` block. In all three iterations, the `finally` block runs and prints its confirmation line, proving it executes regardless of which path was taken.

## Mini Quiz

```yaml
- question: "When does the finally block run?"
  options:
    - "Only when no exception was thrown"
    - "Only when an exception was caught"
    - "Always, whether or not an exception was thrown or caught"
    - "Only if you manually call it"
  answer: 2
  explanation: "The finally block always runs after the try/catch, regardless of whether the try block succeeded, an exception was caught, or even if it was not caught at all."
- question: "Why must more specific catch blocks be written before more general ones like \\Throwable?"
  options:
    - "PHP requires alphabetical order for catch blocks"
    - "PHP runs the first matching catch block, so a general type listed first would prevent more specific blocks below it from ever running"
    - "It has no effect on behavior, only on code style"
    - "General catch blocks must always be written first for performance reasons"
  answer: 1
  explanation: "PHP checks catch blocks in order and executes the first one that matches. If a general type like Throwable appears first, it intercepts everything, so any more specific catch blocks written after it become unreachable."
- question: "What is a good real-world use for the finally block?"
  options:
    - "Formatting output text for the user"
    - "Declaring new variables for later use"
    - "Closing a file handle or database connection so it always happens whether or not an error occurred"
    - "Defining the function's return type"
  answer: 2
  explanation: "finally guarantees cleanup code runs no matter what happened in the try block, making it ideal for releasing resources like open files or database connections."
```
