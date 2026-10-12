---
slug: type-declarations-and-defaults
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "Type Declarations and Default Parameters"
difficulty: beginner
order: 3
estimatedMinutes: 10
tags: [php, functions, types]
---

## Learning Objective

By the end of this lesson, you will be able to add type declarations to function parameters and return values, and give parameters default values so they become optional when calling the function.

## Explanation

In the previous lesson, our functions accepted parameters like `$price` and `$taxRate` without saying anything about what *kind* of value they expected — a number, a piece of text, or something else entirely. PHP normally does not force you to specify this, but you are allowed to, and doing so brings real benefits. This is called a **type declaration** (sometimes called a "type hint"): you write the expected data type directly before a parameter's name in the function definition.

```php
function add(int $a, int $b) {
    return $a + $b;
}
```

Here, `int` before `$a` and before `$b` tells PHP (and anyone reading the code) that both parameters are expected to be whole numbers (integers). If someone tries to call `add("hello", 3)`, PHP will notice that `"hello"` cannot reasonably be treated as an integer and will raise an error, rather than silently producing a broken or confusing result.

You can also declare the type of value a function will `return`, by writing a colon and the type after the closing parenthesis of the parameter list, and before the opening curly brace:

```php
function add(int $a, int $b): int {
    return $a + $b;
}
```

This says: "this function takes two integers, and it promises to give back an integer." Common types you will use include `int` (whole numbers), `float` (decimal numbers), `string` (text), `bool` (true/false), and `array` (a list or collection, which you learned about in Chapter 1).

Next, sometimes a parameter has a sensible value that should be used most of the time, but you still want to allow the caller to override it occasionally. This is what a **default parameter value** is for. You give a parameter a default by writing `= value` after its name in the function definition:

```php
function greet(string $name = "Guest") {
    echo "Hello, $name!\n";
}

greet("Alice"); // prints "Hello, Alice!"
greet();        // prints "Hello, Guest!" because $name was omitted
```

When you omit an argument that has a default value, PHP automatically fills it in for you using the default, instead of raising an error demanding you supply it. A parameter with a default value is often called an "optional parameter," while one without a default is a "required parameter." An important rule in PHP is that once you start giving parameters defaults, any required (non-default) parameters must be listed first — you cannot have an optional parameter appear before a required one in the list.

Finally, it is worth knowing that PHP has an optional mode called **strict types**, turned on by writing `declare(strict_types=1);` as the very first statement in a PHP file. Normally, PHP will try to automatically convert values to match a type declaration (for example, turning the string `"5"` into the integer `5` if a parameter expects `int`). With strict types turned on, PHP refuses to do these automatic conversions and demands the exact type instead, catching more potential mistakes. This is considered a more advanced, professional practice — you do not need to use it yet, but you will likely see `declare(strict_types=1);` at the top of real PHP files, including in Symfony projects, so it is useful to recognize what it means.

## Why It Matters

Type declarations exist because PHP, by default, is quite relaxed about mixing different kinds of values, which is convenient for very short scripts but risky for larger, real applications. Imagine a function meant to calculate a shipping cost that expects a number but accidentally receives a piece of text, perhaps because of a mistake somewhere earlier in a large program. Without a type declaration, this mistake might go unnoticed until it produces a confusing, incorrect result far away from where the actual bug happened — making it very hard to track down. With a type declaration in place, PHP raises a clear error message immediately at the point of the mistake, which is far easier to diagnose and fix. Type declarations also work as a form of built-in documentation: another developer (or you, reading your own code months later) can look at a function's signature and immediately understand what kind of data it expects and produces, without having to read through its entire body.

Default parameter values matter because many real functions have one "usual" behavior but occasionally need flexibility. For example, a function that formats a date might almost always use a standard format, but sometimes needs a different one. Giving the format parameter a sensible default means most callers can ignore that detail entirely, while still allowing it to be customized when truly needed. This keeps everyday function calls short and readable.

## Example

Imagine a small function used throughout a website to build a personalized welcome message. Most visitors are logged in and have a name, but visitors browsing anonymously should still see a friendly, generic greeting without the calling code needing to handle that case specially every time.

## Code

```php
<?php

function buildWelcomeMessage(string $name = "Guest", int $visitCount = 1): string {
    if ($visitCount > 1) {
        return "Welcome back, $name! This is visit number $visitCount.";
    }

    return "Welcome, $name!";
}

echo buildWelcomeMessage("Alice", 3) . "\n";
echo buildWelcomeMessage("Bob") . "\n";
echo buildWelcomeMessage() . "\n";
```

## Code Explanation

