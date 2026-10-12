---
slug: variadic-functions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "Variadic Functions"
difficulty: beginner
order: 4
estimatedMinutes: 9
tags: [php, functions, variadic]
---

## Learning Objective

By the end of this lesson, you will be able to write a function that accepts any number of arguments using the `...` syntax, and understand how to use those collected arguments inside the function.

## Explanation

Every function you have written so far has a fixed number of parameters. `calculateTotalWithTax($price, $taxRate)` always expects exactly two arguments — not one, not three. But sometimes you genuinely do not know in advance how many values a function will need to work with. For example, imagine a function that adds up numbers: sometimes you might want to add two numbers, sometimes five, sometimes just one. Writing separate functions like `addTwoNumbers`, `addThreeNumbers`, and so on for every possible count would be impractical.

PHP solves this with **variadic functions** (from "variable number of arguments"). You mark a parameter as variadic by writing three dots `...` right before its name in the function definition. This tells PHP: "collect any number of arguments passed here into a single array."

```php
function sumAll(...$numbers) {
    // $numbers is an array containing every argument passed in
    return array_sum($numbers);
}

echo sumAll(1, 2, 3);       // 6
echo sumAll(10, 20);        // 30
echo sumAll(5);             // 5
```

Inside the function, `$numbers` behaves exactly like a regular indexed array, which you already learned about in Chapter 1. This means you can use a `foreach` loop over it, count its elements with `count()`, or use built-in array functions like `array_sum()` directly on it.

You are also allowed to combine regular, fixed parameters with a variadic one, as long as the variadic parameter is listed last:

```php
function announceWinners(string $eventName, ...$winnerNames) {
    echo "Winners of $eventName: " . implode(", ", $winnerNames) . "\n";
}

announceWinners("Chess Tournament", "Alice", "Bob", "Chen");
```

There is also a related but separate use of the `...` syntax, called **argument unpacking** (or "spreading"), which happens on the *caller's* side instead of the function definition's side. If you already have an array of values and want to pass each of its elements as a separate argument into a function, you can write `...` before the array when calling the function:

```php
$scores = [95, 82, 76];
echo sumAll(...$scores); // same as writing sumAll(95, 82, 76)
```

Here, `...$scores` "unpacks" the array into three separate arguments, rather than passing the whole array as one single argument.

## Why It Matters

Variadic functions matter because plenty of real-world tasks have a genuinely unpredictable number of inputs. Consider a logging function used throughout a website that needs to combine several pieces of text into one log message, or a function that calculates the average of however many test scores a student happens to have that semester — three scores for one student, six for another. Without variadic parameters, you would either have to force every caller to build an array manually every single time (which is extra, repetitive work) or write many near-duplicate functions for different argument counts (which brings back the exact code-duplication problem functions were meant to solve in the first place).

You have actually already been using variadic functions without necessarily noticing: PHP's own built-in `implode()` groundwork aside, functions like `array_merge()`, which you may encounter in real projects, accept any number of arrays. Understanding `...` prepares you to both read functions like these and write your own flexible utilities, which is a common need in real PHP applications, including ones built with Symfony.

## Example

Imagine a small utility used across an application to join together any number of name strings into a single, friendly sentence — sometimes two names, sometimes five, without knowing in advance.

## Code

```php
<?php

function joinNames(string $separator, ...$names): string {
    return implode($separator, $names);
}

echo joinNames(", ", "Alice", "Bob", "Chen") . "\n";
echo joinNames(" and ", "Sam", "Riley") . "\n";

// Argument unpacking: passing an existing array's items as separate arguments.
$teamMembers = ["Dana", "Priya", "Omar", "Lee"];
echo joinNames(", ", ...$teamMembers) . "\n";
```

## Code Explanation

- `function joinNames(string $separator, ...$names): string` defines a function with one fixed, required parameter `$separator`, followed by a variadic parameter `...$names`. Because `$separator` is listed first and `...$names` is listed last, this is valid — a variadic parameter must always come at the end of the parameter list.
- Inside the function, `$names` is a normal indexed array containing every argument passed after `$separator`. `implode($separator, $names)`, a function from Chapter 1, joins all the array elements into one string, placed between each pair with `$separator`.
- `joinNames(", ", "Alice", "Bob", "Chen")` passes `", "` as `$separator`, and then three separate string arguments, which all get collected into `$names` as `["Alice", "Bob", "Chen"]`.
- `joinNames(" and ", "Sam", "Riley")` shows the same function working correctly with a different number of names (two instead of three) and a different separator.
- `$teamMembers = ["Dana", "Priya", "Omar", "Lee"];` builds a regular array. Then `joinNames(", ", ...$teamMembers)` uses `...` on the caller's side to unpack that array, so it is exactly as if we had written `joinNames(", ", "Dana", "Priya", "Omar", "Lee")` by hand.

