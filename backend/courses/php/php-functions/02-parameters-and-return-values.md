---
slug: parameters-and-return-values
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "Parameters and Return Values"
difficulty: beginner
order: 2
estimatedMinutes: 10
tags: [php, functions, parameters]
---

## Learning Objective

By the end of this lesson, you will be able to write functions that accept input values, and functions that send an output value back to the code that called them using `return`.

## Explanation

In the previous lesson, the `printDivider()` function always did exactly the same thing every time it was called — it had no way to behave differently based on information you gave it. Most useful functions, however, need to work with different values each time. For example, a function that greets someone should be able to greet "Alice" one time and "Bob" the next, without you writing a separate function for every possible name.

This is what **parameters** are for. A parameter is a named placeholder you write inside the parentheses of a function definition, representing a value the function expects to receive. When you actually call the function, the real value you provide is called an **argument**. So: parameters are the placeholders in the definition, and arguments are the real values you pass in when calling.

```php
function greet($name) {
    echo "Hello, $name!\n";
}

greet("Alice"); // "Alice" is the argument passed to the $name parameter
greet("Bob");   // "Bob" is a different argument, same function
```

A function can accept more than one parameter, separated by commas:

```php
function greetTwoPeople($firstName, $secondName) {
    echo "Hello, $firstName and $secondName!\n";
}

greetTwoPeople("Alice", "Bob");
```

Now, so far, every function we have written communicates its result only by printing with `echo`. But there is a more powerful and more flexible way for a function to produce output: the `return` statement. `return` sends a value back out of the function, to wherever the function was called from, so that value can be stored, reused, or combined with other logic — instead of being immediately and permanently printed to the screen.

```php
function addTax($price) {
    return $price * 1.20; // adds 20% tax and sends the result back
}

$total = addTax(100); // $total now holds the value 120
echo $total;
```

The key difference: a function that only `echo`s a value has already decided, on its own, exactly what happens to that value (printing it) — that decision cannot be undone by the calling code. A function that `return`s a value instead hands that value over and lets the caller decide what to do with it: print it, save it in a variable, use it in a math expression, pass it into another function, and so on. As soon as a `return` statement runs inside a function, the function stops immediately — no code after it, inside that function, will run.

## Why It Matters

Returning values instead of only printing them is one of the most important habits to build as a programmer, because it makes functions dramatically more reusable. Imagine a real online store's checkout page. It needs to calculate a total price with tax, but that total might need to be displayed on the page, saved into a database, added to a shipping calculation, or sent to a payment provider — all from the same calculation. If the function that calculates the total only ever printed the number with `echo`, none of those other uses would be possible; you would be stuck with whatever the function decided to do with the value. By using `return`, the calculation logic can live in one function, and every different part of the program that needs that number can call the function and then decide, on its own, what to do with the result. This separation — calculating a value versus deciding what to do with it — is a foundational idea used throughout professional PHP and Symfony applications.

## Example

Imagine an online shop that needs to calculate the final price of a product after adding a fixed sales tax rate. The calculation should happen in one function, and different parts of the program should be free to use that result differently — one part prints it to the customer, another part adds it to a running order total.

## Code

```php
<?php

function calculateTotalWithTax($price, $taxRate) {
    $total = $price * (1 + $taxRate);
    return $total;
}

// First use: print the total for a single item.
$itemPrice = 50;
$total = calculateTotalWithTax($itemPrice, 0.20);
echo "Total for this item: $" . $total . "\n";

// Second use: add several totals together to get an order sum.
$orderSum = 0;
$orderSum += calculateTotalWithTax(50, 0.20);
$orderSum += calculateTotalWithTax(30, 0.20);
echo "Order sum: $" . $orderSum . "\n";
```

## Code Explanation

