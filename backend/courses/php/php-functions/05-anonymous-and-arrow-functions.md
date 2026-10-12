---
slug: anonymous-and-arrow-functions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "Anonymous Functions and Arrow Functions"
difficulty: beginner
order: 5
estimatedMinutes: 12
tags: [php, functions, closures]
---

## Learning Objective

By the end of this lesson, you will understand what anonymous functions (closures) are, how to use the `use` keyword to capture outside variables, and how to write the shorter arrow function syntax.

## Explanation

Every function you have written so far has had a name, defined with `function someName() { ... }`. But PHP also lets you create a function that has no name at all, called an **anonymous function**, sometimes also called a **closure**. Instead of being defined with a name you call later, an anonymous function is usually created and immediately stored inside a variable, or passed directly into another function.

```php
$greet = function ($name) {
    echo "Hello, $name!\n";
};

$greet("Alice"); // calling it through the variable that holds it
```

Here, `$greet` is a regular variable, but the value it holds happens to be a function. You can call it using `$greet(...)`, exactly as if `$greet` were a function name.

Why would you want a function with no name? The most common reason is to pass a small, one-off piece of behavior directly into another function as an argument, without bothering to define and name a separate function elsewhere just to use it once. You already met two functions like this in Chapter 1: `array_map()`, which applies a function to every element of an array and returns a new array of the results, and `array_filter()`, which applies a function to every element and keeps only the elements where that function returns `true`. Both of these expect a function as one of their arguments — and anonymous functions are a natural fit for that role.

```php
$prices = [10, 25, 40, 5];

$expensiveOnly = array_filter($prices, function ($price) {
    return $price > 20;
});
// $expensiveOnly now contains [25, 40] (with original array keys preserved)
```

By default, an anonymous function cannot see variables that exist outside of it, even ones defined just before it, in the same script. This might feel surprising, but it is intentional (the next lesson, on variable scope, explains this idea in depth). If you need an anonymous function to use a specific outside variable, you must explicitly say so using the `use` keyword, right after the parameter list:

```php
$minimumPrice = 20;

$expensiveOnly = array_filter($prices, function ($price) use ($minimumPrice) {
    return $price > $minimumPrice;
});
```

`use ($minimumPrice)` tells PHP: "let this anonymous function see the outside variable `$minimumPrice`, as it was at the moment the function was created." Without writing `use (...)`, referring to `$minimumPrice` inside the anonymous function would fail, because it would be treated as an entirely separate, empty variable.

Because writing `function ($price) use ($minimumPrice) { return ...; }` is fairly long for such a simple task, PHP (from version 7.4 onward) offers a shorter alternative called an **arrow function**, written with `fn` instead of `function`:

```php
$expensiveOnly = array_filter($prices, fn($price) => $price > $minimumPrice);
```

An arrow function is written as `fn(parameters) => expression`. It automatically returns the result of the expression after `=>` — there is no `return` keyword and no curly braces needed. Even more conveniently, an arrow function automatically captures any outside variables it refers to, without needing a `use` clause at all. This makes arrow functions especially popular for short, simple operations like the ones typically passed to `array_map()` and `array_filter()`.

## Why It Matters

Anonymous and arrow functions matter because a huge amount of real PHP code, especially in modern frameworks like Symfony, is organized around passing small pieces of behavior into other functions, rather than only calling named functions directly. Array functions like `array_map()` and `array_filter()` are just the beginning: Symfony and many PHP libraries let you register a small function to run when a specific event happens (for example, "run this code whenever a new user signs up"), and anonymous or arrow functions are the natural, lightweight way to write that behavior right where it is used, instead of forcing you to invent and name a separate function for every small task. Being comfortable reading and writing `function (...) { ... }` and `fn(...) => ...` is essential for reading real-world PHP code, because you will see this pattern constantly once you move beyond small standalone scripts.

## Example

Imagine a small online store with a list of product prices that needs two different filtered views: products above a minimum price, and every price converted into its value after a fixed discount. Both are perfect uses for `array_filter()` and `array_map()` paired with short throwaway functions.

## Code

```php
<?php

$prices = [10, 25, 40, 5, 60];

// Anonymous function with `use`, capturing the outside $minimumPrice variable.
$minimumPrice = 20;
$expensiveOnly = array_filter($prices, function ($price) use ($minimumPrice) {
    return $price > $minimumPrice;
});

// The same filter, written as a shorter arrow function.
// Arrow functions capture outside variables automatically, no `use` needed.
$expensiveOnlyShort = array_filter($prices, fn($price) => $price > $minimumPrice);

// Using array_map with an arrow function to apply a 10% discount to every price.
$discounted = array_map(fn($price) => $price * 0.9, $prices);

echo implode(", ", $expensiveOnly) . "\n";
echo implode(", ", $discounted) . "\n";
```

## Code Explanation

