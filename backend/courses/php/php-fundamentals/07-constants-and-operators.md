---
slug: constants-and-operators
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Constants and Operators"
difficulty: beginner
order: 7
estimatedMinutes: 12
tags: [php, constants, operators, comparison]
---

## Learning Objective

By the end of this lesson, you will understand what a constant is and how it differs
from a variable, how to define constants with `define()` and `const`, and how to
compare and combine values using comparison operators (`==`, `===`, `!=`, `!==`, `<`,
`>`, `<=`, `>=`) and logical operators (`&&`, `||`, `!`).

## Explanation

### Constants

You already know a **variable** is a named container for a value that can change while
your program runs (`$score = 10;` and later `$score = 20;` is perfectly normal). A
**constant** is different: it is a named value that, once set, can **never** be
changed for the rest of the program's run. If you try to change it, PHP will refuse and
report an error.

Why would you want a value that cannot change? Because some things in your program
really should never change while it runs — for example, the maximum number of login
attempts allowed, the name of your application, or the mathematical value of pi. Giving
these a fixed name protects you from accidentally overwriting something important
later in a large program, and it makes your intention ("this value is fixed and
meaningful") clear to anyone reading the code.

There are two ways to define a constant in PHP:

1. The `define()` function, which has existed in PHP for a very long time:
   `define('MAX_LOGIN_ATTEMPTS', 5);`
2. The `const` keyword, the more modern way, written like a variable assignment but
   without a `$` sign: `const MAX_LOGIN_ATTEMPTS = 5;`

Both create a constant named `MAX_LOGIN_ATTEMPTS` holding the value `5`. You then use
it anywhere in your code simply by writing its name, with **no** `$` sign:
`echo MAX_LOGIN_ATTEMPTS;`. By convention, constant names are written in
**SCREAMING_SNAKE_CASE** — all uppercase letters, with underscores between words. This
is not a technical requirement, but virtually every PHP developer follows it, so an
uppercase name instantly signals "this is a constant" to anyone reading the code.

### Comparison Operators

To make decisions in your program (which you will start doing formally in the next
lesson), you first need to be able to *compare* values. PHP provides:

- `==` equal to (loose comparison — explained below)
- `===` identical to (strict comparison)
- `!=` not equal to (loose)
- `!==` not identical to (strict)
- `<` less than
- `>` greater than
- `<=` less than or equal to
- `>=` greater than or equal to

Each of these produces a **boolean** result: `true` or `false`.

The most important, and most commonly misunderstood, detail in PHP is the difference
between `==` and `===`. `==` is called **loose comparison**: it will convert the two
values to a common type before comparing them, which can produce surprising results.
For example, `0 == "abc"` used to be `true` in older PHP versions, and even today,
`"5" == 5` is `true` because PHP converts the string `"5"` into the number `5` before
comparing. `===` is called **strict comparison**: it checks that the two values are
both equal *and* of the exact same type, with no conversion. `"5" === 5` is `false`,
because one is a string and the other is an integer, even though they "look" the same.
Because loose comparison can hide bugs, **it is standard practice in modern PHP to
almost always prefer `===` and `!==`** unless you have a specific reason to allow type
conversion.

### Logical Operators

Logical operators combine multiple `true`/`false` values into one result:

- `&&` (**and**): `true` only if *both* sides are `true`.
- `||` (**or**): `true` if *at least one* side is `true`.
- `!` (**not**): flips a value — `!true` is `false`, and `!false` is `true`.

## Why It Matters

Constants matter in real projects because configuration values — API keys, tax rates,
file size limits, the number of items shown per page — are used in many places across
a codebase. Defining them once as a constant means that if the value ever needs to
change, you change it in exactly one place instead of hunting through the entire
project for every place a "magic number" like `5` or `0.20` was typed directly into the
code. Symfony and other frameworks rely heavily on constants and similar fixed
configuration values for exactly this reason.

Comparison and logical operators matter because virtually every useful program needs
to make decisions: "if the user's age is greater than or equal to 18," "if the password
matches AND the account is not locked," "if the input is not empty." Understanding the
`==` vs `===` distinction specifically matters because using the wrong one is one of
the most common sources of real bugs in PHP code — a loose comparison can silently
treat two very different values as "equal," letting bad data slip through checks that
were supposed to catch it.

## Example

Imagine a simple login system that only allows access if a user has entered the
correct numeric PIN code, and it wants to compare the PIN strictly so that, for
instance, the text `"1234abc"` is never mistaken for the number `1234`.

## Code

```php
<?php

const CORRECT_PIN = 1234;
const MAX_ATTEMPTS = 3;

$enteredPin = "1234";
$attemptsUsed = 1;

$isPinCorrect = ($enteredPin === CORRECT_PIN);
$hasAttemptsLeft = ($attemptsUsed < MAX_ATTEMPTS);

echo "Strict match (===): " . ($isPinCorrect ? "true" : "false") . "\n";

$isPinCorrectLoose = ($enteredPin == CORRECT_PIN);
echo "Loose match (==): " . ($isPinCorrectLoose ? "true" : "false") . "\n";

$canTryAgain = (!$isPinCorrect && $hasAttemptsLeft);
echo "Can try again: " . ($canTryAgain ? "true" : "false") . "\n";
```

## Code Explanation

- `const CORRECT_PIN = 1234;` and `const MAX_ATTEMPTS = 3;` define two constants using
  the modern `const` keyword. Notice there is no `$` sign, and the names are in
  SCREAMING_SNAKE_CASE.
- `$enteredPin = "1234";` stores the PIN as a **string** (text), which is realistic
  because PIN entry fields almost always give you text, even if it looks like a
  number.
- `$isPinCorrect = ($enteredPin === CORRECT_PIN);` uses strict comparison `===`. Since
  `$enteredPin` is the string `"1234"` and `CORRECT_PIN` is the integer `1234`, they
  are *not* of the same type, so this is `false`, even though they "look" the same.
- `$isPinCorrectLoose = ($enteredPin == CORRECT_PIN);` uses loose comparison `==`,
  which converts the string `"1234"` to the number `1234` before comparing, making this
  `true`. This line exists purely to demonstrate the difference — in a real login
  system you would want the strict, safer version.
- `$hasAttemptsLeft = ($attemptsUsed < MAX_ATTEMPTS);` checks whether the number of
  attempts used (`1`) is less than the maximum allowed (`3`), which is `true`.
- `$canTryAgain = (!$isPinCorrect && $hasAttemptsLeft);` combines two conditions with
  `&&`: the PIN must be incorrect (`!$isPinCorrect`, meaning "not correct") **and**
  there must be attempts left. Both are `true` here, so `$canTryAgain` is `true`.

## Common Mistakes

- **Using `==` by default out of habit.** This can let unexpected values slip through,
  such as comparing user input against a stored value and getting a false "match"
  because of a type conversion you did not intend.
- **Putting a `$` in front of a constant name.** Constants never use `$` — writing
  `$CORRECT_PIN` refers to a completely different (and in this case undefined)
  variable, not the constant.
- **Trying to reassign a constant later**, such as `CORRECT_PIN = 9999;` after it was
  already defined. PHP will raise an error, because the entire point of a constant is
  that it cannot change.
- **Confusing `!=` with `!==`.** Just like `==` vs `===`, `!=` allows type conversion
  and `!==` does not — the same risk applies in reverse for "not equal" checks.

## Best Practices

- Default to `===` and `!==` for comparisons unless you have a clear, deliberate reason
  to allow loose type conversion.
- Use constants (`const` in modern code) for any value that is fixed and meaningful
  across your program, and name them in SCREAMING_SNAKE_CASE so they are instantly
  recognizable.
- Prefer `const` over `define()` in new code you write — `const` is the more modern
  syntax and works more naturally alongside classes, which you will meet later in this
  course. `define()` still works everywhere and you will see it in older codebases.
- When combining several logical conditions, use parentheses generously, even when not
  strictly required, so the intended grouping is obvious to a human reader.

## Exercise

Define a constant `PASSING_SCORE` with the value `50`. Then create a variable
`$studentScore` set to `50` (as an integer). Write an expression that checks, using
strict comparison and logical operators, whether the student passed (`$studentScore`
is greater than or equal to `PASSING_SCORE`) **and** the score is not negative. Print
`"Passed: true"` or `"Passed: false"` accordingly.

## Solution

```php
<?php

const PASSING_SCORE = 50;

$studentScore = 50;

$passed = ($studentScore >= PASSING_SCORE) && ($studentScore >= 0);

echo "Passed: " . ($passed ? "true" : "false");
```

`$studentScore >= PASSING_SCORE` checks `50 >= 50`, which is `true`. `$studentScore >=
0` checks `50 >= 0`, which is also `true`. Since both sides of `&&` are `true`, `$passed`
is `true`, so the script prints `"Passed: true"`.

## Mini Quiz

```yaml
- question: "What is the key difference between a constant and a variable?"
  options:
    - "A constant can only hold numbers, while a variable can hold any type"
    - "A constant's value cannot be changed once it is defined; a variable's can"
    - "A constant must start with a $ sign, while a variable does not"
    - "There is no real difference; the terms are interchangeable in PHP"
  answer: 1
  explanation: "A constant is a named value that stays fixed for the rest of the program's run, unlike a variable, whose value can be reassigned."
- question: "What does `\"5\" === 5` evaluate to in PHP, and why?"
  options:
    - "true, because === ignores type differences"
    - "false, because === requires both the value and the type to match, and one is a string while the other is an integer"
    - "true, because PHP automatically converts strings to numbers for ===" 
    - "An error, because you cannot compare a string to a number"
  answer: 1
  explanation: "=== is strict comparison: it requires the same type as well as the same value. \"5\" is a string and 5 is an integer, so they are not identical."
- question: "Which naming convention is standard for PHP constants?"
  options:
    - "camelCase, e.g. maxAttempts"
    - "kebab-case, e.g. max-attempts"
    - "SCREAMING_SNAKE_CASE, e.g. MAX_ATTEMPTS"
    - "PascalCase, e.g. MaxAttempts"
  answer: 2
  explanation: "By convention, PHP constants are written in all uppercase letters with underscores between words, such as MAX_ATTEMPTS."
- question: "What does the && (logical and) operator return?"
  options:
    - "true if at least one side is true"
    - "true only if both sides are true"
    - "The sum of both sides"
    - "false if both sides are true"
  answer: 1
  explanation: "&& (and) evaluates to true only when both of its sides are true; if either side is false, the whole expression is false."
```
