---
slug: numbers
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Working with Numbers"
difficulty: beginner
order: 6
estimatedMinutes: 12
tags: [php, numbers, operators, math]
---

## Learning Objective

By the end of this lesson, you will be able to do arithmetic in PHP using the standard
math operators, understand the difference between integer and float numbers when doing
calculations, increment and decrement numbers, and use a few built-in functions that
help you work with numbers safely.

## Explanation

You already know from an earlier lesson that PHP has two number types: **integers**
(whole numbers with no decimal point, like `4` or `-10`) and **floats** (numbers with a
decimal point, like `4.5` or `-10.25`). This lesson is about *doing things* with those
numbers.

PHP gives you these arithmetic operators:

- `+` addition, e.g. `5 + 2` gives `7`
- `-` subtraction, e.g. `5 - 2` gives `3`
- `*` multiplication, e.g. `5 * 2` gives `10`
- `/` division, e.g. `5 / 2` gives `2.5`
- `%` modulo (the **remainder** left over after division), e.g. `5 % 2` gives `1`,
  because 2 goes into 5 twice with 1 left over
- `**` exponentiation (raising to a power), e.g. `5 ** 2` gives `25` (5 squared)

A very important detail about `/` (division): in many other languages, dividing two
integers gives you another integer, throwing away anything after the decimal point.
**PHP does not do this.** `7 / 2` in PHP gives `3.5`, a float, not `3`. PHP always gives
you the mathematically correct answer when you use `/`, converting the result to a
float automatically if the numbers do not divide evenly. If you actually want just the
whole-number part of a division (throwing away the remainder), you need to be explicit
about it — for example with `intdiv(7, 2)`, which gives `3`, or by wrapping the result
in `(int)` to convert it, like `(int) (7 / 2)`.

PHP also has two operators for changing a number by exactly one:

- `++` increment: adds 1 to a variable. `$score++;` is the same as writing
  `$score = $score + 1;`.
- `--` decrement: subtracts 1 from a variable. `$lives--;` is the same as writing
  `$lives = $lives - 1;`.

These are shortcuts you will see constantly in real code, especially for counting
things.

Finally, three built-in functions are extremely useful when working with numbers:

- `round()` rounds a float to the nearest whole number (or to a given number of
  decimal places if you pass a second argument). `round(4.567, 2)` gives `4.57`.
- `intval()` converts a value to an integer, throwing away any decimal part.
  `intval(4.9)` gives `4` (it does not round — it simply cuts off the decimal part).
- `is_numeric()` checks whether a value *looks like* a number (including numbers
  written as text, such as `"42"`) and returns `true` or `false`. This is very handy
  when you receive input from a user and are not yet sure if it is a valid number.

## Why It Matters

Almost every real application needs to calculate something: a shopping cart total, a
shipping cost, a percentage discount, an average rating, a countdown timer, or a page
number for pagination. Getting comfortable with arithmetic operators, and knowing that
PHP's `/` always returns exact (float) results rather than silently truncating, will
save you from a whole category of subtle bugs — for example, miscalculating a total
price by a few cents because you assumed integer division would happen automatically.

The `%` (modulo) operator in particular shows up everywhere in real projects: checking
if a number is even (`$n % 2 === 0`), deciding which items go in which column of a
grid, or figuring out if today is a billing day. Functions like `is_numeric()` matter
because data coming from outside your program — a web form, a file, a database — is
never guaranteed to be a clean number, and checking before calculating protects your
program from crashing or producing nonsense results.

## Example

Imagine an online shop that needs to calculate the final price of an order, including
a 20% tax rate, and show the customer a nicely rounded total in dollars and cents.

## Code

```php
<?php

$pricePerItem = 19.99;
$quantity = 3;
$taxRate = 0.20;

$subtotal = $pricePerItem * $quantity;
$taxAmount = $subtotal * $taxRate;
$total = $subtotal + $taxAmount;

$roundedTotal = round($total, 2);

echo "Subtotal: " . $subtotal . "\n";
echo "Tax: " . round($taxAmount, 2) . "\n";
echo "Total: " . $roundedTotal . "\n";

$itemsLeftInStock = 10;
$itemsLeftInStock--;
$itemsLeftInStock--;

echo "Items left in stock: " . $itemsLeftInStock . "\n";

$remainder = $quantity % 2;
echo "Quantity is even? " . ($remainder === 0 ? "yes" : "no") . "\n";
```

## Code Explanation

- `$pricePerItem = 19.99;` and `$quantity = 3;` store a float and an integer. PHP does
  not require you to declare a number's type in advance — it figures it out from the
  value you assign.
- `$subtotal = $pricePerItem * $quantity;` multiplies the price by the quantity using
  `*`, giving the cost before tax.
