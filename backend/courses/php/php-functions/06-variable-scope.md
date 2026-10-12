---
slug: variable-scope
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "Variable Scope"
difficulty: beginner
order: 6
estimatedMinutes: 9
tags: [php, functions, scope]
---

## Learning Objective

By the end of this lesson, you will understand what variable scope means, why variables inside a function are normally invisible outside it (and vice versa), and how to correctly share data between functions using parameters and return values instead of relying on global variables.

## Explanation

**Scope** refers to the part of a program where a particular variable can be seen and used. So far, all the code you have written in this course has existed in one single scope — the main body of the script — so every variable you created was visible everywhere else in that script. Functions change this. Every function you define creates its own separate, private scope, called **local scope**. Any variable created inside a function only exists and is only visible while that function is running, and disappears the moment the function finishes.

```php
function setMessage() {
    $message = "Hello from inside the function!";
    echo $message; // works fine, $message exists here
}

setMessage();
echo $message; // ERROR / undefined: $message does not exist out here
```

In this example, `$message` is created inside `setMessage()`, so it belongs to that function's local scope. Once `setMessage()` finishes running, `$message` ceases to exist. Trying to use `$message` outside the function, in the main script's scope (sometimes called the **global scope**), does not work, because PHP treats it as a completely different, unrelated area of the program.

This also works the other way around: a variable created in the global scope (the main body of the script, outside any function) is normally **not** automatically visible inside a function, even if the function is defined right next to it.

```php
$storeName = "PHP Corner";

function announceStore() {
    echo "Welcome to $storeName!"; // does NOT work: $storeName is invisible here
}
```

This might feel inconvenient at first, but it is a deliberate and important design decision, not a limitation to work around carelessly. If functions could freely see and change every variable in the rest of the program, it would become very difficult to predict what a function actually does just by looking at its own code — you would also have to check every other place in the entire program that might affect its variables. Keeping each function's local scope separate means a function's behavior depends only on what you explicitly give it (its parameters) and what it explicitly gives back (its `return` value), making it far easier to understand, test, and reuse safely.

PHP does provide a way to explicitly reach into the global scope from inside a function, using the `global` keyword:

```php
$storeName = "PHP Corner";

function announceStore() {
    global $storeName;
    echo "Welcome to $storeName!"; // now this works
}
```

Writing `global $storeName;` inside the function tells PHP: "connect this function's `$storeName` to the one from the global scope, instead of treating it as a new, separate variable." While this technically works, relying on `global` is generally considered poor practice in real projects, and you should avoid it except in rare, deliberate cases. The proper, much safer way to give a function access to outside information is to pass that information in as a parameter, and the proper way to get information back out is to `return` it — exactly what you already practiced in earlier lessons of this chapter.

```php
$storeName = "PHP Corner";

function announceStore(string $storeName) {
    echo "Welcome to $storeName!";
}

announceStore($storeName); // pass it in explicitly instead of using global
```

## Why It Matters

Variable scope exists to keep programs predictable and manageable as they grow. Imagine a large real-world application with hundreds of functions and thousands of variables. If every function could see and freely modify every variable anywhere else in the program, then understanding or safely changing any single function would require understanding the entire rest of the application at the same time — a change deep inside one function could unexpectedly break something completely unrelated elsewhere, simply because they happened to share a variable name. By keeping each function's variables local by default, PHP (like virtually every modern programming language) makes it possible to reason about a function in isolation: you only need to know its parameters and its return value to understand how it behaves, not the entire surrounding program. This is one of the most important ideas for writing reliable, maintainable code, and it becomes especially critical once you start building larger applications, including the Symfony projects this course is building toward, where code is organized into many small, focused, independently understandable pieces.

## Example

Imagine a simple script that tracks a shopping cart's total. A beginner might be tempted to let a function reach out and directly change a global total variable. A safer approach uses parameters and return values instead, so the function's behavior is clear and self-contained just from reading its definition.

## Code

```php
<?php

// This variable lives in the global scope (the main script).
$cartTotal = 0;

// A function that receives the current total and an item price,
// and RETURNS the new total, instead of reaching for global variables.
function addItemToTotal(float $currentTotal, float $itemPrice): float {
    return $currentTotal + $itemPrice;
}

$cartTotal = addItemToTotal($cartTotal, 19.99);
$cartTotal = addItemToTotal($cartTotal, 5.50);

echo "Cart total: $" . $cartTotal . "\n";
```

## Code Explanation

