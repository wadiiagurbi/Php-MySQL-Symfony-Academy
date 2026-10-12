---
slug: loops-for-while-foreach
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Loops: for, while, and foreach"
difficulty: beginner
order: 10
estimatedMinutes: 12
tags: [php, loops, control-flow]
---

## Learning Objective

By the end of this lesson, you will understand why loops exist, how to write a
`while` loop and a `for` loop to repeat an action a certain number of times, and
what `foreach` is used for (you will learn to use `foreach` properly in an upcoming
lesson once you know what arrays are).

## Explanation

So far, every piece of code you have written runs exactly once, from top to bottom.
But imagine you wanted to print the numbers 1 through 1000. Writing 1000 `echo`
lines by hand would be slow, error-prone, and painful to change later. This is
exactly the kind of problem **loops** solve.

A **loop** is a piece of code that repeats a block of instructions multiple times,
either until a condition becomes false or for a fixed number of repetitions. Each
single run-through of the loop's body is called an **iteration**.

PHP gives you a few different kinds of loops. In this lesson, you will learn two of
them in depth:

**The `while` loop** repeats its body for as long as a given condition stays
`true`. It checks the condition *before* each iteration. Its basic shape is:

```
while (condition) {
    // code to repeat
}
```

Every `while` loop needs something inside it that eventually makes the condition
`false` — otherwise it would repeat forever, which is called an **infinite loop**
and will freeze or crash your program.

**The `for` loop** is a more compact way to write a loop when you already know
roughly how many times you want to repeat something, especially when you are
counting up or down. Its shape has three parts, separated by semicolons:

```
for (initialization; condition; increment) {
    // code to repeat
}
```

- **Initialization** runs once, before the loop starts. It usually creates a
  **counter variable**, for example `$i = 1`.
- **Condition** is checked before every iteration, just like in `while`. As long as
  it evaluates to `true`, the loop body runs.
- **Increment** runs at the end of every iteration, after the body executes. It
  usually changes the counter, for example `$i++` (which means "add 1 to `$i`").

A `for` loop is essentially a `while` loop with the counter setup, check, and update
all written together on one line, which makes counting loops easier to read and
harder to get wrong.

There is also a third kind of loop called `foreach`, which is designed specifically
for going through every value in a list, one at a time, without needing a manual
counter at all. You have not yet learned about **arrays** (PHP's way of storing a
list of values in a single variable) — that comes in the next couple of lessons.
Once you know what arrays are, `foreach` will make a lot more sense, so for now just
remember its name and that its job is "go through every item in a list."

## Why It Matters

Loops are one of the most fundamental tools in programming because almost every
real program needs to repeat work. A shopping website needs to calculate a total
across every item somebody bought. A game needs to check dozens of enemy positions
every fraction of a second. A report generator needs to process thousands of rows
of data pulled from a database. None of these programs contain thousands of
hand-written, nearly identical lines of code — they contain a small loop that runs
the same logic many times.

In a Symfony backend (the framework this course is building toward), loops appear
constantly: looping over rows fetched from a database to build a webpage, looping
over form fields to validate them, or looping over items in an order to calculate a
shipping cost. Learning to control exactly how many times something repeats, and
under what condition, is a skill you will use in essentially every program you
write from here on.

## Example

Imagine you want to count down from 5 to 1, like a rocket launch countdown, and
then print "Liftoff!". You also want to calculate the sum of the numbers 1 through
10 without typing `1 + 2 + 3 + ...` by hand. Both tasks involve repeating a small
action a known number of times — a perfect use case for `for` and `while` loops.

## Code

```php
<?php

// A countdown from 5 to 1 using a while loop
$count = 5;

while ($count >= 1) {
    echo $count . "...\n";
    $count--; // subtract 1 from $count each time
}

echo "Liftoff!\n";

// Summing the numbers 1 to 10 using a for loop
$sum = 0;

for ($i = 1; $i <= 10; $i++) {
    $sum = $sum + $i;
}

echo "The sum of 1 to 10 is " . $sum . "\n";
```

## Code Explanation

- `$count = 5;` creates a counter variable starting at 5. This is the value the
  `while` loop's condition will check.
- `while ($count >= 1)` means "keep looping as long as `$count` is 5, 4, 3, 2, or
  1." Once `$count` becomes 0, the condition is `false` and the loop stops.
- `echo $count . "...\n";` prints the current count followed by `"..."` and a
  newline character (`\n` moves the following output to a new line).
