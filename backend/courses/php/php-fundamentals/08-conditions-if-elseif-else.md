---
slug: conditions-if-elseif-else
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Conditions: if, elseif, else"
difficulty: beginner
order: 8
estimatedMinutes: 12
tags: [php, conditionals, control-flow]
---

## Learning Objective

By the end of this lesson, you will be able to make your PHP scripts respond
differently to different situations using `if`, `elseif`, and `else`, combined with
the comparison and logical operators from the previous lesson.

## Explanation

Every program you have written so far in this course runs the exact same instructions
every single time, from top to bottom, no matter what the data is. Real programs need
to behave differently depending on circumstances — show a discount only to logged-in
customers, reject a form if a required field is empty, display "Open" or "Closed"
depending on the time of day. This ability to choose which instructions to run is
called **control flow**, and the most basic tool for it is the **conditional
statement**.

A conditional statement in PHP starts with the keyword `if`, followed by a condition in
parentheses, followed by a block of code in curly braces `{ }` that only runs if that
condition is `true`:

```php
if ($age >= 18) {
    echo "You are an adult.";
}
```

If `$age >= 18` evaluates to `true`, the line inside the braces runs. If it evaluates
to `false`, PHP skips straight over the block as if it were not there.

Often you want to check several possibilities in order. That is what `elseif` is for —
it only gets checked if every condition above it was `false`:

```php
if ($age < 13) {
    echo "Child";
} elseif ($age < 20) {
    echo "Teen";
} else {
    echo "Adult";
}
```

PHP checks the conditions **from top to bottom** and runs the first block whose
condition is `true`, then skips the rest entirely — it never checks the remaining
conditions once one has matched. `else`, at the very end, is optional, and its block
runs only if *none* of the conditions above it were `true`. You can have as many
`elseif` blocks as you need, but only one `if` and, at most, one final `else`.

Inside the parentheses of an `if` or `elseif`, you use exactly the comparison operators
(`==`, `===`, `<`, `>=`, etc.) and logical operators (`&&`, `||`, `!`) from the
previous lesson, since a condition needs to end up being a single `true` or `false`
value.

