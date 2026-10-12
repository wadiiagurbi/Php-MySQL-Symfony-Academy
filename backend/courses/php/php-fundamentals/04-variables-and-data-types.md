---
slug: variables-and-data-types
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Variables and Data Types"
difficulty: beginner
order: 4
estimatedMinutes: 12
tags: [php, variables, data-types]
---

## Learning Objective

By the end of this lesson, you will understand what a variable is, how to correctly
name variables in PHP, and what PHP's basic data types are (string, integer, float,
boolean, and null).

## Explanation

A **variable** is a named container for storing a piece of information (a "value")
that your program can use and change later. Think of it like a labeled box: you write
a label on the box (the variable's name) so you can find it again, and you put
something inside it (the value). Later, you can look inside the box, use what's there,
or replace it with something else.

You already saw a variable in the very first lesson of this course:

```php
$visitorName = "Sarah";
```

Here, `visitorName` is the label on the box, and `"Sarah"` is what got placed inside
it. In PHP, every variable name must start with a **dollar sign** (`$`). This is not
optional decoration — it's how PHP recognizes that a word is a variable rather than,
say, a function name or a keyword.

PHP has a few rules for what counts as a valid variable name after the `$`:

- It must start with a letter or an underscore (`_`) — never with a number.
- After that first character, it can contain letters, numbers, and underscores.
- It cannot contain spaces or symbols like `-`, `!`, or `@`.
- PHP variable names are **case-sensitive**, meaning `$age` and `$Age` are treated as
  two completely different variables, even though they look almost identical.

So `$age`, `$user_name`, and `$total2` are all valid names. But `$2total`, `$user
name`, and `$user-name` are not valid.

Now, what kind of things can you actually put inside a variable? Every value in PHP
has a **data type** — a category describing what kind of information it represents
and what you're allowed to do with it. PHP has several basic ("scalar," meaning a
single simple value rather than a collection of many values) data types:

- **string**: text, written between quotes, like `"Hello"` or `'Paris'`.
- **integer** (often called **int**): a whole number, with no decimal point, like
  `42` or `-7`.
- **float** (short for "floating-point number"): a number that includes a decimal
  point, like `3.14` or `0.5`.
- **boolean** (often called **bool**): one of exactly two values, `true` or `false`,
  used to represent yes/no or on/off type answers.
- **null**: a special value meaning "no value at all" or "intentionally empty." It is
  written simply as `null`.

There is also a data type called **array**, which can hold many values grouped
together under a single variable name. Arrays are powerful and common in real PHP
code, but they have their own set of rules and syntax, so this course covers them in
detail in a later lesson — for now, just know the name exists as one of PHP's data
types.

An important trait of PHP is that it is **loosely typed** (also called **dynamically
typed**). This means you do not have to declare, in advance, what type of value a
variable will hold — you simply assign a value, and PHP figures out its type
automatically, on the fly. You can even later assign a completely different type of
value to the same variable name, and PHP will not complain. Some other programming
languages require you to state a variable's type up front and never change it — PHP
does not work that way.

You can inspect a value's type yourself using two useful built-in tools: `gettype()`,
which returns the type's name as a string (like `"integer"`), and `var_dump()`, which
prints both the type and the value together, which is very handy while learning or
debugging.

## Why It Matters

Variables are the basic building block of almost every useful program. Without a way
to store information — a user's name, a product's price, whether someone is logged
in — a program could only ever work with values typed directly into the code, which
would make it useless for handling real, changing data.

Understanding data types matters just as much, because different types behave
differently. For example, adding two integers together (`2 + 2`) gives you a number,
but "adding" two strings together doesn't work the same way — you saw in the first
lesson that joining text uses `.` (concatenation) rather than `+`. Later, when you
build real applications (reading form input, storing prices, checking whether a user
is an administrator), knowing exactly what type of data you're working with will save
you from a huge number of confusing bugs, since PHP will sometimes convert values
between types automatically in ways that can surprise you if you don't understand the
underlying types.

## Example

Imagine you're storing basic details about a customer: their name, their age, their
account balance, and whether their email address has been verified. Each of these
pieces of information naturally fits a different PHP data type.

## Code

```php
<?php

$customerName = "Amara Okafor";
$customerAge = 29;
$accountBalance = 128.50;
$emailVerified = true;
$referralCode = null;

echo $customerName;
echo "\n";
echo gettype($customerAge);
echo "\n";
var_dump($accountBalance);
var_dump($emailVerified);
var_dump($referralCode);
```

## Code Explanation

- `$customerName = "Amara Okafor";` stores a string — text wrapped in quotes — inside
  the variable `customerName`.
- `$customerAge = 29;` stores an integer. Because `29` has no decimal point and no
  quotes around it, PHP treats it as a whole number, not text.
