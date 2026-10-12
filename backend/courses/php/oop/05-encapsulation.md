---
slug: encapsulation
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Encapsulation"
difficulty: intermediate
order: 5
estimatedMinutes: 9
tags: [php, oop, encapsulation]
---

## Learning Objective

Understand encapsulation — the practice of hiding an object's internal data behind controlled methods — and learn to write getters, setters, and validating methods that keep an object's state always valid.

## Explanation

You already met `private` and `public` in the last lesson. **Encapsulation** is the *name for the overall practice* of combining those two ideas deliberately: keeping a class's data `private` (hidden from the outside world) and exposing only carefully chosen `public` methods that control exactly how that data can be read or changed. The word comes from "capsule" — the object's data sits inside a protective shell, and the only way in or out is through the methods the class itself provides.

Without encapsulation, any code anywhere in your application could reach into an object and set its properties to anything, including invalid or nonsensical values — a negative age, an empty email address, a discount percentage over 100%. With encapsulation, the class itself is the single place responsible for enforcing its own rules, because it is the only code that can touch its private data directly.

Two common patterns for exposing controlled access are:

- A **getter**: a public method (often named `getSomething()`) that returns the current value of a private property, without allowing it to be changed directly.
- A **setter**: a public method (often named `setSomething($value)`) that updates a private property, but only after checking the new value is valid — rejecting it (by throwing an exception, or simply refusing to apply it) if it isn't.

Not every private property needs both a getter and a setter. Some properties should only ever be readable from outside (a getter with no setter, like a database-generated `id`), and some should only be changeable through a specific, meaningfully-named method rather than a generic setter — for example, a bank account's balance is far better protected by `deposit()` and `withdraw()` methods (each with its own rules) than by a generic `setBalance()` that would let anyone set it to any value, including a negative one.

PHP also provides a way to throw a controlled error when something is invalid: the `throw new InvalidArgumentException("message")` statement. Throwing an exception immediately stops the current method and signals to the calling code that something went wrong, which is far safer than silently accepting bad data. (Exceptions are covered in more depth in the error-handling chapter — for this lesson, treat `throw new InvalidArgumentException(...)` simply as "reject this value and stop.")

## Why It Matters

Encapsulation is what makes large codebases maintainable. If ten different files across your application are all allowed to directly set a `$user->age = -5;`, a bug could originate from any of them, and you would have to hunt through all ten to find it. If instead `age` is `private` and can only be changed through a `setAge()` method that rejects negative numbers, there is exactly one place in the entire codebase where a bad age could ever be introduced — and that one place already refuses to allow it. This dramatically narrows down where bugs can come from and, more importantly, prevents entire categories of bugs from happening at all.

This idea is central to how Symfony's own classes are written, and to how you will be expected to write your own entities and services later in this course: private data, and small, meaningfully-named public methods as the only door in.

## Example

A `UserAccount` class keeps `email` `private` and only allows it to be changed through `changeEmail()`, which rejects any string that doesn't look like a valid email address, rather than allowing `$user->email = "not-an-email";` directly from outside code.

## Code

```php
<?php

class UserAccount
{
    private string $email;

    public function __construct(string $email)
    {
        $this->setEmailInternal($email);
    }

    public function changeEmail(string $newEmail): void
    {
        $this->setEmailInternal($newEmail);
    }

    private function setEmailInternal(string $email): void
    {
        if (!str_contains($email, '@')) {
            throw new InvalidArgumentException("'{$email}' is not a valid email address.");
        }

        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

$user = new UserAccount("alice@example.com");
echo "Email: " . $user->getEmail() . "\n";

$user->changeEmail("alice.new@example.com");
echo "Updated email: " . $user->getEmail() . "\n";

try {
    $user->changeEmail("not-an-email");
} catch (InvalidArgumentException $e) {
    echo "Rejected: " . $e->getMessage() . "\n";
}
```

## Code Explanation

- `private string $email;` hides the property completely from outside code — there is no `$user->email` access possible anywhere outside this class.
- `setEmailInternal()` is a `private` helper method containing the validation rule in exactly one place; both the constructor and `changeEmail()` call it, so the rule can never be bypassed or accidentally duplicated inconsistently.
- The constructor calls `$this->setEmailInternal($email);` instead of assigning `$this->email` directly, ensuring even the very first value is validated — an object can never be constructed with a bad email.
- `changeEmail()` is the only `public` way to change the email after creation, and it funnels through the same validation as the constructor.
- `if (!str_contains($email, '@')) { throw new InvalidArgumentException(...); }` rejects invalid input immediately by throwing an exception, which stops execution of that method and must be handled by the calling code.
- The `try { ... } catch (InvalidArgumentException $e) { ... }` block around the last call demonstrates handling that thrown exception gracefully, printing a rejection message instead of crashing the whole script.
- `getEmail()` is a simple getter: it lets outside code *read* the email, but gives no way to set it directly, keeping the "only through validated methods" rule airtight in both directions.