- `$cartTotal = 0;` creates a variable in the global scope, meaning it exists in the main body of the script, outside any function.
- `function addItemToTotal(float $currentTotal, float $itemPrice): float { ... }` defines a function with two parameters. Notice that `$currentTotal` is a parameter name chosen inside the function — it is a completely separate, local variable from the global `$cartTotal`, even though it represents similar information. The function has no way to see `$cartTotal` directly; it only knows about whatever value was passed into `$currentTotal`.
- Inside the function, `return $currentTotal + $itemPrice;` calculates the new total and sends it back out, without touching any variable outside the function.
- `$cartTotal = addItemToTotal($cartTotal, 19.99);` passes the current global `$cartTotal` value into the function as an argument, receives the returned new total, and stores it back into `$cartTotal`. This explicit "pass in, get back, store" pattern is what keeps the global variable's value correct, without the function ever needing direct access to it.
- The second call, `addItemToTotal($cartTotal, 5.50)`, reuses the same function, again passing in whatever `$cartTotal` currently holds, and updating it with the result.

## Common Mistakes

- **Expecting a function to automatically "see" a variable defined outside it.** As shown earlier, referring to an outside variable's name inside a function, without passing it in as a parameter or using `global`, does not work, and typically produces an "undefined variable" warning followed by unexpected behavior.
- **Reaching for the `global` keyword as a quick fix whenever a function needs outside data.** While `global` does work, overusing it recreates the exact problem scope is meant to prevent: functions whose behavior secretly depends on, and modifies, variables from far away in the program, which becomes very hard to track as a program grows.
- **Assuming a variable inside a function keeps its value between separate calls.** Each time a function is called, its local variables start fresh; a local variable does not remember values from a previous, separate call to the same function (this is different from parameters carrying values in from outside, and different from special cases like static variables, which are outside the scope of this lesson).
- **Confusing two variables that happen to share the same name in different scopes**, such as a parameter named `$total` inside a function and a completely unrelated global variable also named `$total`. They are not connected just because the names match.

## Best Practices

- Prefer passing data into functions through parameters, and getting data out through `return`, rather than relying on the `global` keyword.
- Treat each function as a self-contained unit: to understand what it does, you should only need to look at its parameters, its body, and its return value — not the rest of the program.
- If you notice a function needing many global variables to work correctly, treat that as a sign the function should instead accept that information as parameters.
- Reserve `global` for rare, deliberate situations, and even then, prefer to double check whether restructuring the code to use parameters and return values would be cleaner.

## Exercise

Write a global variable `$discountRate = 0.10;` representing a 10% discount. Then write a function called `applyDiscount` that takes a `float $price` parameter and a `float $discountRate` parameter, and returns the price after subtracting the discount (price minus price times discount rate). Call the function by passing in a price of `50` and the global `$discountRate`, and print the result. Do not use the `global` keyword.

## Solution

```php
<?php

$discountRate = 0.10;

function applyDiscount(float $price, float $discountRate): float {
    return $price - ($price * $discountRate);
}

$finalPrice = applyDiscount(50, $discountRate);
echo "Final price: $" . $finalPrice . "\n"; // Final price: $45
```

The global variable `$discountRate` lives in the main script's scope. Rather than letting `applyDiscount` reach out and grab that global variable directly (which would require the `global` keyword and would make the function depend on something outside its own definition), we pass its current value explicitly into the function as the `$discountRate` parameter. Inside the function, this parameter is a separate, local variable — it happens to share a name with the global one, but that is only a naming coincidence, not a connection. `$price - ($price * $discountRate)` calculates 10% of `50` (which is `5`) and subtracts it, returning `45`. Because the function receives everything it needs through its parameters and hands its result back through `return`, its behavior is fully predictable just from reading its own definition.

## Mini Quiz

```yaml
- question: "What does 'scope' refer to in programming?"
  options:
    - "How fast a program runs"
    - "The part of the program where a particular variable can be seen and used"
    - "The number of parameters a function has"
    - "The total number of lines in a script"
  answer: 1
  explanation: "Scope describes where in a program a variable is visible and usable. A function's local scope is separate from the global scope of the main script."
- question: "Why can a function not normally see a variable defined outside it in the global scope?"
  options:
    - "PHP does not allow variables outside of functions at all"
    - "Because each function has its own local scope by default, keeping its behavior predictable and self-contained"
    - "Because all variables outside functions are automatically deleted"
    - "Because global variables can only be numbers"
  answer: 1
  explanation: "PHP gives each function its own local scope by default so that a function's behavior can be understood just from its parameters and return value, without depending on unrelated outside variables."
- question: "What is the recommended way to give a function access to a value from outside it, instead of using the `global` keyword?"
  options:
    - "Rename the outside variable so it matches a variable name inside the function"
    - "Pass the value in as a function parameter, and get results back out using return"
    - "Copy the entire script into the function body"
    - "There is no alternative; global must always be used"
  answer: 1
  explanation: "The proper, safer pattern is to pass needed values into a function as parameters and to send results back out with return, rather than reaching into the global scope with the global keyword."
```