One more idea worth knowing now: PHP does not strictly require the value inside an
`if(...)` to literally be `true` or `false` — it will convert almost any value to a
boolean automatically. Values that convert to `false` are called **falsy**: the number
`0`, the empty string `""`, the string `"0"`, and `null` (which you met earlier as "no
value"). Almost every other value — including any non-empty string that is not `"0"`,
and any non-zero number — converts to `true`, and is called **truthy**. So
`if ($username)` is a common shorthand for "if `$username` is not empty and not
`null`," though for this course we will usually prefer writing explicit comparisons
(like `$username !== ""`) since they are clearer to read.

## Why It Matters

Conditionals are the foundation of nearly every piece of software logic you will ever
write. A real-world example: an online store needs to decide whether to apply free
shipping (order total over a threshold), whether to show a "low stock" warning
(quantity below some number), and whether a coupon code is still valid (checking it
against today's date). Every one of those is a conditional statement, often several
combined together. Without `if`/`elseif`/`else`, every user would see the exact same
output regardless of their situation, which makes almost no real application possible.
Later in this course, when you build actual web features with Symfony, you will use
conditionals constantly — to check if a user is logged in, if a form was submitted
correctly, or if a database lookup found anything.

## Example

Imagine a simple grading script for a school that turns a numeric test score into a
letter grade: 90 and above is an "A", 80 to 89 is a "B", 70 to 79 is a "C", and
anything below 70 is an "F".

## Code

```php
<?php

$score = 84;

if ($score >= 90) {
    $grade = "A";
} elseif ($score >= 80) {
    $grade = "B";
} elseif ($score >= 70) {
    $grade = "C";
} else {
    $grade = "F";
}

echo "Score: " . $score . " -> Grade: " . $grade . "\n";

$isPassing = ($grade !== "F");
$hasHonors = ($score >= 90 && $isPassing);

if ($hasHonors) {
    echo "Congratulations, you made the honor roll!";
} elseif ($isPassing) {
    echo "You passed.";
} else {
    echo "You did not pass this time.";
}
```

## Code Explanation

- `$score = 84;` sets up the number we will classify.
- The first `if`/`elseif`/`else` chain checks the score from the highest cutoff down to
  the lowest. Because PHP checks conditions top to bottom and stops at the first match,
  `$score >= 90` (`84 >= 90`, which is `false`) is skipped, then `$score >= 80`
  (`84 >= 80`, which is `true`) matches, so `$grade` is set to `"B"` and none of the
  remaining `elseif`/`else` blocks even get evaluated.
- `$isPassing = ($grade !== "F");` uses strict "not equal" comparison to check the
  grade is not an "F". Since `$grade` is `"B"`, this is `true`.
- `$hasHonors = ($score >= 90 && $isPassing);` combines two conditions with `&&`. Since
  `$score >= 90` is `false` here, the whole expression is `false` regardless of
  `$isPassing` — `&&` needs *both* sides to be `true`.
- The second `if`/`elseif`/`else` chain then picks the right message: because
  `$hasHonors` is `false` but `$isPassing` is `true`, it prints `"You passed."`.

## Common Mistakes

- **Using `elseif` as two words (`else if`) inconsistently, or missing the braces.**
  PHP actually accepts both `elseif` and `else if`, but mixing styles inconsistently
  within one project makes code harder to read; pick one and stick with it (this course
  uses `elseif`).
- **Putting the most general condition first.** For example, writing
  `if ($score >= 0) { ... } elseif ($score >= 90) { ... }` — the first condition
  (`$score >= 0`) is almost always `true`, so the more specific `elseif` after it will
  never be reached. Order conditions from most specific to least specific.
- **Using `=` instead of `==`/`===` inside a condition.** `if ($score = 90)` assigns
  `90` to `$score` (which is usually a mistake and, since `90` is truthy, would always
  run the block) instead of comparing. Always double-check you used a comparison
  operator, not an assignment operator, inside `if(...)`.
- **Forgetting that only the first matching block runs.** Beginners sometimes expect
  every `true` condition to run its block, but once one `elseif` matches, PHP skips all
  the rest, even if they would also have been `true`.

## Best Practices

- Order `elseif` conditions from most specific to most general, as shown in the
  grading example, so nothing is skipped incorrectly.
- Prefer explicit comparisons (`$username !== ""`) over relying purely on truthy/falsy
  conversion, especially while you are still learning — it makes your intention
  unmistakable to anyone reading the code.
- Keep the code inside each `if`/`elseif`/`else` block short and focused. If a block is
  growing very long, it is often a sign the logic should be split into smaller pieces
  (you will learn how to do this with functions later in this course).
- Always use curly braces `{ }`, even for a single-line block. PHP allows omitting them
  for one-line blocks, but doing so makes it easy to introduce bugs later when someone
  adds a second line without noticing the braces are missing.

## Exercise

Write a PHP script with a variable `$temperature` set to `15` (representing degrees
Celsius). Using `if`/`elseif`/`else`, print `"Freezing"` if the temperature is `0` or
below, `"Cold"` if it is above `0` but below `15`, `"Mild"` if it is `15` up to and
including `25`, and `"Hot"` if it is above `25`.

## Solution

```php
<?php

$temperature = 15;

if ($temperature <= 0) {
    echo "Freezing";
} elseif ($temperature < 15) {
    echo "Cold";
} elseif ($temperature <= 25) {
    echo "Mild";
} else {
    echo "Hot";
}
```

With `$temperature` set to `15`, the first condition `$temperature <= 0` (`15 <= 0`) is
`false`, so PHP checks the next one: `$temperature < 15` (`15 < 15`) is also `false`
(15 is not strictly less than 15). The third condition, `$temperature <= 25`
(`15 <= 25`), is `true`, so `"Mild"` is printed and the final `else` is never reached.

## Mini Quiz

```yaml
- question: "In an if/elseif/else chain, what happens once one condition evaluates to true?"
  options:
    - "PHP runs that block and then also checks the remaining elseif conditions"
    - "PHP runs that block and skips every remaining elseif and else block"
    - "PHP runs every block whose condition is true"
    - "PHP throws an error because only one condition is allowed to be true"
  answer: 1
  explanation: "PHP checks conditions top to bottom and runs the first block that matches, then skips all remaining elseif/else blocks in that chain."
- question: "Which of these values is considered \"falsy\" in PHP?"
  options:
    - "The string \"0\""
    - "The number 1"
    - "The string \"false\""
    - "The number -1"
  answer: 0
  explanation: "The string \"0\" is one of PHP's falsy values, along with 0, an empty string \"\", and null. Interestingly, the string \"false\" is actually truthy, because it is a non-empty string."
- question: "What is wrong with writing `if ($score = 90) { ... }`?"
  options:
    - "Nothing; it correctly checks if $score equals 90"
    - "It uses = (assignment) instead of == or === (comparison), so it sets $score to 90 rather than comparing it"
    - "PHP does not allow numbers inside if conditions"
    - "elseif is required whenever if is used"
  answer: 1
  explanation: "A single = is the assignment operator. Using it inside an if condition assigns the value instead of comparing, which is almost always a mistake."
- question: "Why should elseif conditions generally be ordered from most specific to most general?"
  options:
    - "PHP requires this order or it will not run"
    - "It has no effect on behavior, only readability"
    - "Because a broad, general condition placed first could match before a more specific one gets a chance to run"
    - "Because elseif blocks run in reverse order"
  answer: 2
  explanation: "Since PHP stops at the first true condition, placing a broad condition first can prevent more specific later conditions from ever being reached."
```