- `function calculateTotalWithTax($price, $taxRate) { ... }` defines a function with two parameters, `$price` and `$taxRate`. Both act as placeholders that will be filled in with real values when the function is called.
- Inside the function, `$total = $price * (1 + $taxRate);` performs the actual math: multiplying the price by `1` plus the tax rate as a decimal (so a tax rate of `0.20` means 20%).
- `return $total;` sends the calculated value back out of the function. Once this line runs, the function immediately stops, and the value of `$total` becomes the result of the whole `calculateTotalWithTax(...)` call.
- `$total = calculateTotalWithTax($itemPrice, 0.20);` calls the function with `$itemPrice` (which holds `50`) as the argument for `$price`, and `0.20` as the argument for `$taxRate`. The value that comes back from `return` is stored in a new variable also named `$total` here — note this is just a variable name chosen at the call site; it is completely separate from the `$total` variable that existed inside the function.
- The second block reuses the exact same function twice with different arguments (`50` and `30`), and adds both results together into `$orderSum` using the `+=` operator you learned in Chapter 1. This demonstrates why returning is powerful: the same calculation function is being used for two very different purposes (printing, and summing) without being rewritten.

## Common Mistakes

- **Confusing parameters with arguments.** The parameter is the name written in the function definition (`$price`); the argument is the actual value supplied at the call site (`50`). Getting the terms mixed up isn't a bug, but understanding the distinction helps you read documentation and error messages correctly.
- **Expecting `echo` inside a function to make the value usable elsewhere.** `echo` only prints text to the screen; it does not give the calling code a value it can store or reuse. If you need the result available for further use, you must `return` it.
- **Forgetting that code after `return` never runs.** Any lines written after a `return` statement, still inside the same function, are unreachable and will simply be skipped every time.
- **Passing arguments in the wrong order.** PHP matches arguments to parameters by position unless you use named arguments (an intermediate feature not covered yet), so `calculateTotalWithTax(0.20, 50)` would incorrectly treat `0.20` as the price and `50` as the tax rate, producing a nonsensical result.

## Best Practices

- Prefer `return` over `echo` inside functions that calculate or produce a value. Let the code that calls the function decide whether to print it, store it, or use it further.
- Give parameters descriptive names, just like variables — `$price` and `$taxRate` are far clearer than `$a` and `$b`.
- Keep the order of arguments logical and consistent, and double check the order matches the parameter list when calling a function with several arguments.
- If a function is meant to produce a result, make sure every possible path through the function actually reaches a `return` statement, so it never accidentally returns nothing.

## Exercise

Write a function called `calculateRectangleArea` that accepts two parameters, `$width` and `$height`, and returns their product (the area of a rectangle). Then call the function with a width of `8` and a height of `5`, store the result in a variable called `$area`, and print a sentence such as "The area is 40".

## Solution

```php
<?php

function calculateRectangleArea($width, $height) {
    return $width * $height;
}

$area = calculateRectangleArea(8, 5);
echo "The area is $area\n";
```

The function `calculateRectangleArea` takes two parameters representing the rectangle's dimensions and immediately returns their product using `$width * $height`. When we call `calculateRectangleArea(8, 5)`, `8` is matched to `$width` and `5` is matched to `$height` based on their position, producing `40`. Because the function returns the value instead of printing it directly, we are free to store it in `$area` and build our own custom message around it with `echo`.

## Mini Quiz

```yaml
- question: "What is the difference between a parameter and an argument?"
  options:
    - "There is no difference; the words are interchangeable"
    - "A parameter is the placeholder name in the function definition; an argument is the real value passed in when calling"
    - "A parameter is used only with return; an argument is used only with echo"
    - "An argument can only be a number, while a parameter can be any type"
  answer: 1
  explanation: "Parameters are the named placeholders declared in the function's definition. Arguments are the actual values supplied when the function is called."
- question: "What happens to code written directly after a `return` statement inside the same function?"
  options:
    - "It runs first, before the return value is sent back"
    - "It causes a syntax error"
    - "It never runs, because return immediately ends the function"
    - "It runs only if the returned value is false"
  answer: 2
  explanation: "As soon as `return` executes, the function stops immediately, so any following code in that function body is never reached."
- question: "Why is returning a value from a function usually more flexible than only echoing it?"
  options:
    - "Returned values run faster than echoed ones"
    - "echo cannot be used inside functions at all"
    - "A returned value can be stored, reused, or passed elsewhere, while an echoed value is only printed immediately"
    - "Returning a value automatically saves it to a database"
  answer: 2
  explanation: "return hands the value back to the caller, who can decide what to do with it (store it, print it, combine it with other logic), whereas echo immediately and permanently prints it."
```