- `$taxAmount = $subtotal * $taxRate;` calculates 20% of the subtotal by multiplying it
  by `0.20`.
- `$total = $subtotal + $taxAmount;` adds the tax on top of the subtotal using `+`.
- `round($total, 2)` rounds the total to 2 decimal places, which is exactly what you
  want when displaying a price in dollars and cents (you cannot sensibly show someone
  a price with 8 decimal digits).
- `"\n"` inside the strings is a special character that means "start a new line" — it
  makes the output easier to read when printed.
- `$itemsLeftInStock--;` used twice reduces the stock count by 1 each time, going from
  10 down to 8.
- `$quantity % 2` calculates the remainder of dividing `3` by `2`, which is `1`. The
  ternary expression `($remainder === 0 ? "yes" : "no")` then checks: if the remainder
  equals `0`, the number is even, so it prints `"yes"`; otherwise it prints `"no"`. You
  do not need to fully master the ternary syntax yet — it is just a compact way of
  writing "if this, then that, otherwise this other thing" in a single line.

## Common Mistakes

- **Assuming `/` truncates like in some other languages.** `7 / 2` in PHP is `3.5`, not
  `3`. If you need the whole-number part only, use `intdiv(7, 2)` or `(int) (7 / 2)`.
- **Confusing `intval()` with rounding.** `intval(4.9)` gives `4`, not `5`, because it
  simply chops off the decimal part rather than rounding to the nearest whole number.
  If you want proper rounding, use `round()`.
- **Doing money math with floats and expecting perfectly exact results.** Floats are
  stored in a way that can introduce tiny rounding errors (for example,
  `0.1 + 0.2` may not print as exactly `0.3` internally). For this lesson's level, just
  remember to `round()` values before displaying them; real financial systems often
  work in cents (integers) internally to avoid this entirely.
- **Forgetting that `++`/`--` change the variable itself.** `$score++;` does not just
  produce a new value — it overwrites `$score` with the incremented value.

## Best Practices

- Use `round()` whenever you are about to *display* a calculated number to a user,
  especially prices, percentages, or averages.
- Use `is_numeric()` to check any number that came from outside your program (a form
  field, a file, user input) before doing arithmetic with it, so your program does not
  behave unpredictably on unexpected input.
- Prefer `intdiv()` over `(int) ($a / $b)` when you specifically want integer division,
  since its name makes your intention obvious to anyone reading the code later.
- Add short comments above calculations that are not immediately obvious (like a tax
  formula), so future readers of your code — including future you — understand the
  "why," not just the "what."

## Exercise

Write a PHP script that stores a temperature of `98.6` (a float, representing degrees
Fahrenheit) in a variable, converts it to Celsius using the formula
`celsius = (fahrenheit - 32) * 5 / 9`, rounds the result to 1 decimal place, and prints
a message like `"98.6°F is 37°C"` (using the rounded value).

## Solution

```php
<?php

$fahrenheit = 98.6;
$celsius = ($fahrenheit - 32) * 5 / 9;
$roundedCelsius = round($celsius, 1);

echo $fahrenheit . "°F is " . $roundedCelsius . "°C";
```

The subtraction happens first inside the parentheses (`98.6 - 32 = 66.6`), then it is
multiplied by `5` (`333`), then divided by `9`, giving approximately `37.0`. Rounding
to 1 decimal place with `round($celsius, 1)` keeps the display clean, and the final
`echo` line concatenates everything into one readable sentence.

## Mini Quiz

```yaml
- question: "What does `7 / 2` evaluate to in PHP?"
  options:
    - "3"
    - "3.5"
    - "4"
    - "An error, because 7 does not divide evenly by 2"
  answer: 1
  explanation: "PHP's / operator always returns the mathematically exact result, converting to a float when needed, so 7 / 2 is 3.5, not 3."
- question: "What does the % (modulo) operator return?"
  options:
    - "The result of dividing two numbers, rounded down"
    - "The remainder left over after dividing two numbers"
    - "The percentage one number is of another"
    - "The absolute value of a number"
  answer: 1
  explanation: "Modulo (%) gives the remainder after division. For example, 5 % 2 is 1, because 2 fits into 5 twice with 1 left over."
- question: "What does `intval(4.9)` return?"
  options:
    - "5"
    - "4.9"
    - "4"
    - "An error"
  answer: 2
  explanation: "intval() converts a value to an integer by cutting off the decimal part rather than rounding, so intval(4.9) is 4, not 5."
- question: "After running `$lives = 3; $lives--;`, what is the value of `$lives`?"
  options:
    - "2"
    - "3"
    - "4"
    - "-3"
  answer: 0
  explanation: "The -- operator decreases the variable's value by 1, so $lives goes from 3 to 2."
```
