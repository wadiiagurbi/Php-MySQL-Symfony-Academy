---
slug: classes-and-objects
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Classes and Objects"
difficulty: intermediate
order: 2
estimatedMinutes: 8
tags: [php, oop, classes, objects]
---

## Learning Objective

Learn the anatomy of a class definition, create multiple independent objects from the same class, and check an object's type using `instanceof`.

## Explanation

In the previous lesson you saw that a class is a blueprint and an object is a real instance created from it. Now let's look more closely at how you actually write a class body, and what happens when you create more than one object from the same class.

A class body is simply a block of code between `class ClassName { ... }` that lists the properties (data) and methods (behavior) the objects will have. There is no limit to how many properties or methods a class can declare, and you list them one after another, in any order you like — PHP does not require properties to come before methods or vice versa, though keeping properties together at the top is a common convention for readability.

The important thing to understand is that **each object you create from a class is completely independent**. When you write `new Car()`, PHP allocates a fresh, separate "slot" in memory to hold that object's own property values. If you then create a second `Car` object with `new Car()` again, it gets its own separate slot too — changing a property on the first object has absolutely no effect on the second one, even though both objects came from the exact same class definition. This is exactly like two cookies cut from the same cutter: they share the same shape, but you can decorate one with icing without changing the other.

Sometimes you need to check what class an object was actually created from — for example, before calling a method that only exists on certain kinds of objects. PHP gives you the `instanceof` operator for this: `$car instanceof Car` evaluates to `true` if `$car` was created from the `Car` class (or certain related classes, as you'll see once we cover inheritance), and `false` otherwise.

## Why It Matters

Understanding that objects are independent instances is not just theoretical — it directly affects how you avoid bugs. A common beginner mistake is assuming that modifying one object somehow "resets" or "shares" state with another object of the same class, which leads to confusing bugs where changes seem to appear or vanish unexpectedly. In a real application, you might create hundreds of `Order` objects while processing a batch of orders, and each one needs to hold its own, separate data — a customer's order total should never leak into another customer's order just because they came from the same `Order` class. Knowing objects are independent by default is foundational to reasoning correctly about any object-oriented codebase, including every Symfony application you build later.

## Example

A `Car` class has `color` and `speed` properties and an `accelerate()` method. We create two separate `Car` objects, change one, and observe the other is unaffected.

## Code

```php
<?php

class Car
{
    public string $color = "white";
    public int $speed = 0;

    public function accelerate(int $amount): void
    {
        $this->speed += $amount;
    }
}

$car1 = new Car();
$car1->color = "red";
$car1->accelerate(30);

$car2 = new Car();
$car2->color = "blue";
$car2->accelerate(10);

echo "Car 1: color={$car1->color}, speed={$car1->speed}\n"; // red, 30
echo "Car 2: color={$car2->color}, speed={$car2->speed}\n"; // blue, 10

var_dump($car1 instanceof Car); // bool(true)
var_dump($car2 instanceof Car); // bool(true)
var_dump($car1 === $car2);      // bool(false) — two separate objects
```

## Code Explanation

- `$car1 = new Car();` and `$car2 = new Car();` create two entirely separate objects. Each has its own `color` and `speed` properties starting from the class's declared defaults (`"white"` and `0`).
- Setting `$car1->color = "red";` and calling `$car1->accelerate(30);` only affects `$car1`'s own copy of those properties. `$car2` is untouched by these calls, which is confirmed by the different printed values.
- `$car1 instanceof Car` checks whether `$car1` was created from the `Car` class, which it was, so it prints `true`. The same is true for `$car2`.
- `$car1 === $car2` uses strict comparison to check whether both variables refer to the exact same object in memory. Even though both were built from the same `Car` class and could theoretically end up with identical property values, they are still two distinct objects, so this evaluates to `false`.

## Common Mistakes

- Assuming that two objects created from the same class automatically "share" data. Each `new` call produces a fully independent object; nothing is shared unless you explicitly pass the same object reference around.
- Using `==` instead of `instanceof` to check an object's type. `==` compares values for equality, not class identity, and can produce confusing results when applied to objects.
- Forgetting the parentheses when calling `instanceof` alongside other expressions, e.g. mixing it up with function-call syntax — `instanceof` is an operator, used as `$object instanceof ClassName`, with no parentheses required around the class name.

## Best Practices

- When debugging, don't assume — use `var_dump($object)` or `print_r($object)` to actually see an object's current property values rather than guessing based on memory of what you set earlier.
- Use `instanceof` checks sparingly and mainly for genuinely necessary type-safety situations (like verifying user input produced the expected object); over-relying on it as a substitute for good class design is a sign the code's structure could be improved, a topic you'll explore later in this chapter.
- Keep property declarations grouped at the top of the class body and methods below them, purely as a widely used convention that makes classes easier to skim.

## Exercise

Create a `Light` class with a `bool $isOn` property defaulting to `false` and a method `toggle(): void` that flips `isOn` to its opposite value. Create two `Light` objects, toggle only the first one, and print both objects' `isOn` values to confirm they remain independent.

## Solution

```php
<?php

class Light
{
    public bool $isOn = false;

    public function toggle(): void
    {
        $this->isOn = !$this->isOn;
    }
}

$light1 = new Light();
$light2 = new Light();

$light1->toggle();

var_dump($light1->isOn); // bool(true)
var_dump($light2->isOn); // bool(false) — unaffected by $light1's change
```

Both `Light` objects start with `isOn` set to `false` by default. Calling `toggle()` on `$light1` flips only its own `isOn` property to `true`, while `$light2`, being a completely separate object, keeps its original `false` value, demonstrating object independence.

## Mini Quiz

```yaml
- question: "If you create two objects from the same class with `new`, and then change a property on the first object, what happens to the second object?"
  options:
    - "The second object's matching property changes to the same value automatically"
    - "The second object is unaffected, because each object has its own independent copy of the properties"
    - "Both objects are merged into one"
    - "PHP throws an error because two objects of the same class cannot exist at once"
  answer: 1
  explanation: "Every object created with 'new' is independent. Changing one object's properties never affects another object, even if both came from the same class."

- question: "What does `$car instanceof Car` check?"
  options:
    - "Whether $car and Car have equal property values"
    - "Whether $car was created from the Car class (or a related class)"
    - "Whether the Car class has been defined anywhere in the script"
    - "Whether $car->color equals 'Car'"
  answer: 1
  explanation: "instanceof checks the class an object was instantiated from, returning true or false, and is unrelated to comparing property values."

- question: "What does `$car1 === $car2` check when both are objects?"
  options:
    - "Whether all their properties happen to have equal values"
    - "Whether they are literally the exact same object in memory, not just similar"
    - "Whether they were created from the same class"
    - "It always returns true for objects of the same class"
  answer: 1
  explanation: "Strict comparison (===) on two object variables checks object identity — whether both variables point to the very same object instance — not just whether their properties look alike."
```
