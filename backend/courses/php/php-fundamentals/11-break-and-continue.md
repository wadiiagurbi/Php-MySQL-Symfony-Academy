---
slug: break-and-continue
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "break and continue"
difficulty: beginner
order: 11
estimatedMinutes: 10
tags: [php, loops, control-flow]
---

## Learning Objective

By the end of this lesson, you will understand what the `break` and `continue`
keywords do inside loops, why they are useful, and how to use them correctly inside
`for` and `while` loops.

## Explanation

In the previous lesson, you learned that a loop keeps running its body until its
condition becomes `false`. Normally, that condition is the only thing that decides
when a loop stops or when an iteration ends. But sometimes, while you are inside
the loop's body, you want to change that behavior early, based on something you
just discovered. PHP gives you two keywords for exactly this: `break` and
`continue`.

**`break`** immediately stops the loop entirely, no matter what the loop's
condition says. As soon as PHP runs a `break;` statement, it exits the loop right
away and continues with whatever code comes after the loop.

**`continue`** does something less drastic: it skips the *rest of the current
iteration* only, and jumps straight to checking the loop's condition again (and, in
a `for` loop, running the increment step) to decide whether to start the next
iteration. The loop itself keeps running — only that one pass through the body is
cut short.

A helpful way to remember the difference: `break` means "stop the whole loop right
now," while `continue` means "skip the rest of this one lap, but keep looping."

Both keywords are almost always used inside an `if` statement, because you only
want to skip or stop under a specific condition, not on every single iteration.

```
for ($i = 1; $i <= 10; $i++) {
    if (/* some condition */) {
        break; // or continue;
    }
    // normal code for this iteration
}
```

## Why It Matters

Real programs rarely need to blindly run every single iteration of a loop no
matter what happens inside it. Two situations come up constantly:

- **Stopping as soon as you find what you were looking for.** Imagine searching
  through customer records for a specific email address. Once you find a match,
  there is no reason to keep checking the remaining records — continuing would
  waste time and computing resources, especially with a very large list. `break`
  lets you stop immediately once the job is done.
- **Skipping values that do not apply, without stopping the whole process.**
  Imagine processing a list of order quantities, and you want to skip any quantity
  of zero (a canceled item) without treating it as an error that halts everything
  else. `continue` lets you skip just that one item and move on to the next.

Both patterns appear everywhere in real backend code: searching, filtering,
validating user input, and processing batches of data. Learning to use `break` and
`continue` well makes your loops more efficient and lets you express "except when
..." logic clearly, instead of writing large, deeply nested `if` statements to
achieve the same result.

## Example

Suppose you want to find the first number between 1 and 100 that is evenly
divisible by 7 (meaning dividing it by 7 leaves no remainder). Once you find it,
there is no point in checking the remaining numbers, so you can `break` out of the
loop immediately. Separately, suppose you want to print every number from 1 to 15
*except* the multiples of 3 — for those, you want to `continue` past them without
printing anything, while still checking every other number.

## Code

```php
<?php

// Find the first number from 1 to 100 divisible by 7
for ($number = 1; $number <= 100; $number++) {
    if ($number % 7 === 0) {
        echo "Found it: " . $number . "\n";
        break; // no need to keep checking once we found one
    }
}

echo "---\n";

// Print numbers 1 to 15, skipping multiples of 3
for ($number = 1; $number <= 15; $number++) {
    if ($number % 3 === 0) {
        continue; // skip printing this number, move to the next one
    }

    echo $number . "\n";
}
```

## Code Explanation

- `$number % 7 === 0` uses the **modulo operator** `%`, which gives the remainder
  of a division. `$number % 7` is `0` exactly when `$number` is evenly divisible by
  7 (for example, 14 % 7 is 0, but 15 % 7 is 1). Combined with `===`, this checks
  "is the remainder exactly equal to 0?"
- As soon as `$number` reaches 7 (the first multiple of 7 from 1 upward), the `if`
  condition is `true`, PHP prints `"Found it: 7"`, and then `break;` runs. This
  immediately exits the `for` loop — PHP never bothers checking 8, 9, 10, and so on
  up to 100, even though the loop's condition (`$number <= 100`) would otherwise
  have allowed it to keep going.