## Common Mistakes

- Adding a generic `setEmail()` (or worse, making `email` `public`) that accepts anything without validation — this defeats the entire purpose of encapsulation, because it reopens the door that was supposed to be closed.
- Duplicating validation logic separately inside the constructor and inside a setter, instead of extracting it into one shared private method — this risks the two copies drifting apart over time as the class evolves.
- Forgetting to validate in the constructor and only validating in a later setter method, which allows an invalid object to exist right after creation, before any setter is ever called.
- Catching exceptions too broadly or not at all, silently swallowing real problems instead of handling the specific exception type meaningfully.

## Best Practices

- Make properties `private` by default, and only add a getter/setter when outside code genuinely needs that access — do not add them reflexively for every property.
- Give setters and validating methods specific, meaningful names (`deposit()`, `changeEmail()`, `applyDiscount()`) rather than generic `set___()` names whenever the operation has real business rules attached to it.
- Centralize validation logic in one private method that every entry point (constructor and any public setter) calls, so the rule is defined exactly once.
- Throw a clear, specific exception with a helpful message when rejecting invalid data, so the calling code (and future you, debugging) understands exactly what went wrong.

## Exercise

Create a `Temperature` class that stores a Celsius value as a `private float $celsius`. Its constructor and a `setCelsius(float $value): void` method should both reject (via `InvalidArgumentException`) any value below -273.15 (absolute zero), routing through one shared private validation method. Add a `getCelsius(): float` getter. Demonstrate creating a valid `Temperature`, calling `setCelsius()` with a valid value, and catching a rejected invalid value.

## Solution

```php
<?php

class Temperature
{
    private float $celsius;

    public function __construct(float $celsius)
    {
        $this->assignCelsius($celsius);
    }

    public function setCelsius(float $value): void
    {
        $this->assignCelsius($value);
    }

    private function assignCelsius(float $value): void
    {
        if ($value < -273.15) {
            throw new InvalidArgumentException("Temperature cannot be below absolute zero (-273.15°C).");
        }

        $this->celsius = $value;
    }

    public function getCelsius(): float
    {
        return $this->celsius;
    }
}

$temp = new Temperature(20.0);
echo "Initial: " . $temp->getCelsius() . "°C\n";

$temp->setCelsius(-40.0);
echo "Updated: " . $temp->getCelsius() . "°C\n";

try {
    $temp->setCelsius(-300.0);
} catch (InvalidArgumentException $e) {
    echo "Rejected: " . $e->getMessage() . "\n";
}
```

Both the constructor and `setCelsius()` route through the shared private `assignCelsius()` method, so the "no value below absolute zero" rule is enforced consistently everywhere a `Temperature`'s value could change, and the invalid `-300.0` attempt is caught and reported instead of silently corrupting the object.

## Mini Quiz

```yaml
- question: "What is the core idea of encapsulation?"
  options:
    - "Making every property and method public for convenience"
    - "Hiding an object's internal data and exposing only controlled public methods to read or change it"
    - "Using inheritance to share code between classes"
    - "Writing all code in a single large function"
  answer: 1
  explanation: "Encapsulation means keeping data private and exposing only deliberate, controlled public methods (getters, setters, validating methods) as the sole way to interact with it."

- question: "In the UserAccount example, why do both the constructor and changeEmail() call the private setEmailInternal() method instead of duplicating the validation?"
  options:
    - "Because private methods run faster than public ones"
    - "So the validation rule exists in exactly one place and can never be bypassed or drift out of sync between the two entry points"
    - "Because PHP requires constructors to call a private method"
    - "It has no real benefit; it's just a stylistic choice"
  answer: 1
  explanation: "Centralizing validation in one shared private method guarantees consistency: every way of setting the email is forced through the same rule, with no chance of the constructor and setter enforcing different logic."

- question: "Why is it usually better to expose a specific method like deposit() rather than a generic setBalance() on a BankAccount-style class?"
  options:
    - "Generic setters are always slower to execute"
    - "A specific method like deposit() can enforce meaningful rules (e.g. amount must be positive), while a generic setter would let anyone set the balance to anything"
    - "PHP does not allow generic setter method names"
    - "There is no real difference between the two approaches"
  answer: 1
  explanation: "A meaningfully-named method can encode business rules specific to that operation, whereas a generic setter tends to accept arbitrary values with no real validation, undermining encapsulation."
```
