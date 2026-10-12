---
slug: logging-and-debugging
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: error-handling
chapterTitle: "Chapter 4 — Error Handling"
chapterOrder: 4
title: "Logging and Debugging PHP"
difficulty: intermediate
order: 4
estimatedMinutes: 10
tags: [logging, debugging, error-log, php-ini]
---

## Learning Objective

By the end of this lesson, you will be able to explain what logging is and why it matters, use `error_log()` to record information instead of displaying it, understand the difference between the `display_errors` and `log_errors` settings, and use basic debugging tools like `var_dump()` and `print_r()` to inspect what your code is actually doing.

## Explanation

**Logging** means recording what happened while a program ran — usually by writing text messages to a file — so that a developer can look back at it later to understand what went wrong, without the end user ever seeing those technical details. Think of it like a ship's logbook: the crew does not announce every entry to the passengers, but if something goes wrong, the captain can read the log afterward to reconstruct exactly what happened and when.

This matters enormously for PHP websites. Earlier in this chapter, you learned that you can `throw` and `catch` exceptions instead of letting the script crash. But catching an exception is only half the job — once you catch it, you must decide what to do with the information. Showing the raw exception message and stack trace directly to a website visitor is a serious problem for two reasons: it looks unprofessional, and it can be a **security risk**. A raw PHP error message can reveal your server's file paths, the names of your database tables, or fragments of your source code — all useful information for someone trying to attack your site. Instead, production applications record ("log") the full technical details somewhere private, and show the visitor a simple, generic message like "Something went wrong. Please try again later."

PHP has a built-in function for this: `error_log(string $message)`. Calling it writes the given message to PHP's configured error log (often a file on the server, though it can also be configured to send messages elsewhere). It does **not** display anything to the browser — it is purely for the developer's records.

PHP's configuration file, called `php.ini`, has two related settings that control this behavior:

- **`display_errors`** controls whether PHP prints error details directly into the page output, where a visitor could see them. This should be turned **off** (`Off` or `0`) on a production server.
- **`log_errors`** controls whether PHP writes error details to a log file instead. This should be turned **on** (`On` or `1`) on a production server, so nothing is lost — it just is not shown to visitors.

In other words, a well-configured production server has `display_errors = Off` and `log_errors = On`: nothing leaks to visitors, but everything is still recorded for developers to review. During local development, many developers temporarily turn `display_errors` on, since seeing the error immediately on screen speeds up fixing bugs — but this should never be left on for a live, public-facing site.

Beyond logging, PHP gives you a few essential tools for **debugging** — the general process of investigating why your code is not behaving as expected:

- **`var_dump($value)`** prints the type and value of any variable, including nested arrays and objects, in a detailed technical format. It is extremely useful for seeing exactly what a variable contains, including subtle differences like whether a value is the string `"0"` versus the integer `0`.
- **`print_r($value)`** prints a more human-readable version of arrays and objects, without showing as much type detail as `var_dump()`.
- Reading your log files is itself a debugging technique — often the very first thing to check when something is broken in production is "what does the log say happened right before this went wrong?"

## Why It Matters

Imagine a real e-commerce site where a payment fails due to a bug in the checkout code. If the raw PHP exception message is displayed directly on the page, the customer sees a wall of confusing, frightening technical text — and possibly private information about how the store's systems are built. Worse, if the store never records that failure anywhere, the developers may never even find out a customer had a broken checkout experience. Proper logging closes both gaps: the customer sees a calm, simple message, and the developers can open the log the next morning, find the exact exception message and stack trace, and fix the underlying bug. This "log it, don't show it" discipline is one of the most important habits separating hobby scripts from real, professional PHP applications.

## Example

Consider a function that loads a user's profile data from a database. If the lookup fails, we catch the resulting exception, log the technical details using `error_log()`, and show the visitor a safe, generic message instead of the raw exception.

## Code

```php
<?php

class UserNotFoundException extends \Exception
{
}

function fetchUserProfile(array $users, int $userId): array
{
    if (!array_key_exists($userId, $users)) {
        throw new UserNotFoundException("No user found with id {$userId}");
    }

    return $users[$userId];
}

function renderProfilePage(array $users, int $userId): string
{
    try {
        $profile = fetchUserProfile($users, $userId);
        return "Welcome, {$profile['name']}!";
    } catch (\Throwable $e) {
        // Record the real, technical details for developers.
        error_log(
            sprintf(
                "Failed to render profile for user %d: %s",
                $userId,
                $e->getMessage()
            )
        );

        // Show the visitor a safe, generic message only.
        return "Sorry, we could not load your profile right now.";
    }
}

$users = [
    1 => ['name' => 'Amina'],
    2 => ['name' => 'Chen'],
];

echo renderProfilePage($users, 1) . PHP_EOL; // Welcome, Amina!
echo renderProfilePage($users, 99) . PHP_EOL; // safe generic message

// A quick debugging aid: inspect the whole $users array during development.
var_dump($users);
```

## Code Explanation

