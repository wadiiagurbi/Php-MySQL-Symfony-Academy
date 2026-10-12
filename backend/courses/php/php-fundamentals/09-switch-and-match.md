---
slug: switch-and-match
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "switch and match"
difficulty: beginner
order: 9
estimatedMinutes: 12
tags: [php, conditionals, switch, match]
---

## Learning Objective

By the end of this lesson, you will be able to use PHP's `switch` statement to compare
one value against several possibilities, understand the fall-through pitfall that
trips up many beginners, and use the modern `match` expression as a safer alternative.

## Explanation

In the previous lesson, you learned `if`/`elseif`/`else` for making decisions. That
works perfectly well, but when you need to compare **one single value** against many
possible exact matches — for example, turning a day number (1 through 7) into a day
name — writing a long chain of `elseif` statements gets repetitive and harder to read:

```php
if ($day === 1) {
    $name = "Monday";
} elseif ($day === 2) {
    $name = "Tuesday";
} elseif ($day === 3) {
    $name = "Wednesday";
}
// ...and so on
```

### switch

PHP's `switch` statement was designed for exactly this situation. It compares one
value against a list of `case` values:

```php
switch ($day) {
    case 1:
        echo "Monday";
        break;
    case 2:
        echo "Tuesday";
        break;
    default:
        echo "Unknown day";
        break;
}
```

`switch ($day)` says "compare `$day` against the following cases." Each `case`
introduces one possible value to compare against, using loose (`==`-style) comparison.
`default` is optional and acts like `else` — it runs if none of the `case` values
matched.

Here is the single most important thing to understand about `switch`, and the source
of a very common beginner bug: **without a `break` statement, PHP does not stop after
running a matching case — it keeps running the code in every case below it too**, even
if their values do not match. This is called **fall-through**. For example, if you
forgot the `break` after `case 1:`, and `$day` was `1`, PHP would print `"Monday"` and
then *also* fall through and print `"Tuesday"`, and keep going through every case after
that, until it hits a `break` or reaches the end. This is almost never what you want,
which is why every `case` should normally end with `break;`.

### match

PHP 8 introduced `match`, a more modern alternative designed to fix exactly this
problem. It looks similar to `switch` but behaves more safely:

```php
$name = match ($day) {
    1 => "Monday",
    2 => "Tuesday",
    3 => "Wednesday",
    default => "Unknown day",
};
```

Three key differences from `switch`:

1. **No fall-through, ever.** Each arm (`1 => "Monday"`) runs only its own code — there
   is no `break` keyword because it is simply not needed.
2. **`match` produces a value.** Notice `$name = match (...)`. Unlike `switch`, which
   only runs statements, `match` evaluates to a result you can assign directly to a
   variable, return, or use in an expression.
3. **`match` uses strict comparison (`===`).** This avoids the loose-comparison
   surprises you learned about with `==` earlier in this course. `switch` compares
   loosely, `match` compares strictly.

You must still include a `default` arm in `match` (or cover every realistic case)
because if none of the arms match and there is no `default`, PHP will stop the script
with an error — `match` refuses to silently do nothing.

## Why It Matters