- In the second loop, `$number % 3 === 0` is `true` whenever `$number` is a
  multiple of 3 (3, 6, 9, 12, 15).
- When that condition is `true`, `continue;` runs, which skips the `echo` line
  below it for that iteration only. PHP then moves on to the `for` loop's increment
  step (`$number++`) and checks the condition again, continuing the loop normally.
- The result is that the second loop prints 1, 2, 4, 5, 7, 8, 10, 11, 13, 14 — every
  number from 1 to 15 except the multiples of 3.

## Common Mistakes

- **Confusing `break` and `continue`.** Using `break` when you meant `continue`
  will stop the entire loop after the first matching value, instead of just
  skipping that one value and checking the rest. Always ask: "do I want to stop
  everything, or just skip this one case?"
- **Placing `break` or `continue` outside of an `if` (or other) condition by
  accident**, so it runs on every single iteration. For example, putting `break;`
  as the very first line inside a loop's body with no condition around it means the
  loop will only ever run once, which is rarely what you want.
- **Expecting `continue` to skip more than one iteration.** `continue` only skips
  the remainder of the *current* pass through the loop; it does not skip the next
  iteration or any future ones.
- **Using `break` inside deeply nested loops and expecting it to stop every level
  at once.** By default, `break` only stops the innermost loop it is directly
  inside. Handling nested loops correctly needs extra care that goes beyond this
  lesson.

## Best Practices

- Use `break` to avoid unnecessary work once you already have the answer you were
  looking for — it makes searches and lookups more efficient, especially over large
  amounts of data.
- Use `continue` to keep loop bodies flatter and more readable. Instead of wrapping
  most of the loop body in a large `if (condition that means "process this item")`
  block, you can write `if (condition that means "skip this item") { continue; }`
  near the top, followed by the normal processing code with no extra nesting.
- Keep the condition next to a `break` or `continue` short and clear, so anyone
  reading the loop can immediately understand *why* an iteration is being skipped
  or the loop is being stopped.
- After writing a loop with `break` or `continue`, mentally trace through a couple
  of example values to confirm the loop behaves the way you expect, especially
  around the very first and very last iterations.

## Exercise

Write a `for` loop that goes through the numbers from 1 to 20. For each number,
skip it (using `continue`) if it is not divisible by 4. Otherwise, print the
number. But if the number 16 is printed, stop the loop entirely right after
printing it, using `break`.

## Solution

```php
<?php

for ($number = 1; $number <= 20; $number++) {
    if ($number % 4 !== 0) {
        continue; // skip numbers that are not divisible by 4
    }

    echo $number . "\n";

    if ($number === 16) {
        break; // stop the loop right after printing 16
    }
}
```

This prints 4, 8, 12, and 16, then stops before checking 17 through 20. The
`continue` statement skips every number whose remainder after dividing by 4 (`%
4`) is not zero, so only multiples of 4 ever reach the `echo` line. After printing
16 specifically, the second `if` condition becomes true and `break;` exits the loop
immediately, which is why 20 (the next multiple of 4) is never printed.

## Mini Quiz

```yaml
- question: "What does the break statement do when it runs inside a loop?"
  options:
    - "It skips the rest of the current iteration only"
    - "It immediately stops the entire loop"
    - "It restarts the loop from the beginning"
    - "It pauses the program until the user presses a key"
  answer: 1
  explanation: "break exits the loop entirely, right away, regardless of what the loop's condition would otherwise evaluate to."
- question: "What does the continue statement do when it runs inside a loop?"
  options:
    - "It stops the entire loop immediately"
    - "It skips the remaining code in the current iteration and moves on to the next one"
    - "It deletes the current value of the loop counter"
    - "It has no effect inside a for loop"
  answer: 1
  explanation: "continue skips only the rest of the current pass through the loop body, then lets the loop continue normally with its next iteration."
- question: "In the expression $number % 7 === 0, what is being checked?"
  options:
    - "Whether $number is exactly equal to 7"
    - "Whether $number divided by 7 leaves no remainder"
    - "Whether $number is greater than 7"
    - "Whether $number is a text string"
  answer: 1
  explanation: "The % operator returns the remainder of a division, so $number % 7 === 0 checks whether $number is evenly divisible by 7."
```