- `UserNotFoundException` is a custom exception (from the previous lesson's pattern) used to represent one specific, expected problem: the requested user does not exist.
- `fetchUserProfile()` throws that exception when the given `$userId` is not a key in the `$users` array.
- `renderProfilePage()` wraps the risky lookup in a `try` block and catches `\Throwable`, meaning it will catch this custom exception and also any unexpected error (like a `TypeError`), since we want the visitor-facing fallback message to appear no matter what goes wrong.
- Inside `catch`, `error_log(...)` records a detailed technical message — including the user ID and the real exception message — to PHP's error log, purely for developers.
- The function then `return`s a short, safe, generic message. Crucially, `$e->getMessage()` and any stack trace are never included in what gets shown to the visitor.
- The first call succeeds normally and returns a personalized welcome message. The second call, with a nonexistent user ID `99`, triggers the exception, logs it quietly, and returns the generic fallback text instead.
- `var_dump($users)` at the end is an example of a debugging aid you would typically only leave in during local development — it prints the full structure and types of the array, which is useful for confirming your data looks the way you expect, but should be removed before code goes to production.

## Common Mistakes

- **Displaying `$e->getMessage()` or `$e->getTraceAsString()` directly in HTML sent to visitors.** This is one of the most common security mistakes in beginner PHP projects — it can expose file paths, database structure, and other internals.
- **Leaving `display_errors = On` on a live production server.** This setting is meant for local development only; on a public site it turns every bug into a potential information leak.
- **Catching an exception and doing nothing with it at all** (an empty `catch` block, sometimes called "swallowing" the exception). This makes bugs invisible — no one is ever told anything went wrong, so it can go unnoticed for a long time.
- **Leaving debugging calls like `var_dump()` or `print_r()` in production code.** They can leak internal data to visitors and add unnecessary output or performance overhead.

## Best Practices

- On production servers, always configure `display_errors = Off` and `log_errors = On`, so problems are recorded but never shown raw to visitors.
- Log enough context to actually be useful later — include identifiers (like a user ID or order number), not just a bare message, so you can find the related data afterward.
- Use `var_dump()` and `print_r()` freely while developing locally, but remove or guard them (for example, behind an environment check) before deploying.
- Treat your logs as a first destination when investigating a bug report — check what was recorded around the time the problem occurred before guessing at causes.

## Exercise

Write a function `loadSetting(array $settings, string $key): string` that returns the value for `$key`, or throws a `\RuntimeException` with message `"Missing setting: {$key}"` if the key does not exist. Then write a function `getSettingSafely(array $settings, string $key): string` that calls `loadSetting()` inside a `try`/`catch`, uses `error_log()` to record the real exception message (prefix it with `"Settings error: "`), and returns the safe fallback string `"default"` to the caller instead of letting the exception escape.

## Solution

```php
<?php

function loadSetting(array $settings, string $key): string
{
    if (!array_key_exists($key, $settings)) {
        throw new \RuntimeException("Missing setting: {$key}");
    }

    return $settings[$key];
}

function getSettingSafely(array $settings, string $key): string
{
    try {
        return loadSetting($settings, $key);
    } catch (\RuntimeException $e) {
        error_log("Settings error: " . $e->getMessage());
        return "default";
    }
}

$settings = ['theme' => 'dark'];

echo getSettingSafely($settings, 'theme') . PHP_EOL;   // dark
echo getSettingSafely($settings, 'language') . PHP_EOL; // default
```

`loadSetting()` throws a `\RuntimeException` whenever the requested key is missing, describing exactly which key was not found. `getSettingSafely()` is the protective wrapper: it attempts the real lookup, and if it fails, records the precise, technical reason with `error_log()` (useful for a developer checking the log later) while returning a harmless fallback value, `"default"`, to whatever code called it. This mirrors the real pattern used throughout the earlier example: catch the specific problem, log the truth, return something safe.

## Mini Quiz

```yaml
- question: "What does the error_log() function do in PHP?"
  options:
    - "It displays the error message directly on the web page"
    - "It writes a message to PHP's configured error log instead of showing it to the visitor"
    - "It automatically fixes the underlying bug"
    - "It deletes old log files to save disk space"
  answer: 1
  explanation: "error_log() records a message to PHP's error log (commonly a file) for developers to review later, without displaying anything in the page sent to the visitor."
- question: "On a production server, what should display_errors and log_errors typically be set to?"
  options:
    - "display_errors On, log_errors Off"
    - "display_errors On, log_errors On"
    - "display_errors Off, log_errors On"
    - "display_errors Off, log_errors Off"
  answer: 2
  explanation: "Production servers should turn display_errors off so raw error details never reach visitors, while keeping log_errors on so those details are still recorded for developers to investigate."
- question: "Why is showing a raw exception message directly to website visitors considered risky?"
  options:
    - "It always crashes the browser"
    - "It can reveal sensitive internal details like file paths or database structure to potential attackers"
    - "It makes the page load more slowly"
    - "PHP does not allow exception messages to be displayed at all"
  answer: 1
  explanation: "Raw exception messages and stack traces can expose internal implementation details, such as server file paths or database schema information, which could help an attacker target the application."
- question: "What is the main difference between var_dump() and print_r()?"
  options:
    - "var_dump() only works on strings, print_r() only works on arrays"
    - "var_dump() shows detailed type and value information, print_r() shows a simpler, more human-readable view"
    - "print_r() writes to the error log, var_dump() writes to the browser"
    - "There is no difference; they are aliases for the same function"
  answer: 1
  explanation: "var_dump() prints detailed type information along with values (useful for spotting subtle type differences), while print_r() produces a simpler, more readable representation of arrays and objects."
```