## Common Mistakes

- **Placing a variadic parameter anywhere except last.** `function example(...$items, string $label)` is not valid PHP — a variadic parameter must be the final parameter, since it is designed to "collect the rest."
- **Confusing the two different uses of `...`.** Writing `...` before a parameter name in a function *definition* collects arguments into an array. Writing `...` before an array when *calling* a function unpacks it into separate arguments. They look identical but happen on opposite sides of a function call.
- **Forgetting that the variadic parameter is always an array inside the function**, even if only one argument (or zero arguments) was passed. Trying to use it as if it were a single value directly (instead of looping over it or using array functions) will not work as expected.
- **Passing the wrong kind of array when unpacking.** Argument unpacking with `...` expects a plain indexed array whose values will be matched to parameters by position; unpacking an associative array with unexpected keys can lead to confusing errors in more advanced cases.

## Best Practices

- Use a variadic parameter when a function's whole purpose is to operate on "however many" values, such as summing, joining, or averaging.
- Combine a variadic parameter with clear, fixed parameters before it when some information (like a separator or a label) is always required, and only the rest is flexible in count.
- Inside a variadic function, treat the collected parameter like any other array: loop over it, or pass it to array functions such as `array_sum()`, `count()`, or `implode()`.
- When you already have an array of values ready to pass into a variadic function, prefer unpacking with `...$array` over manually writing out each element.

## Exercise

Write a variadic function called `averageOf` that accepts any number of numeric arguments and returns their average (the sum divided by the count). Handle the case where no arguments are passed by returning `0` instead of dividing by zero. Then call it with three different sets of numbers, including one call using an existing array with `...` to unpack it.

## Solution

```php
<?php

function averageOf(...$numbers): float {
    if (count($numbers) === 0) {
        return 0;
    }

    return array_sum($numbers) / count($numbers);
}

echo averageOf(4, 8, 12) . "\n";   // 8
echo averageOf(10) . "\n";         // 10
echo averageOf() . "\n";           // 0, avoids dividing by zero

$scores = [70, 85, 90, 95];
echo averageOf(...$scores) . "\n"; // 85
```

`averageOf` collects every argument into the `$numbers` array using the variadic `...$numbers` parameter. The `if (count($numbers) === 0)` check guards against dividing by zero when the function is called with no arguments at all, returning `0` safely in that case. Otherwise, `array_sum($numbers) / count($numbers)` calculates the mathematical average: the total of all numbers divided by how many there are. The final call uses `...$scores` to unpack the `$scores` array into separate arguments, demonstrating that a variadic function works the same whether its arguments arrive one by one or come from unpacking an existing array.

## Mini Quiz

```yaml
- question: "What does writing `...$numbers` as a function parameter do?"
  options:
    - "It limits the function to accepting exactly three arguments"
    - "It collects any number of passed-in arguments into a single array"
    - "It automatically sorts the arguments before the function runs"
    - "It makes the parameter optional with a default value of an empty array"
  answer: 1
  explanation: "A variadic parameter, marked with ... before its name, gathers however many arguments were passed at that position into one array inside the function."
- question: "Where must a variadic parameter appear in a function's parameter list?"
  options:
    - "It must be the first parameter"
    - "It can be placed anywhere"
    - "It must be the last parameter"
    - "It must be the only parameter the function has"
  answer: 2
  explanation: "A variadic parameter collects all remaining arguments, so PHP requires it to be the final parameter in the list."
- question: "What does `...$scores` mean when written in front of an array at the point where a function is CALLED (not defined)?"
  options:
    - "It deletes the array after the call"
    - "It unpacks the array's elements into separate individual arguments for the call"
    - "It converts the array into a string"
    - "It has no effect outside of a function definition"
  answer: 1
  explanation: "Using ... before an array at a call site unpacks (spreads) its elements so each one is passed as its own separate argument, instead of passing the whole array as one argument."
```