- `$prices = [10, 25, 40, 5, 60];` creates a regular indexed array, as covered in Chapter 1.
- `array_filter($prices, function ($price) use ($minimumPrice) { return $price > $minimumPrice; })` calls `array_filter()`, passing it an anonymous function as its second argument. `array_filter()` runs that anonymous function once for every element in `$prices`, keeping only the elements where it returns `true`. The `use ($minimumPrice)` clause is required here because the anonymous function refers to `$minimumPrice`, a variable defined outside of it.
- `array_filter($prices, fn($price) => $price > $minimumPrice)` does exactly the same filtering, but using the shorter arrow function syntax. There is no `use` clause because arrow functions automatically capture outside variables like `$minimumPrice` on their own.
- `array_map(fn($price) => $price * 0.9, $prices)` calls `array_map()`, which runs the given function on every single element of `$prices` and collects all the results into a brand-new array, `$discounted`. Multiplying by `0.9` reduces each price by 10%.
- `implode(", ", $expensiveOnly)` and `implode(", ", $discounted)`, from Chapter 1, join the resulting arrays' values into readable, comma-separated strings for printing.

## Common Mistakes

- **Forgetting the `use` clause when a regular (non-arrow) anonymous function needs an outside variable.** Without `use ($variableName)`, the anonymous function simply cannot see that variable, and PHP will treat it as undefined inside the function.
- **Assuming arrow functions need a `use` clause too.** They do not — this is one of their main advantages. Adding `use (...)` to an arrow function is actually a syntax error, since arrow functions handle capturing automatically.
- **Trying to fit multi-line logic into an arrow function.** An arrow function body must be a single expression after `=>`; if your logic needs multiple steps, an `if` statement, or a loop, you need a regular anonymous (or named) function with curly braces instead.
- **Forgetting that array functions like `array_filter()` preserve original array keys.** After filtering, the resulting array's keys may skip around (for example `[1 => 25, 2 => 40]`), which can be surprising if you expect a fresh `0, 1, 2, ...` sequence. Using `array_values()` afterward re-indexes it if needed.

## Best Practices

- Prefer arrow functions (`fn(...) => ...`) for short, single-expression logic, especially when passing behavior into `array_map()`, `array_filter()`, or similar functions.
- Use a regular anonymous function with curly braces when the logic needs more than one line, such as an `if` statement or a loop inside it.
- Give anonymous/arrow function parameters clear names, just as you would for named functions, even though the function itself has no name.
- Only capture the specific outside variables an anonymous function actually needs with `use (...)` — avoid capturing more than necessary, to keep the function's behavior easy to reason about.

## Exercise

Given an array of ages `$ages = [12, 17, 18, 25, 16, 30];`, use `array_filter()` with an arrow function to create a new array `$adults` containing only the ages that are `18` or greater. Then use `array_map()` with an arrow function to create `$agesNextYear`, where every age in the original `$ages` array has `1` added to it.

## Solution

```php
<?php

$ages = [12, 17, 18, 25, 16, 30];

$adults = array_filter($ages, fn($age) => $age >= 18);

$agesNextYear = array_map(fn($age) => $age + 1, $ages);

echo implode(", ", $adults) . "\n";        // 18, 25, 30
echo implode(", ", $agesNextYear) . "\n";  // 13, 18, 19, 26, 17, 31
```

`array_filter($ages, fn($age) => $age >= 18)` runs the arrow function once per element of `$ages`, keeping only the ages for which `$age >= 18` evaluates to `true`, producing `18`, `25`, and `30` (with their original positions in the array preserved). `array_map($ages, fn($age) => $age + 1)`... more precisely `array_map(fn($age) => $age + 1, $ages)` runs the arrow function on every single age in `$ages`, without skipping any, and collects each result (the age plus one) into the new `$agesNextYear` array, which has the same number of elements as the original.

## Mini Quiz

```yaml
- question: "What is an anonymous function in PHP?"
  options:
    - "A function that can only be called once"
    - "A function that has no name and is often stored in a variable or passed directly to another function"
    - "A function that automatically runs when the script starts"
    - "A function that cannot accept any parameters"
  answer: 1
  explanation: "An anonymous function (also called a closure) is defined without a name, typically assigned to a variable or passed directly as an argument to another function."
- question: "Why is the `use ($variableName)` clause needed on a regular anonymous function but not on an arrow function?"
  options:
    - "use is only needed for functions with more than one parameter"
    - "Regular anonymous functions don't capture outside variables automatically, so use explicitly lets them in; arrow functions capture outside variables automatically"
    - "use makes the function run faster, and arrow functions don't need speed improvements"
    - "Arrow functions cannot access any variables at all, ever"
  answer: 1
  explanation: "A plain anonymous function needs use (...) to explicitly bring in an outside variable, since it doesn't see outer variables by default. Arrow functions automatically capture outer variables they reference, with no use clause required."
- question: "Which of these is valid arrow function syntax that returns $price multiplied by 2?"
  options:
    - "function($price) use return $price * 2;"
    - "fn($price) => $price * 2"
    - "fn($price) { return $price * 2 }"
    - "arrow($price) => $price * 2;"
  answer: 1
  explanation: "Arrow functions use the syntax fn(parameters) => expression, where the expression's value is automatically returned, with no curly braces or return keyword needed."
```
