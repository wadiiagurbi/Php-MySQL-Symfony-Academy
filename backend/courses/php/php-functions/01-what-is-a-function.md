---
slug: what-is-a-function
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-functions
chapterTitle: "Chapter 2 — PHP Functions"
chapterOrder: 2
title: "What is a Function?"
difficulty: beginner
order: 1
estimatedMinutes: 8
tags: [php, functions, basics]
---

## Learning Objective

By the end of this lesson, you will understand what a function is, why programmers use them, and how to define and call your own simple function in PHP.

## Explanation

So far in this course, every piece of code you have written runs from top to bottom, line by line. If you wanted to print a greeting five times, you would have had to write the same `echo` line five times, or use a loop. But what if you wanted to print that same greeting in many different places throughout a large program, possibly hundreds of lines apart? Copying and pasting the same lines over and over quickly becomes messy and hard to maintain.

This is the problem that **functions** solve. A function is a named, reusable block of code that performs a specific task. You write the code for that task once, give it a name, and then you can run ("use") that block of code as many times as you like, from anywhere in your program, just by referring to its name.

There are two separate actions involved when working with a function:

- **Defining** a function means writing out the block of code and giving it a name. This is where you describe *what* the function does. Defining a function does not run it — it just teaches PHP that this block of code exists and can be used later.
- **Calling** a function means actually telling PHP "run that block of code now." You call a function by writing its name followed by parentheses `()`.

In PHP, you define a function using the `function` keyword, followed by a name you choose, followed by parentheses, followed by a pair of curly braces `{ }` that contain the code the function will run:

```php
function sayHello() {
    echo "Hello there!";
}
```

This code by itself does not print anything. It only defines the function. To actually see "Hello there!" printed, you need to call the function elsewhere in your code:

```php
sayHello();
```

Notice the semicolon at the end of the call, just like any other PHP statement. Also notice that both the definition and the call use parentheses `()` — for now our function does not need anything inside those parentheses, but later lessons will show you how to put information inside them.

## Why It Matters

Functions exist because real-world programs quickly grow to thousands or even millions of lines of code, written and maintained by many different people over time. Without functions, any time a task needed to happen in multiple places, you would have to duplicate that code everywhere it was needed. This causes two serious problems: first, if you later find a bug in that logic, you would have to fix it in every single copy, and it is easy to miss one. Second, large blocks of repeated, unnamed code make it very hard for anyone (including your future self) to understand what the program is doing.

