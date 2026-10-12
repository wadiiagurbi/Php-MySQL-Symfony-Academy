---
slug: validation-and-sanitization
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Validation and Sanitization"
difficulty: beginner
order: 4
estimatedMinutes: 12
tags: [php, validation, sanitization, security]
---

## Learning Objective

By the end of this lesson, you will understand the difference between validating and sanitizing user input, and how to use functions like `trim()`, `empty()`, `filter_var()`, and `htmlspecialchars()` to handle form data safely.

## Explanation

In the previous two lessons, you learned how to read data a visitor submits through `$_GET` and `$_POST`. There is an important rule you must adopt from this point onward: **never trust user input**. A visitor can type absolutely anything into a form field, send a request with missing fields, or even craft an HTTP request by hand, completely bypassing your HTML form. Your PHP code is the only thing standing between whatever arrives and whatever your application does with it.

This is where two related, but different, ideas come in:

**Validation** means checking whether submitted data is correct and matches what you expect, before you use it. Does this field actually look like an email address? Is this supposed-to-be-a-number field actually numeric? Is this required field even present, or is it empty? If validation fails, you reject the input and tell the visitor what went wrong, rather than proceeding with bad data.

**Sanitization** means cleaning up or transforming data to make it safe to use or display, regardless of whether it passed validation. A common case is removing extra whitespace from the beginning and end of a text field, or converting special characters so that text can be safely inserted into an HTML page without being interpreted as HTML or code.

Some useful PHP tools for these tasks:

- `trim($string)` removes whitespace from the start and end of a string — useful because visitors often leave stray spaces around form input, and an email like `" bob@example.com "` should be treated as `"bob@example.com"`.
- `empty($value)` checks whether a value is considered "empty" (an empty string, `0`, `null`, an unset variable, and a few other cases). It is often used to check whether a required field was actually filled in.
- `filter_var($value, $filter)` validates or sanitizes a value against a specific rule. For example, `filter_var($email, FILTER_VALIDATE_EMAIL)` checks whether `$email` is correctly formatted as an email address, returning the email back if it's valid, or `false` if it is not.
- `htmlspecialchars($string)` converts characters that have special meaning in HTML — such as `<`, `>`, `&`, and quotes — into safe equivalents (called HTML entities) so that the text is displayed as plain text rather than being interpreted as HTML markup.

A field can pass validation, but should still often be sanitized before being displayed or stored, and a field should always be validated before you rely on it being what you expect.

## Why It Matters

Any script that accepts data from a visitor and later displays that data back to other visitors is a potential target for an attack called **Cross-Site Scripting (XSS)**. In an XSS attack, someone submits text containing HTML or JavaScript — for example, a comment containing `<script>` tags — hoping the site will display it back without escaping it. If it does, that script can run in the browser of every other visitor who views the page, potentially stealing their session information or performing actions on their behalf.

Validation protects your application's logic and data integrity: if you expect an age to be a number between 0 and 120, and someone submits the text `"banana"` or `"-5"`, validation catches that before it corrupts a calculation or a database record. Sanitization, especially escaping output with `htmlspecialchars()`, protects your visitors from attacks hidden inside submitted data. Real-world projects — from small contact forms to large e-commerce platforms — apply both consistently, because the cost of skipping them is data corruption at best and a security breach at worst.

## Example

Imagine a signup form that collects a name and an email address. Before doing anything with this data — saving it, sending a welcome email, displaying a "Welcome" message — the script should confirm the name isn't empty, confirm the email actually looks like an email, and escape anything before it gets echoed back into HTML.

## Code

```php
<?php
// signup-check.php
// Simulates receiving $_POST data from a signup form.

$_POST['name'] = '  Alice  ';
$_POST['email'] = 'alice@example.com';

$errors = [];

$name = trim($_POST['name'] ?? '');
if (empty($name)) {
    $errors[] = 'Name is required.';
}

$rawEmail = trim($_POST['email'] ?? '');
$email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($errors)) {
    $safeName = htmlspecialchars($name);
    $safeEmail = htmlspecialchars($email);
    echo "Welcome, {$safeName}! A confirmation will be sent to {$safeEmail}.";
} else {
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . "\n";
    }
}
```

## Code Explanation

- `trim($_POST['name'] ?? '')` strips the leading and trailing spaces from `"  Alice  "`, turning it into `"Alice"`, and falls back to an empty string if `name` was never sent at all.
- `empty($name)` checks whether, after trimming, the name is still an empty string — this is how required-field validation is expressed.
- `filter_var($rawEmail, FILTER_VALIDATE_EMAIL)` validates the email's format. If the string is a properly structured email address, it returns that same string; if not, it returns the boolean `false`, which is why the code checks `$email === false` (using strict comparison, since a technically valid but unusual email could otherwise be confused with a falsy value).
- The `$errors` array collects every problem found, rather than stopping at the first one, so the visitor can see everything that needs fixing at once.
- `htmlspecialchars()` is applied right before the values are inserted into the output string, converting any stray HTML-significant characters into safe text — this is sanitization for the purpose of safe display, applied even to data that already passed validation.

