---
slug: constructors-and-visibility
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Constructors and Visibility"
difficulty: intermediate
order: 4
estimatedMinutes: 10
tags: [php, oop, constructors, visibility]
---

## Learning Objective

Learn how constructors let you require essential data at object-creation time, and how the `public`, `private`, and `protected` visibility keywords control where properties and methods can be accessed from.

## Explanation

In every example so far, you created an object with `new ClassName()` and then set its properties one by one afterward, like `$car->color = "red";`. This works, but it has a real weakness: nothing forces you to actually set every property. It is easy to create an object and forget to set a required piece of data, leaving it in an incomplete or invalid state for the rest of the program to trip over later.

A **constructor** solves this. It is a special method named `__construct()` that PHP runs automatically, immediately, every time you create a new object with `new`. Whatever parameters you declare on `__construct()` become required arguments you must pass to `new`, so the object can never exist without that data:

```php
public function __construct(string $name, float $balance)
{
    $this->name = $name;
    $this->balance = $balance;
}
```

Now `new BankAccount("Alice", 100.0)` is the only way to create the object, and the essential data is guaranteed to be set from the very first moment the object exists.

PHP 8 offers a shortcut called **constructor property promotion** that lets you declare a property and assign it from a constructor parameter in one line, by adding a visibility keyword directly to the parameter:

```php
public function __construct(private string $name, private float $balance) {}
```

This single line declares two properties (`$name` and `$balance`) AND assigns the constructor's arguments to them — you don't need to separately declare the properties above the constructor or write `$this->name = $name;` by hand.

Notice the word `private` in that example. This is a **visibility** keyword, and it's the second big topic of this lesson. Every property and method has a visibility level that controls where it can be accessed from:

