---
slug: what-is-oop
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "What is Object-Oriented Programming?"
difficulty: intermediate
order: 1
estimatedMinutes: 8
tags: [php, oop, basics]
---

## Learning Objective

Understand what Object-Oriented Programming (OOP) is, why PHP supports it, and how it differs from the procedural style of PHP you have used so far (functions, arrays, loops).

## Explanation

Up to this point in the course, you have written PHP in a "procedural" style: you defined variables, wrote functions that took data in and returned data out, and used arrays to group related values together. For example, you might have represented a user as an associative array like `['name' => 'Alice', 'email' => 'alice@example.com']`, and written separate functions like `sendWelcomeEmail($user)` or `formatUserName($user)` that operated on that array.

Object-Oriented Programming (OOP) is a different way of organizing code. Instead of keeping data (the array) and behavior (the functions) separate, OOP lets you bundle them together into a single unit called an **object**. An object groups related data and the functions that act on that data into one thing.

To create objects, you first write a **class**. A class is a blueprint or template — it describes what data an object of that type will hold and what actions (behavior) it can perform, but it does not itself hold real data. Think of a class like a cookie cutter, and an object like the actual cookie made from it. You can stamp out as many cookies (objects) as you want from the same cutter (class), and each one is a separate, independent object, even though they all have the same shape.

In plain terms:
- A **class** = the definition/blueprint (e.g., `class User { ... }`).
- An **object** = a real instance created from that blueprint (e.g., a specific user named Alice).
- **Properties** = the data an object holds (like variables that belong to the object).
- **Methods** = the functions that belong to the object and operate on its own data.

This is not a completely new language — it is still PHP, still using `if`, `foreach`, `array`, and everything you already know. OOP is simply a different way to structure that same code so that related data and logic live together instead of being scattered across many standalone functions.

## Why It Matters

Real-world PHP applications — including every Symfony application you will build later in this course — are built almost entirely using OOP. Symfony's core building blocks (controllers, services, entities that represent database rows, forms, and more) are all PHP classes. If you do not understand what a class and an object are, you will not be able to read or write a single line of real Symfony code, because nearly everything in the framework is "create an object of this class, then call a method on it."

Beyond Symfony specifically, OOP became popular in professional PHP development because it solves a real, recurring pain: as procedural codebases grow, it becomes hard to know which functions are allowed to touch which arrays, and typos in array keys (like `$user['emial']`) fail silently instead of raising clear errors. OOP gives data a defined shape and keeps the operations on that data close to it, which makes large codebases easier to navigate, test, and maintain.

## Example

Imagine a procedural version of a "Book" versus an object-oriented version.

Procedural: you'd have `$book = ['title' => 'PHP Basics', 'pages' => 200];` and a separate function `isLongBook($book) { return $book['pages'] > 150; }`.

Object-oriented: you define a `Book` class that holds `title` and `pages` as its own properties, and has its own method `isLong()` that checks `pages` internally. Then you create an actual `Book` object and simply call `$book->isLong()` on it.

## Code

```php
<?php

// A minimal class: a blueprint describing what a Book object looks like.
class Book
{
    // Properties: the data each Book object will hold.
    public string $title;
    public int $pages;

    // A method: behavior that belongs to Book objects.
    public function isLong(): bool
    {
        return $this->pages > 150;
    }
}

// Creating an object (an "instance") from the Book class.
$book = new Book();
$book->title = "PHP Basics";
$book->pages = 200;

echo $book->title . " is long? " . ($book->isLong() ? "yes" : "no") . "\n";
```

## Code Explanation

- `class Book { ... }` defines the blueprint. Nothing is created yet — this just describes what a `Book` object will look like.
- `public string $title;` and `public int $pages;` declare **properties**: named pieces of data that every `Book` object will carry around with it.
- `public function isLong(): bool { ... }` defines a **method** — a function that belongs to the class. Inside it, `$this` refers to "the current object the method was called on," so `$this->pages` reads the `pages` property of whichever `Book` object called `isLong()`.
- `new Book()` creates an actual object from the blueprint. This is called **instantiation**, and the resulting object is called an **instance** of `Book`.
- `$book->title = "PHP Basics";` sets a property on that specific object using the `->` (arrow) operator, which is how you access properties and methods on an object.
- `$book->isLong()` calls the method on that object, and because `$this->pages` inside the method refers to `$book`'s own `pages` (200), the result is `true`.

## Common Mistakes

- Confusing a class with an object: writing `Book->title` instead of creating an object first with `new Book()` and then using `$book->title`. The class itself is not a "thing" you can store data in directly.
- Forgetting the `new` keyword when creating an object, e.g. writing `$book = Book();` — this causes a PHP error because `Book()` alone looks like a call to a function named `Book`, which does not exist.
- Assuming OOP replaces everything you already know about PHP. Loops, conditionals, arrays, and functions all still exist and are used constantly inside classes and methods.

## Best Practices

- Give classes singular, capitalized names that describe one clear "thing," such as `Book`, `User`, or `Invoice` — not `Books` or `doStuff`.
- Keep a class focused on one concept. If a `Book` class starts handling email sending or database connections, that is a sign the design needs to be split up (you'll revisit this idea later when we cover the Single Responsibility Principle).
- Read OOP code out loud using "the object's own data and behavior" framing: `$book->isLong()` reads naturally as "book, is it long?" — this habit helps you reason about object-oriented code.

## Exercise

Create a `Movie` class with two properties, `title` (string) and `durationMinutes` (int), and one method `isFeatureLength()` that returns `true` if `durationMinutes` is 40 or more. Then create a `Movie` object, set its properties, and print whether it is feature length.

## Solution

```php
<?php

class Movie
{
    public string $title;
    public int $durationMinutes;

    public function isFeatureLength(): bool
    {
        return $this->durationMinutes >= 40;
    }
}

$movie = new Movie();
$movie->title = "Short Film Festival Reel";
$movie->durationMinutes = 45;

echo $movie->title . " is feature length? " . ($movie->isFeatureLength() ? "yes" : "no") . "\n";
```

The `Movie` class defines the blueprint with two properties. `new Movie()` creates a real object, we set its data using `->`, and `isFeatureLength()` uses `$this->durationMinutes` to check the object's own data, printing "yes" because 45 is greater than or equal to 40.

## Mini Quiz

```yaml
- question: "What is the correct way to describe the relationship between a class and an object?"
  options:
    - "A class and an object are exactly the same thing"
    - "A class is a blueprint; an object is a specific instance created from that blueprint"
    - "An object is the blueprint; a class is created from it"
    - "Classes are only used for storing data, never behavior"
  answer: 1
  explanation: "A class defines the structure (properties and methods) and an object is a concrete instance created from that class using 'new'."

- question: "In the Book example, what does $this refer to inside the isLong() method?"
  options:
    - "The Book class definition itself, not any specific object"
    - "A global variable available everywhere"
    - "The specific object the method was called on"
    - "The PHP interpreter"
  answer: 2
  explanation: "$this always refers to the current object instance on which the method is being called, allowing the method to access that object's own properties."

- question: "Which keyword is used to create an object instance from a class in PHP?"
  options:
    - "create"
    - "instance"
    - "object"
    - "new"
  answer: 3
  explanation: "The 'new' keyword instantiates a class, producing a new object in memory that follows the class's blueprint."
```