Functions fix both problems. Because the logic lives in exactly one place, fixing a bug means fixing it once. And because a function has a descriptive name, reading a call like `sendWelcomeEmail()` or `calculateShippingCost()` tells you immediately what is happening, without needing to read every line of implementation detail. This is called **abstraction**: hiding the messy details behind a simple, readable name. Every real PHP application — including ones built with Symfony, which this course will get to later — is organized almost entirely out of functions (and, as you'll learn much later, similar building blocks called methods).

## Example

Imagine you are building a simple script that needs to print a decorative divider line between sections of text several times. Instead of repeating the same `echo` statement everywhere, you can define one function for it and call it whenever you need a divider.

## Code

```php
<?php

// Defining the function: this describes what the function does,
// but does not run it yet.
function printDivider() {
    echo "----------------------------\n";
}

// Now we call (run) the function wherever we need a divider.
echo "Section 1: Introduction\n";
printDivider();

echo "Section 2: Main Content\n";
printDivider();

echo "Section 3: Conclusion\n";
```

## Code Explanation

- `function printDivider() { ... }` defines a new function named `printDivider`. The empty parentheses `()` mean this function does not accept any input values (we will learn about that in the next lesson). Everything between the curly braces `{` and `}` is the body of the function — the code that runs each time the function is called.
- Inside the body, `echo "----------------------------\n";` prints a row of dashes followed by a newline character (`\n`), which moves the following output to a new line.
- The line `echo "Section 1: Introduction\n";` is regular code that runs immediately, in order, just like you learned in Chapter 1.
- `printDivider();` is a **function call**. When PHP reaches this line, it jumps into the `printDivider` function, runs its body (printing the dashes), and then comes back to continue running the next line of the main script.
- Notice that `printDivider();` is written twice, but the function itself was only defined once. That single definition is being reused twice — this is the entire point of functions.

## Common Mistakes

- **Forgetting the parentheses when calling a function.** Writing `sayHello;` instead of `sayHello();` does not call the function — PHP will treat `sayHello` as if you meant something else entirely (a constant name), and it will not run the function's code.
- **Trying to call a function before it is defined "in a way that doesn't exist yet."** In PHP, as long as the function definition exists somewhere in a file that has been loaded before the call happens, order usually doesn't matter for simple scripts. However, it is still good practice, especially as a beginner, to define your functions before you call them, so the flow of the file reads top to bottom in a logical way.
- **Confusing "defining" with "running."** Beginners sometimes expect that simply writing a `function ... { }` block causes it to execute immediately. It does not. The code inside a function only runs when the function is called.
- **Using unclear or vague function names**, like `doStuff()` or `thing1()`. This defeats the main benefit of functions, which is making code easier to read and understand.

## Best Practices

- Give functions clear, descriptive names that explain what they do, usually as a verb phrase, such as `printDivider`, `calculateTotal`, or `sendEmail`.
- In PHP, the common convention is to name functions using `camelCase`: the first word is lowercase, and each following word starts with a capital letter, with no spaces or underscores (for example `printDivider`, not `print_divider` or `PrintDivider`). You will see both styles in real PHP code, but camelCase is the standard used throughout the Symfony ecosystem that this course is building toward.
- Keep each function focused on doing one clear task. If a function's name needs the word "and" to describe it (like `printDividerAndLogMessage`), it is usually a sign it should be split into two functions.
- Define your functions before you use them in the file whenever practical, so someone reading top to bottom can follow the logic naturally.

## Exercise

Write a PHP script that defines a function called `printStars` which prints a line of ten asterisk characters (`**********`) followed by a newline. Then call that function three times, with a different `echo` statement printing a short message between each call (for example, "Before stars", "Middle message", "After stars").

## Solution

```php
<?php

function printStars() {
    echo "**********\n";
}

echo "Before stars\n";
printStars();

echo "Middle message\n";
printStars();

echo "After stars\n";
printStars();
```

This works because `printStars` is defined once, at the top, describing exactly what should happen (print ten asterisks and a newline). Each time we write `printStars();`, PHP jumps into that function's body, runs the `echo` statement inside it, and then returns to continue with the next line in the main script. Because the function was reused three times instead of copying the `echo "**********\n";` line three separate times, if we ever wanted to change the divider style (for example, to use dashes instead of stars), we would only need to change it in one place.

## Mini Quiz

```yaml
- question: "What does 'calling' a function mean?"
  options:
    - "Writing the function's code for the first time"
    - "Telling PHP to actually run the function's code"
    - "Deleting a function you no longer need"
    - "Giving a function a name"
  answer: 1
  explanation: "Calling a function means telling PHP to execute the block of code inside that function right now. Defining is writing the code; calling is running it."
- question: "What is the main benefit of using a function instead of copying and pasting the same code everywhere it's needed?"
  options:
    - "Functions make the program run without needing a computer"
    - "Functions automatically fix all bugs in your program"
    - "Functions let you reuse code from one place and give it a clear name, avoiding duplication"
    - "Functions make PHP files smaller in file size only"
  answer: 2
  explanation: "Functions let you write logic once, reuse it anywhere by name, and fix bugs in a single place instead of many duplicated copies."
- question: "In `function printDivider() { echo \"---\\n\"; }`, what happens if this code runs but `printDivider();` is never written anywhere else?"
  options:
    - "PHP throws an error because the function was never called"
    - "The dashes are printed once automatically when the file loads"
    - "Nothing is printed, because defining a function does not run it"
    - "PHP prints the function's name instead of running it"
  answer: 2
  explanation: "Defining a function only teaches PHP that the function exists. Its body only runs when the function is actually called somewhere."
```