## Common Mistakes

- **Confusing validation with sanitization and only doing one of them.** Validating an email's format does not make it safe to display without escaping, and escaping a value does not make it a correctly formatted email. They solve different problems.
- **Comparing `filter_var()`'s result with `==` instead of `===`.** Because `filter_var()` can return `false` on failure, and because loosely comparing `false == 0` or `false == ''` is `true` in PHP, using `==` instead of the strict `===` can hide validation failures.
- **Escaping data before storing it instead of before displaying it.** Applying `htmlspecialchars()` when saving data (for example, into a database) and again when displaying it results in double-escaped text like `&amp;lt;`. Store the original, cleaned value; escape only at the point where it is output as HTML.
- **Trusting client-side (JavaScript) validation alone.** A visitor can disable JavaScript, or send a request directly without ever loading your HTML form, so any validation that only happens in the browser can be bypassed entirely. Server-side PHP validation is what actually protects your application.

## Best Practices

- Always validate on the server, even if you also validate in the browser with JavaScript or HTML attributes like `required` — client-side checks are a convenience for users, not a security measure.
- Use `trim()` on text input before checking it with `empty()`, since whitespace-only input should usually count as empty.
- Use the right `filter_var()` filter for the kind of data you expect (`FILTER_VALIDATE_EMAIL`, `FILTER_VALIDATE_INT`, `FILTER_VALIDATE_URL`, and others), rather than writing your own pattern-matching logic from scratch.
- Escape output with `htmlspecialchars()` at the moment you display data, not earlier, to avoid double-escaping and to keep your stored data clean.

## Exercise

Write a PHP script called `age-check.php` that simulates receiving an `age` field through `$_POST`. Validate that the value is present, that it is numeric, and that it falls between 1 and 120 inclusive. If all checks pass, display `"Valid age: "` followed by the (escaped) age. Otherwise, display `"Invalid age submitted."`.

## Solution

```php
<?php
// age-check.php

$_POST['age'] = '34';

$rawAge = trim($_POST['age'] ?? '');
$isValid = false;

if ($rawAge !== '' && filter_var($rawAge, FILTER_VALIDATE_INT) !== false) {
    $age = (int) $rawAge;
    if ($age >= 1 && $age <= 120) {
        $isValid = true;
    }
}

if ($isValid) {
    echo "Valid age: " . htmlspecialchars((string) $age);
} else {
    echo "Invalid age submitted.";
}
```

This works because `filter_var($rawAge, FILTER_VALIDATE_INT)` confirms the submitted text is a genuine whole number rather than something like `"thirty"` or `"12.5"`, returning `false` if it isn't. Only once the value is confirmed numeric is it cast to an actual integer with `(int)` and checked against the sensible range 1 to 120. Escaping the final output with `htmlspecialchars()` is good habit even for a value already confirmed to be a number, since it costs nothing and keeps the output consistently safe.

## Mini Quiz

```yaml
- question: "What is the main difference between validation and sanitization?"
  options:
    - "They are two names for the exact same process"
    - "Validation checks that data meets expected rules; sanitization cleans or escapes data to make it safe to use or display"
    - "Sanitization only applies to numbers, validation only applies to text"
    - "Validation happens in the browser, sanitization happens on the server"
  answer: 1
  explanation: "Validation confirms data is correct and expected (e.g. a real email format); sanitization cleans or escapes data, such as removing whitespace or escaping HTML characters, regardless of whether it passed validation."
- question: "What does filter_var($value, FILTER_VALIDATE_EMAIL) return if $value is not a valid email address?"
  options:
    - "An empty string"
    - "null"
    - "false"
    - "It throws an error"
  answer: 2
  explanation: "filter_var() returns false when the value fails the given filter's validation, such as FILTER_VALIDATE_EMAIL for a badly formatted email."
- question: "Why should you use === false instead of == false when checking a filter_var() result?"
  options:
    - "=== is faster to execute"
    - "== would incorrectly treat some valid, falsy-looking values the same as an actual failure"
    - "== does not work with filter_var() at all"
    - "There is no difference, either works the same"
  answer: 1
  explanation: "Because PHP's loose == comparison treats false, 0, and '' as equal, using == can mistake a legitimate value for a validation failure; === checks both value and type, avoiding that mix-up."
- question: "Why is htmlspecialchars() important when displaying user-submitted text back on a page?"
  options:
    - "It makes the text load faster"
    - "It automatically translates the text into other languages"
    - "It prevents special HTML characters in the text from being interpreted as real HTML or scripts, protecting against XSS attacks"
    - "It permanently deletes any invalid characters from the database"
  answer: 2
  explanation: "htmlspecialchars() converts characters like < and > into safe HTML entities, so submitted text is displayed as plain text instead of being executed as HTML or JavaScript, which is what prevents Cross-Site Scripting (XSS)."
```