- `$count--;` is shorthand for `$count = $count - 1;`. It reduces `$count` by one
  on every iteration, which is what eventually makes the `while` condition false.
  Without this line, the loop would run forever.
- After the `while` loop finishes, `echo "Liftoff!\n";` runs once, exactly as
  written — it is outside the loop's curly braces `{ }`, so it is not repeated.
- `$sum = 0;` starts a running total at zero before adding anything to it.
- `for ($i = 1; $i <= 10; $i++)` sets up the counting loop: `$i = 1` runs once at
  the start, `$i <= 10` is checked before every iteration, and `$i++` (shorthand for
  "add 1 to `$i`") runs after every iteration's body finishes.
- Inside the loop, `$sum = $sum + $i;` adds the current value of `$i` onto the
  running total. Over ten iterations, this adds `1 + 2 + 3 + ... + 10`.
- After the loop ends (once `$i` becomes 11 and the condition fails), the final
  `echo` prints the completed sum, which is 55.

## Common Mistakes

- **Forgetting to update the counter**, which creates an infinite loop. For
  example, writing `while ($count >= 1) { echo $count; }` without `$count--;`
  inside it would print forever and freeze the program, because the condition
  never becomes false.
- **Using the wrong comparison direction**, such as writing `for ($i = 10; $i <=
  1; $i++)` when counting down from 10 to 1. Since `$i` starts at 10 and the
  condition `$i <= 1` is immediately false, the loop body never runs at all. To
  count down, use `$i--` and a condition like `$i >= 1`.
- **Off-by-one errors**, for example using `$i < 10` instead of `$i <= 10` when you
  actually want the loop to include the number 10. Always double-check whether your
  boundary condition should include or exclude the final value.
- **Putting a semicolon after the condition by accident**, like `for ($i = 1; $i <=
  10; $i++);`. That stray semicolon creates an empty loop body, and the code you
  intended to repeat only runs once, after the loop already finished.

## Best Practices

- Always make sure something inside the loop body will eventually make the
  condition false. Before running a loop, ask yourself: "what changes on each
  iteration, and will it eventually stop the loop?"
- Prefer `for` when you know in advance how many times you want to repeat
  something (especially when counting), and prefer `while` when the number of
  repetitions depends on a condition that might change unpredictably (like waiting
  for user input to be valid).
- Give counter variables clear names when it helps readability. `$i` is a very
  common, widely understood name for a simple loop counter, but `$row` or `$page`
  can be clearer in more specific situations.
- Keep the loop body focused. If the code inside a loop grows very long and does
  many unrelated things, it usually becomes harder to read and to debug.

## Exercise

Write a `for` loop that prints the multiplication table for 3, from `3 x 1 = 3` up
to `3 x 10 = 30`, with one line printed per multiplication.

## Solution

```php
<?php

for ($i = 1; $i <= 10; $i++) {
    echo "3 x " . $i . " = " . (3 * $i) . "\n";
}
```

The loop starts `$i` at 1 and repeats while `$i <= 10`, increasing `$i` by 1 after
each iteration. On every pass, it prints `3 x` followed by the current value of
`$i`, an equals sign, and the result of `3 * $i`. Because `$i` takes on every value
from 1 to 10 in turn, this produces exactly ten lines, one for each row of the
table, ending with `3 x 10 = 30`.

## Mini Quiz

```yaml
- question: "What is the main risk if you forget to update the counter variable inside a while loop?"
  options:
    - "The program will run slightly slower"
    - "The loop will never run at all"
    - "The loop condition may never become false, causing an infinite loop"
    - "PHP will automatically fix it for you"
  answer: 2
  explanation: "If nothing inside the loop changes the value the condition checks, the condition can stay true forever, creating an infinite loop that never stops."
- question: "In a for loop written as for ($i = 1; $i <= 5; $i++), which part runs after every iteration's body has finished?"
  options:
    - "$i = 1"
    - "$i <= 5"
    - "$i++"
    - "The loop's opening curly brace"
  answer: 2
  explanation: "The third part of a for loop, here $i++, is the increment step, and it runs at the end of every iteration, after the loop body executes."
- question: "Which loop type is generally best suited for a task where you already know exactly how many times you want to repeat something, such as counting from 1 to 10?"
  options:
    - "while"
    - "for"
    - "foreach"
    - "None of PHP's loops support counting"
  answer: 1
  explanation: "A for loop is designed for cases where the number of repetitions is known in advance, bundling the counter setup, condition, and increment together in one line."
```
