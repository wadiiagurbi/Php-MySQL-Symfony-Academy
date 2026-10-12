---
slug: properties-and-methods
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Properties and Methods"
difficulty: intermediate
order: 3
estimatedMinutes: 9
tags: [php, oop, properties, methods]
---

## Learning Objective

Understand properties and methods in depth: how to type them, give them default values, use `$this` correctly inside methods, and let one method call another method on the same object.

## Explanation

A **property** is a variable that belongs to an object rather than existing on its own. You declare one inside a class body by writing a visibility keyword (for now, use `public` — full visibility rules come in the next lesson), an optional type, the property name starting with `$`, and an optional default value: `public int $stock = 0;`. Every object created from that class gets its own independent copy of `$stock`, starting at `0` unless changed.

A **method** is a function that belongs to an object. You declare one exactly like a regular function, but inside the class body: `public function restock(int $amount): void { ... }`. What makes it a *method* rather than a plain function is that, inside its body, it has access to a special variable called `$this`, which always refers to "the object this method is currently running on." Using `$this->stock`, a method can read or change the property that belongs to that specific object.

Methods can also call *other* methods on the same object, again through `$this`. For instance, a method `isAvailable()` might call `$this->getStock()` internally rather than duplicating logic. This lets you build small methods and combine them, instead of writing one giant method that does everything.

It's worth being precise about types here, since PHP allows (but does not require) declaring types for properties, method parameters, and return values. `public int $stock = 0;` means PHP will enforce that `$stock` always holds an integer; assigning a string to it (unless it is a numeric string PHP can coercively convert) triggers a `TypeError`. Declaring types is optional in PHP but strongly recommended, because it catches mistakes early and makes the class's contract clear to anyone reading it — this matters even more once you reach Symfony, where typed properties are the norm throughout the framework.

## Why It Matters

Real classes are rarely just data containers — their methods encode the *rules* of how that data is allowed to change. A `BankAccount` class's `withdraw()` method, for example, can refuse to let the balance go negative, something a plain array could never enforce on its own. This is one of the biggest practical differences between procedural arrays and OOP: with a class, the object itself is responsible for keeping its own data valid, instead of relying on every piece of code that touches the array to remember the rules.

Being fluent with properties, `$this`, and methods calling other methods is also essential for reading Symfony's source code and any serious PHP library, where classes routinely have a dozen small methods, each calling a few others, rather than one giant block of logic.

## Example

An `Inventory` class tracks a `stock` property and exposes methods like `restock()`, `sell()`, and `isAvailable()`, where `sell()` internally checks `isAvailable()` before reducing the stock — one method relying on another, both operating on the same object's data.

## Code

```php
<?php

class Inventory
{
    public int $stock = 0;

    public function restock(int $amount): void
    {
        $this->stock += $amount;
    }

    public function isAvailable(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    public function sell(int $quantity): bool
    {
        if (!$this->isAvailable($quantity)) {
            return false; // Not enough stock; refuse the sale.
        }

        $this->stock -= $quantity;
        return true;
    }
}

$inventory = new Inventory();
$inventory->restock(10);

$sold = $inventory->sell(4);
echo "Sale of 4 succeeded? " . ($sold ? "yes" : "no") . "\n"; // yes
echo "Remaining stock: " . $inventory->stock . "\n"; // 6

$sold = $inventory->sell(100);
echo "Sale of 100 succeeded? " . ($sold ? "yes" : "no") . "\n"; // no
echo "Remaining stock: " . $inventory->stock . "\n"; // still 6
```

## Code Explanation

- `public int $stock = 0;` declares a typed property with a default value, so every new `Inventory` object starts with zero stock unless told otherwise.
- `restock(int $amount): void` modifies `$this->stock` directly. It returns `void` because it doesn't need to hand back any value — its job is only to change the object's state.
- `isAvailable(int $quantity): bool` is a small, focused method that answers one question using the object's own `$stock`. Notice it does not change anything; it only reads.
- `sell(int $quantity): bool` calls `$this->isAvailable($quantity)` — one method calling another on the *same* object. This avoids repeating the "is there enough stock" comparison logic inside `sell()` itself.
- Because `sell(100)` finds `isAvailable(100)` returns `false` (only 6 remain), it returns `false` immediately without touching `$this->stock`, which is why the stock stays at 6 rather than going negative.
- Each `$inventory->...` call operates on the single `$inventory` object created earlier; `$this` inside every method refers to that same object throughout.