Real applications constantly need to map one specific value to one specific outcome:
turning an HTTP status code into a human-readable message ("200" → "OK", "404" → "Not
Found"), turning a subscription plan name into a monthly price, or turning a user role
into a set of permissions. `switch` has existed in PHP (and most programming languages)
for decades and you will still see it in plenty of existing codebases, so recognizing
its fall-through behavior is essential for reading other people's code safely.
`match`, being newer and safer, is now the preferred choice in modern PHP and Symfony
projects specifically because it eliminates the fall-through bug entirely and reads
more clearly when you just want to compute a value from a fixed set of options.

## Example

Imagine mapping a numeric day of the week (1 to 7) to its name, once using `switch`
and once using `match`, to see the difference directly.

## Code

```php
<?php

$day = 3;

switch ($day) {
    case 1:
        $switchName = "Monday";
        break;
    case 2:
        $switchName = "Tuesday";
        break;
    case 3:
        $switchName = "Wednesday";
        break;
    case 4:
        $switchName = "Thursday";
        break;
    case 5:
        $switchName = "Friday";
        break;
    case 6:
        $switchName = "Saturday";
        break;
    case 7:
        $switchName = "Sunday";
        break;
    default:
        $switchName = "Invalid day";
        break;
}

echo "switch result: " . $switchName . "\n";

$matchName = match ($day) {
    1 => "Monday",
    2 => "Tuesday",
    3 => "Wednesday",
    4 => "Thursday",
    5 => "Friday",
    6 => "Saturday",
    7 => "Sunday",
    default => "Invalid day",
};

echo "match result: " . $matchName . "\n";
```

## Code Explanation

- `$day = 3;` sets the value both examples will compare against.
- In the `switch` block, PHP compares `$day` against each `case` in order. It matches
  `case 3:`, sets `$switchName = "Wednesday";`, then hits `break;`, which stops the
  `switch` immediately so none of the later cases run.
- Each `case` needing its own `break;` is exactly the fall-through safeguard discussed
  above — if any `break;` were missing, execution would continue into the next case's
  code.
- In the `match` expression, `match ($day)` compares `$day` strictly against `1`, `2`,
  `3`, and so on. It finds `3 => "Wednesday"`, and the entire `match` expression
  evaluates to the string `"Wednesday"`, which is assigned directly to `$matchName` in
  one step — no separate variable assignment inside each arm, and no `break` needed.
- `default => "Invalid day",` is the fallback used only if `$day` does not equal any of
  the listed numbers.
- Both approaches produce the same result here (`"Wednesday"`), demonstrating that
  `match` can replace `switch` for this kind of one-value-to-one-result mapping, more
  concisely and more safely.

## Common Mistakes

- **Forgetting `break;` in a `switch` case.** This causes fall-through: execution
  continues into the next case's code even though its value did not match. This is the
  single most common `switch`-related bug.
- **Expecting `switch` to compare strictly.** `switch` uses loose comparison
  internally, so `case "1":` would match an `$day` of the integer `1`, similar to how
  `==` behaves. If you need strict, type-safe comparison, prefer `match`.
- **Forgetting the `default` arm in `match`.** Unlike `switch`'s optional `default`,
  skipping it in `match` means that any unmatched value causes PHP to raise an error
  and stop the script, rather than quietly doing nothing.
- **Using a trailing comma inconsistently in `match`.** PHP allows (and many style
  guides encourage) a trailing comma after the last arm, as shown above — omitting it
  is not an error, but forgetting a comma *between* arms is.

## Best Practices

- Prefer `match` over `switch` in new code whenever you simply need to turn one input
  value into one output value, since it is shorter, returns a value directly, and
  avoids the fall-through pitfall entirely.
- If you do use `switch`, get into the habit of typing `break;` immediately after
  writing each `case`'s code, before you forget.
- Always include a `default` case in both `switch` and `match` so that unexpected
  input is handled deliberately rather than causing confusing behavior or a crash.
- Reserve `switch` for situations where you need to run several statements (not just
  produce one value) for a given case, or when working in an older codebase that
  already uses it consistently.

## Exercise

Using `match`, write a script with a variable `$httpStatus` set to `404`. Map `200` to
`"OK"`, `301` to `"Moved Permanently"`, `404` to `"Not Found"`, `500` to `"Server
Error"`, and anything else to `"Unknown Status"`. Store the result in a variable
`$statusText` and print it.

## Solution

```php
<?php

$httpStatus = 404;

$statusText = match ($httpStatus) {
    200 => "OK",
    301 => "Moved Permanently",
    404 => "Not Found",
    500 => "Server Error",
    default => "Unknown Status",
};

echo $statusText;
```

`match ($httpStatus)` strictly compares `404` against each listed value in order. It
finds `404 => "Not Found",`, so the whole `match` expression evaluates to the string
`"Not Found"`, which is stored in `$statusText` and then printed, producing the output
`Not Found`.

## Mini Quiz

```yaml
- question: "What happens if you forget the `break;` statement in a switch case whose value matches?"
  options:
    - "PHP raises an error immediately"
    - "PHP stops the switch statement automatically anyway"
    - "PHP keeps running the code in the following cases too, even though their values did not match (fall-through)"
    - "Nothing runs at all"
  answer: 2
  explanation: "Without break, switch execution falls through into the next case's code, running it regardless of whether its value actually matched."
- question: "Which of these is true about match compared to switch?"
  options:
    - "match uses loose comparison, just like switch"
    - "match requires a break statement after every arm"
    - "match compares strictly (like ===) and directly produces a value you can assign to a variable"
    - "match cannot have a default case"
  answer: 2
  explanation: "match compares its subject strictly against each arm and evaluates to a result, which is why you can write $result = match (...) {...};."
- question: "What happens in `match` if none of the arms match and there is no `default` arm?"
  options:
    - "PHP silently does nothing and continues"
    - "PHP raises an error and stops the script"
    - "PHP falls through to the next arm automatically"
    - "PHP returns null without any error"
  answer: 1
  explanation: "Unlike switch, match requires a matching arm (or a default). If nothing matches and there is no default, PHP raises an error."
```
