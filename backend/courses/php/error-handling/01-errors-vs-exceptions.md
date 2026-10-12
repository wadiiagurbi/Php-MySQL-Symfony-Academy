---
slug: errors-vs-exceptions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: error-handling
chapterTitle: "Chapter 4 — Error Handling"
chapterOrder: 4
title: "Errors vs. Exceptions"
difficulty: intermediate
order: 1
estimatedMinutes: 9
tags: [errors, exceptions, php-basics, throwable]
---

## Learning Objective

By the end of this lesson, you will be able to explain the difference between a PHP "error" and a PHP "exception", describe what happens when a script hits a problem it cannot recover from, and read a simple example that shows an exception being thrown and caught.

## Explanation

Every program eventually runs into something unexpected: a file that does not exist, a value that is the wrong type, or a calculation that does not make mathematical sense (like dividing by zero). When PHP hits one of these problems, it has to tell you — and your program — that something went wrong. Historically, PHP has had two related but different ways of doing this: **errors** and **exceptions**.

An **error** in PHP usually refers to a problem reported directly by the PHP engine while it is running your script. PHP has several severity levels of these, including:

- **Notice** — a minor issue, such as using a variable that was never given a value. The script keeps running.
- **Warning** — something more serious, such as calling a function with the wrong number of arguments. The script still keeps running, but the operation likely did not do what you expected.
- **Fatal error** — a problem so severe that PHP cannot continue at all, such as calling a function that does not exist. The script stops immediately.

For a long time, fatal errors were a dead end: you could not "catch" them or write code to react to them — the script just stopped, often showing a scary technical message to whoever was viewing the page.

An **exception** is a different, more flexible concept. An exception is an object — a small package of information — that represents "something exceptional happened here." Instead of PHP just stopping the script, your code (or PHP itself) can **throw** an exception, and other code can **catch** it, meaning it gets a chance to notice the problem and decide what to do: show a friendly message, try something else, or log the details for a developer to look at later. This "throw and catch" mechanism is called **exception handling**, and we will cover its full syntax in the next lesson.

Since PHP 7, the language merged these two worlds together. Many situations that used to be uncatchable fatal errors are now thrown as `Error` objects (for example, calling a method on something that is not an object throws a `TypeError` or `Error`). Both `Error` and `Exception` implement a shared interface called `Throwable`, which means code written to catch `Throwable` can catch almost anything that goes wrong in modern PHP, whether it originated as a classic "error" or a true "exception".

A **stack trace** is another term you will see constantly once you start dealing with errors and exceptions. It is simply a list showing which function called which other function, in order, leading up to the point where the problem occurred — like a trail of breadcrumbs that helps you find exactly where things went wrong.

## Why It Matters

In a real production website, you never want a visitor to see PHP's raw error output. Not only does it look unprofessional, it can leak sensitive information — file paths on your server, database table names, or snippets of your source code — that an attacker could use against you. Understanding the difference between errors and exceptions is the first step toward building applications that fail gracefully: showing the user a polite "Something went wrong, please try again" page while a developer is quietly notified with the real technical details in a log file. Nearly every framework and library you will use professionally (Symfony, Laravel, WordPress plugins, API clients) is built entirely around throwing and catching exceptions, so this vocabulary is foundational to everything that follows in this chapter.

## Example

Imagine a small script that tries to open a required configuration file that does not exist. Without any exception handling, PHP would either emit a warning and continue with a broken configuration, or in some cases, throw an exception that immediately halts the script with an ugly message. Below is a minimal example that shows PHP throwing a built-in exception on its own, and a second version where we catch it ourselves.

## Code

```php
<?php

// PHP itself throws exceptions for certain problems.
// Dividing by zero is a good example: in PHP 8+, this throws
// a DivisionByZeroError, which is an object implementing Throwable.

function safeDivide(int $a, int $b): float
{
    // Without any handling, this line would let the DivisionByZeroError
    // escape and stop the script if $b is 0.
    return $a / $b;
}

// This call works fine.
echo safeDivide(10, 2) . PHP_EOL; // 5

// This call would normally crash the script. We catch it instead,
// which is the basic pattern the next lesson explains in full.
try {
    echo safeDivide(10, 0) . PHP_EOL;
} catch (\DivisionByZeroError $e) {
    echo "Could not divide: " . $e->getMessage() . PHP_EOL;
}

echo "Script kept running after the problem." . PHP_EOL;
```

## Code Explanation