- `function buildWelcomeMessage(string $name = "Guest", int $visitCount = 1): string` declares a function with two parameters. `string $name = "Guest"` means `$name` must be text, and defaults to `"Guest"` if not supplied. `int $visitCount = 1` means `$visitCount` must be a whole number, and defaults to `1`. The `: string` after the parentheses declares that this function always returns text.
- Inside the function, an `if` statement (from Chapter 1) checks whether `$visitCount` is greater than `1`, and returns a different message mentioning the visit count in that case.
- `buildWelcomeMessage("Alice", 3)` supplies both arguments explicitly, so `$name` becomes `"Alice"` and `$visitCount` becomes `3`, producing the "Welcome back" message.
- `buildWelcomeMessage("Bob")` only supplies one argument. `$name` becomes `"Bob"`, and since `$visitCount` was omitted, PHP automatically uses its default value of `1`.
- `buildWelcomeMessage()` supplies no arguments at all, so both parameters fall back to their defaults: `$name` becomes `"Guest"` and `$visitCount` becomes `1`.

## Common Mistakes

- **Putting a required parameter after an optional one.** Writing `function greet(string $name = "Guest", int $age)` is not allowed in PHP, because once `$name` has a default, PHP needs to know how to interpret an argument list that skips it — required parameters must come first.
- **Assuming type declarations automatically validate everything about a value**, such as a number being positive or a string being non-empty. A type declaration only checks the general kind of data (is it a string? a number?), not specific business rules — those still need to be checked separately with your own logic.
- **Forgetting that without `declare(strict_types=1);`, PHP will quietly convert compatible values**, such as turning the string `"5"` into the integer `5` for an `int` parameter. This is usually harmless but can occasionally hide a mistake that strict types would have caught.
- **Overusing default values for parameters that should really always be provided explicitly**, which can make it unclear at a glance what a typical call to the function actually needs.

## Best Practices

- Add type declarations to your function parameters and return types whenever you reasonably can — it costs little and catches real mistakes early.
- Choose default values that represent the most common, sensible case, so that most calls to the function can omit that parameter entirely.
- List required parameters first, followed by any optional ones with defaults, matching PHP's required ordering rule.
- Get comfortable reading `declare(strict_types=1);` at the top of PHP files, even before you start writing it yourself — it is very common in professional PHP and Symfony codebases.

## Exercise

Write a function called `formatPrice` that accepts a required `float $amount` parameter and an optional `string $currencySymbol` parameter defaulting to `"$"`. The function should return a string combining the currency symbol and the amount, such as `"$19.99"`. Call the function once with only the amount, and once supplying a different currency symbol such as `"€"`.

## Solution

```php
<?php

function formatPrice(float $amount, string $currencySymbol = "$"): string {
    return $currencySymbol . $amount;
}

echo formatPrice(19.99) . "\n";        // uses the default "$" symbol
echo formatPrice(19.99, "€") . "\n";   // overrides with a custom symbol
```

`formatPrice` declares `$amount` as a required `float` parameter, since every call needs a price to format, while `$currencySymbol` is optional and defaults to `"$"`. The first call, `formatPrice(19.99)`, only supplies the amount, so PHP fills in `"$"` automatically, producing `"$19.99"`. The second call explicitly overrides the symbol with `"€"`, producing `"€19.99"`. Because the return type is declared as `string`, and the function combines the symbol and amount with the string concatenation operator `.`, the result is always text ready to be displayed.

## Mini Quiz

```yaml
- question: "What does writing `int $a` as a function parameter do?"
  options:
    - "It converts every value passed in into the number 1"
    - "It declares that the parameter $a is expected to be an integer"
    - "It gives $a a default value of 0"
    - "It makes the function return an integer"
  answer: 1
  explanation: "A type declaration like `int $a` tells PHP the parameter is expected to hold a whole number, and PHP will raise an error if an incompatible value is passed."
- question: "In `function greet(string $name = \"Guest\") { ... }`, what happens if you call `greet()` without any arguments?"
  options:
    - "PHP raises an error because an argument is required"
    - "$name automatically becomes an empty string"
    - "$name automatically becomes \"Guest\", the declared default value"
    - "The function does not run at all"
  answer: 2
  explanation: "Because $name has a default value of \"Guest\", omitting the argument causes PHP to use that default automatically instead of raising an error."
- question: "Which parameter ordering rule must PHP function definitions follow?"
  options:
    - "Optional (default-value) parameters must come before required parameters"
    - "Required parameters must come before optional (default-value) parameters"
    - "Parameters must be listed in alphabetical order"
    - "There is no ordering rule in PHP"
  answer: 1
  explanation: "PHP requires that parameters without a default value (required parameters) be listed before any parameters that have a default value (optional parameters)."
```