- `public` — accessible from anywhere: inside the class, and from any code outside the class.
- `private` — accessible only from inside the same class. Code outside the class cannot read or write it directly at all.
- `protected` — accessible from inside the class, and also from any class that inherits from it (called a "subclass" — you'll learn exactly what that means in the inheritance lesson later in this chapter; for now, just know `protected` is a middle ground between `public` and `private`).

If a property is `private`, code outside the class that tries to access it directly, like `$account->balance`, causes a fatal error. To let outside code read that value safely, you write a small `public` method — often called a "getter" — that returns it, such as `public function getBalance(): float { return $this->balance; }`.

## Why It Matters

Constructors and visibility are how professional PHP code avoids two very common bugs: objects that exist in an invalid, half-set-up state, and code far away in the application reaching in and corrupting an object's internal data by mistake. Nearly every class in a real Symfony application uses a constructor to require its essential dependencies and data upfront — if a class needs a database connection or a specific configuration value to function, its constructor demands it, so it is structurally impossible to end up with a broken half-constructed object floating around your application. Visibility keywords are what make this possible: by marking internal details `private`, you guarantee that the ONLY way to interact with an object's sensitive data is through the methods you deliberately chose to expose, not through direct, uncontrolled property access from anywhere in a large codebase.

## Example

An `Employee` class requires a name and a starting salary at creation time via its constructor, keeps both as private properties, and exposes them safely through public getter methods.

## Code

```php
<?php

class Employee
{
    private string $name;
    private float $salary;

    public function __construct(string $name, float $salary)
    {
        $this->name = $name;
        $this->salary = $salary;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSalary(): float
    {
        return $this->salary;
    }
}

$employee = new Employee("Priya Sharma", 52000.0);

echo $employee->getName() . " earns $" . number_format($employee->getSalary(), 2) . "\n";

// The next line would cause a fatal error if uncommented, because
// $name and $salary are private and cannot be accessed from outside the class:
// echo $employee->name;
```

## Code Explanation

- `private string $name;` and `private float $salary;` declare both properties as private, meaning only code inside the `Employee` class itself can read or write them directly.
- `__construct(string $name, float $salary)` requires both values to be supplied the moment an `Employee` object is created — there is no way to end up with an `Employee` missing a name or salary.
- Inside the constructor, `$this->name = $name;` assigns the constructor's parameter (a local variable available only inside this method) to the object's own `$name` property, distinguishing "the value that was passed in" from "the value now permanently stored on this object."
- `getName()` and `getSalary()` are small `public` methods that expose read-only access to the otherwise-private data — this is the only sanctioned way for outside code to retrieve those values.
- The commented-out line `$employee->name` demonstrates what would happen without a getter: attempting to access a `private` property from outside its class is a fatal, program-stopping error in PHP.

## Common Mistakes

- Forgetting that constructor parameters and object properties are technically different things that happen to share a name; writing `$this->name = $name;` is required to actually store the incoming value on the object — simply naming the parameter `$name` does not automatically create or fill a property.
- Marking everything `public` out of convenience "to make it easier," which defeats the entire purpose of visibility and reintroduces the risk of any code anywhere corrupting the object's data directly.
- Trying to access a `private` property from outside the class and being confused by the resulting fatal error — remember that the fix is to add a small `public` getter method, not to change the property back to `public`.

## Best Practices

- Default to `private` for properties, and only make something `public` when you have a deliberate reason for outside code to touch it directly.
- Use the constructor to require every piece of data an object cannot function without — if an object would be meaningless or broken without a certain value, that value belongs in the constructor's parameter list, not set optionally afterward.
- Prefer constructor property promotion (`private string $name` directly in the parameter list) for simple cases to reduce repetitive boilerplate, but fall back to writing the property declaration and assignment separately whenever the constructor needs extra logic beyond a plain assignment.

## Exercise

Create a `Product` class with private properties `name` (string) and `priceCents` (int, storing price in cents to avoid floating-point rounding issues). Give it a constructor requiring both values. Add public getters `getName(): string` and `getPriceCents(): int`, and a public method `getPriceFormatted(): string` that returns the price divided by 100 and formatted like `"$19.99"`.

## Solution

```php
<?php

class Product
{
    private string $name;
    private int $priceCents;

    public function __construct(string $name, int $priceCents)
    {
        $this->name = $name;
        $this->priceCents = $priceCents;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function getPriceFormatted(): string
    {
        return "$" . number_format($this->priceCents / 100, 2);
    }
}

$product = new Product("Wireless Mouse", 1999);

echo $product->getName() . ": " . $product->getPriceFormatted() . "\n"; // Wireless Mouse: $19.99
```

The constructor requires both `name` and `priceCents` up front, so a `Product` can never exist without valid pricing data. Both properties stay `private`, and `getPriceFormatted()` is a public method that safely derives a display-friendly string from the private `priceCents` value without exposing the raw property itself.

## Mini Quiz

```yaml
- question: "What is the main problem that a constructor solves compared to setting properties one by one after `new`?"
  options:
    - "Constructors make objects load faster"
    - "Constructors let you skip declaring properties entirely"
    - "Constructors guarantee required data is provided the moment the object is created, preventing incomplete objects"
    - "Constructors are only needed for private properties"
  answer: 2
  explanation: "Because constructor parameters are required arguments to 'new', an object cannot be created at all without supplying that essential data, eliminating the risk of a half-set-up object."

- question: "If a property is declared `private`, how can code outside the class read its value?"
  options:
    - "It cannot ever be read from outside the class under any circumstances"
    - "By accessing it directly with the arrow operator, the same as a public property"
    - "By calling a public method (commonly a getter) defined inside the class that returns the value"
    - "By redeclaring the property as public in a different file"
  answer: 2
  explanation: "Private properties are only directly accessible from inside their own class. The standard way to expose their value to outside code is through a public method, like a getter, that returns it."

- question: "What does `protected` visibility mean, at the level introduced in this lesson?"
  options:
    - "Accessible from anywhere in the application, just like public"
    - "Never accessible from anywhere, even inside the class"
    - "A middle ground: accessible from inside the class and from classes that inherit from it"
    - "Only used for constants, never for properties or methods"
  answer: 2
  explanation: "protected sits between public and private: it is accessible inside the defining class and inside any subclass that inherits from it, a topic covered fully in the later inheritance lesson."
```