- `function safeDivide(int $a, int $b): float` declares a function with typed parameters and a typed return value. This is not required for exceptions to work, but it is good practice and helps PHP catch mistakes earlier.
- `return $a / $b;` is where the danger lives: if `$b` is `0`, PHP 8+ throws a `DivisionByZeroError` object instead of just producing a warning like older PHP versions did.
- The first call, `safeDivide(10, 2)`, has no problem, so it just returns `5` and execution continues normally.
- The second call is wrapped in a `try` block. This tells PHP "attempt to run this code, but be ready to catch a problem if one happens."
- `catch (\DivisionByZeroError $e)` says "if a `DivisionByZeroError` occurs inside the `try` block, run this code instead of crashing." The `$e` variable holds the exception object itself.
- `$e->getMessage()` is a method every `Throwable` object provides — it returns a human-readable description of what went wrong.
- Because the exception was caught, the line `echo "Script kept running..."` is reached — proof that the program survived the problem instead of stopping dead.

## Common Mistakes

- **Assuming all problems are catchable the old way.** Beginners sometimes try to use `error_reporting()` tricks to "catch" fatal errors instead of learning `try`/`catch`. Since PHP 7+, many of those are `Throwable` objects and should be caught with proper exception handling.
- **Confusing `Exception` and `Error`.** They are siblings, not parent/child. Both implement `Throwable`, but `catch (\Exception $e)` will NOT catch an `Error` object like `TypeError` or `DivisionByZeroError`. If you want to catch anything at all, catch `\Throwable`.
- **Ignoring warnings and notices during development.** They often point to real bugs (like typos in variable names) that will eventually cause a much bigger failure in production.

## Best Practices

- Turn on full error reporting (`error_reporting(E_ALL)`) while developing locally, so small issues surface immediately instead of hiding until production.
- Learn to read a stack trace from the bottom up (oldest call) or top down (most recent call), depending on your tools — get comfortable identifying which line actually triggered the problem.
- Treat `Throwable` as the "catch absolutely everything" type, and more specific exception/error classes as tools to react precisely to specific situations.

## Exercise

Write a function called `getListItem(array $list, int $index): mixed` that returns the item at `$index` in `$list`. If `$index` does not exist in the array, PHP will normally just emit a warning and return `null`. Instead, make your function throw a built-in `\OutOfRangeException` with a message like `"Index 5 does not exist"` when the index is missing. Then write code that calls your function with an invalid index inside a `try`/`catch` block and prints the exception's message instead of letting the script crash.

## Solution

```php
<?php

function getListItem(array $list, int $index): mixed
{
    if (!array_key_exists($index, $list)) {
        throw new \OutOfRangeException("Index {$index} does not exist");
    }

    return $list[$index];
}

$fruits = ["apple", "banana", "cherry"];

try {
    echo getListItem($fruits, 1) . PHP_EOL; // banana
    echo getListItem($fruits, 5) . PHP_EOL; // triggers the exception
} catch (\OutOfRangeException $e) {
    echo "Problem: " . $e->getMessage() . PHP_EOL;
}
```

`array_key_exists($index, $list)` checks whether that index is actually present, which is safer than just checking if the value is "empty" (a valid item could legitimately be `0` or an empty string). When the index is missing, we `throw` a new `\OutOfRangeException` object built with a descriptive message. The calling code wraps both function calls in a `try` block, so when the second call throws, control jumps straight to the matching `catch` block, prints a friendly message, and the script finishes normally instead of crashing.

## Mini Quiz

```yaml
- question: "What is the key difference between a classic PHP fatal error (before PHP 7) and an exception?"
  options:
    - "Fatal errors could always be caught with try/catch, exceptions could not"
    - "Exceptions are objects that code can catch and react to; classic fatal errors simply stopped the script"
    - "There is no difference, they are the same feature under different names"
    - "Exceptions only exist in other languages, not in PHP"
  answer: 1
  explanation: "An exception is an object representing a problem that other code can catch and react to. Classic fatal errors, before PHP 7 unified things under Throwable, simply halted script execution with no way to intervene."
- question: "What interface do both Error and Exception implement in modern PHP?"
  options:
    - "Catchable"
    - "Throwable"
    - "Recoverable"
    - "ErrorHandler"
  answer: 1
  explanation: "Both Error and Exception implement the Throwable interface, which is why code that wants to catch anything that can go wrong typically catches Throwable."
- question: "What does a stack trace show you?"
  options:
    - "The current values of every variable in your script"
    - "The list of function calls, in order, that led to the point where a problem occurred"
    - "The amount of memory your script is using"
    - "A list of all files included in your project"
  answer: 1
  explanation: "A stack trace is an ordered list of function calls showing how execution reached the point where the error or exception occurred, which helps you trace the problem back to its source."
```