## Common Mistakes

- Forgetting `$this->` when reading or writing a property inside a method, e.g. writing `stock += $amount;` instead of `$this->stock += $amount;`. PHP treats the property name without `$this->` as an undefined variable, not the object's property.
- Writing methods that both do too much and don't call each other — duplicating the "enough stock?" check inside multiple methods instead of extracting it into `isAvailable()` once and reusing it.
- Mismatching declared types and actual values, such as declaring `public int $stock` but later assigning `$inventory->stock = "lots";`, which throws a `TypeError` at runtime.
- Confusing a method call with a property access, e.g. writing `$inventory->isAvailable` (missing parentheses) — this does not call the method, and PHP will complain that no such property exists.

## Best Practices

- Keep each method focused on one clear question or action ("is this available?", "reduce the stock") rather than one large method that mixes several responsibilities.
- Let higher-level methods call smaller, focused methods (as `sell()` calls `isAvailable()`) instead of duplicating logic — this is a small preview of good object design you'll build on throughout this chapter.
- Always declare types on properties and method parameters/return values when you know them; it documents intent and lets PHP catch mistakes for you immediately rather than producing confusing bugs later.

## Exercise

Create a `Thermostat` class with a typed property `int $temperature` defaulting to `20`. Add a method `increase(int $degrees): void` that raises the temperature, a method `decrease(int $degrees): void` that lowers it but never below `0`, and a method `describe(): string` that returns a string like "Current temperature: 20°C". `decrease()` should call a helper method `clampToMinimum()` that ensures the temperature does not go below zero.

## Solution

```php
<?php

class Thermostat
{
    public int $temperature = 20;

    public function increase(int $degrees): void
    {
        $this->temperature += $degrees;
    }

    public function decrease(int $degrees): void
    {
        $this->temperature -= $degrees;
        $this->clampToMinimum();
    }

    private function clampToMinimum(): void
    {
        if ($this->temperature < 0) {
            $this->temperature = 0;
        }
    }

    public function describe(): string
    {
        return "Current temperature: {$this->temperature}°C";
    }
}

$thermostat = new Thermostat();
$thermostat->decrease(30); // Would go to -10, but clamped to 0.

echo $thermostat->describe() . "\n"; // Current temperature: 0°C
```

`decrease()` first lowers `$this->temperature`, then calls `$this->clampToMinimum()` — a smaller helper method operating on the same object — to enforce the "never below zero" rule. Because 20 - 30 = -10, `clampToMinimum()` resets the value to `0`, and `describe()` correctly reports the clamped result.

## Mini Quiz

```yaml
- question: "Inside a method, what does $this refer to?"
  options:
    - "The class definition itself"
    - "The specific object the method is currently running on"
    - "A global variable shared by all objects"
    - "The parent class, if one exists"
  answer: 1
  explanation: "$this is a special variable inside every non-static method that refers to the current object instance on which that method was called."

- question: "In the Inventory example, why does sell(100) leave $inventory->stock unchanged at 6?"
  options:
    - "Because sell() always returns false without checking anything"
    - "Because isAvailable(100) returns false (not enough stock), so sell() returns early before modifying $this->stock"
    - "Because restock() automatically undoes any failed sale"
    - "Because PHP prevents stock from ever changing"
  answer: 1
  explanation: "sell() calls $this->isAvailable($quantity) first; since only 6 units remain, it returns false, and sell() exits before subtracting from $this->stock."

- question: "What happens if you write 'stock += $amount;' inside a method instead of '$this->stock += $amount;'?"
  options:
    - "It works identically because PHP infers $this automatically"
    - "It creates or modifies a local/undefined variable named stock, NOT the object's stock property"
    - "It throws a compile-time syntax error"
    - "It modifies the stock property on every object of that class"
  answer: 1
  explanation: "Without the $this-> prefix, PHP treats 'stock' as a separate, unrelated variable, not the object's property, which is a common source of bugs for beginners."
```