- `$accountBalance = 128.50;` stores a float, because the value contains a decimal
  point.
- `$emailVerified = true;` stores a boolean. Note that `true` is written without
  quotes — if you wrote `"true"` with quotes, that would be a string containing the
  four letters t-r-u-e, not the actual boolean value.
- `$referralCode = null;` stores null, representing "this customer has no referral
  code at all," which is different from an empty string `""` — null means "nothing is
  here," while an empty string means "there is a value, and it happens to be empty
  text."
- `echo $customerName;` prints the string directly: `Amara Okafor`.
- `echo gettype($customerAge);` asks PHP for the type name of `$customerAge` as plain
  text and prints it: `integer`.
- `var_dump($accountBalance);` prints both the type and the value together, producing
  output like `float(128.5)`.
- `var_dump($emailVerified);` produces `bool(true)`.
- `var_dump($referralCode);` produces `NULL`.

Running this script would print something like:

```text
Amara Okafor
integer
float(128.5)
bool(true)
NULL
```

## Common Mistakes

- **Forgetting the `$` sign.** Writing `customerName = "Amara";` instead of
  `$customerName = "Amara";` will cause a PHP error, because without the `$`, PHP
  doesn't recognize `customerName` as a variable at all.
- **Mixing up variable case.** Since `$Total` and `$total` are different variables to
  PHP, accidentally using inconsistent capitalization is a very common source of "why
  is my variable empty?" bugs for beginners. Pick a consistent naming style and stick
  to it.
- **Confusing the string `"true"` with the boolean `true`.** Putting quotes around
  `true` or `false` turns them into ordinary text, losing the special boolean
  behavior. Boolean literals are written without quotes.
- **Thinking null and an empty string are the same thing.** They are conceptually
  different: null means "no value was ever set," while `""` means "a value was set,
  and it's text with zero characters." Many real bugs come from treating these as
  interchangeable.

## Best Practices

- Use descriptive variable names that explain what they store, like `$customerAge`
  rather than `$x` or `$a`. Code is read far more often than it is written, and future
  you (or a teammate) will thank you.
- Use `camelCase` for multi-word variable names (like `$customerName`), where the
  first word is lowercase and each following word starts with a capital letter. This
  is a common convention in PHP and matches what you'll see throughout this course.
- When you're unsure what type a value has, especially while learning, use
  `var_dump()` to check. It's a fast, reliable way to confirm your assumptions instead
  of guessing.

## Exercise

Create a script that stores four pieces of information about a book: its title
(string), its number of pages (integer), its price (float), and whether it is
currently in stock (boolean). Print each variable's type using `gettype()`.

## Solution

```php
<?php

$bookTitle = "The Pragmatic Programmer";
$pageCount = 352;
$price = 24.99;
$inStock = true;

echo gettype($bookTitle);
echo "\n";
echo gettype($pageCount);
echo "\n";
echo gettype($price);
echo "\n";
echo gettype($inStock);
```

Running this script prints:

```text
string
integer
double
boolean
```

Each `gettype()` call returns the name of the type stored in that variable. Note that
PHP internally calls the float type `"double"` (short for "double-precision floating
point number") when `gettype()` reports it — this is simply PHP's internal name for
the same float type described earlier in this lesson.

## Mini Quiz

```yaml
- question: "What symbol must every PHP variable name start with?"
  options:
    - "#"
    - "@"
    - "$"
    - "&"
  answer: 2
  explanation: "PHP requires every variable name to begin with a dollar sign ($), which is how PHP recognizes it as a variable."
- question: "Are $userName and $username the same variable in PHP?"
  options:
    - "Yes, PHP ignores letter case in variable names"
    - "No, PHP variable names are case-sensitive, so they are two different variables"
    - "Yes, but only inside functions"
    - "It depends on the PHP version"
  answer: 1
  explanation: "PHP variable names are case-sensitive, so $userName and $username are treated as two completely separate variables."
- question: "Which value would PHP consider a float?"
  options:
    - "42"
    - "\"42\""
    - "true"
    - "42.5"
  answer: 3
  explanation: "A float is a number that includes a decimal point, like 42.5. The value 42 without a decimal point is an integer, and \"42\" in quotes is a string."
- question: "What does it mean to say PHP is 'loosely typed'?"
  options:
    - "You must declare a variable's type before using it, and it can never change"
    - "Variables can hold values of any type, and you don't have to declare the type in advance"
    - "PHP does not support data types at all"
    - "Only strings can be stored in PHP variables"
  answer: 1
  explanation: "Loosely typed (dynamically typed) means PHP determines a variable's type automatically based on the value assigned, without requiring an upfront type declaration, and the type can change if a new value is assigned later."
```
