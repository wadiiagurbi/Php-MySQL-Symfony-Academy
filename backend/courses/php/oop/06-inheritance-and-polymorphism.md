---
slug: inheritance-and-polymorphism
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Inheritance and Polymorphism"
difficulty: intermediate
order: 6
estimatedMinutes: 11
tags: [php, oop, inheritance, polymorphism]
---

## Learning Objective

Learn how one class can extend another to reuse and specialize its behavior (inheritance), and how objects of different subclasses can be treated interchangeably through a shared parent type (polymorphism).

## Explanation

Imagine you are modeling different kinds of employees: a `Manager` and a `Developer`. Both share things in common — a name, a salary, a method to describe themselves — but each also has something unique: a `Manager` might have a `teamSize`, while a `Developer` has a `programmingLanguage`. Without a way to share the common parts, you would have to copy-paste the shared properties and methods into both classes, and if you ever needed to fix a bug in the shared logic, you'd have to remember to fix it in both places.

**Inheritance** solves this. You define a general class — called the **parent class** (or "base class" or "superclass") — containing everything shared. Then you define more specific classes — **child classes** (or "subclasses") — that use the `extends` keyword to inherit everything from the parent automatically, adding or overriding only what makes them different.

```php
class Employee {
    public function __construct(protected string $name, protected float $salary) {}
    public function describe(): string {
        return "{$this->name}, earning {$this->salary}";
    }
}

class Manager extends Employee {
    // Manager automatically has $name, $salary, and describe() from Employee.
}
```

Notice the properties in `Employee` are declared `protected`, not `private`. Recall from an earlier lesson: `protected` is accessible from the declaring class *and* from any subclass, but not from completely unrelated outside code. This is exactly the visibility level inheritance needs — `Manager` can use `$this->name` inside its own methods, but code outside the class hierarchy still cannot reach it directly.

A child class can **override** an inherited method by redeclaring a method with the same name — its own version replaces the parent's when called on that child. Inside an overriding method, a child can still call the parent's original version using `parent::methodName()`, which is useful when you want to *add* behavior rather than fully replace it.

```php
class Manager extends Employee {
    public function __construct(string $name, float $salary, private int $teamSize) {
        parent::__construct($name, $salary); // Calls Employee's constructor.
    }

    public function describe(): string {
        return parent::describe() . ", managing {$this->teamSize} people";
    }
}
```

This overriding ability leads directly to **polymorphism** ("many forms"): if you have a list of different `Employee` subclasses — some `Manager`s, some `Developer`s — you can call `$employee->describe()` on each one without caring which specific subclass it is. Each object automatically runs *its own* version of `describe()`. The calling code is written once, against the general `Employee` type, yet behaves differently depending on the real, concrete object at runtime. This is one of OOP's most powerful ideas: code that works with a general type automatically adapts to every specific subtype, without needing `if`/`else` checks for each one.

## Why It Matters

Inheritance and polymorphism let you avoid duplicating shared logic across related classes, and — more importantly for real applications — they let you write code that works against a general concept ("any `Employee`," "any `PaymentMethod`") and have it correctly handle every specific case without a growing pile of `if ($employee instanceof Manager) { ... } elseif ($employee instanceof Developer) { ... }` checks scattered throughout the codebase. Symfony itself is full of this pattern: for example, many different classes implement the same interface (the next lesson's topic) so the framework can treat them uniformly, calling the same method name and letting each concrete class provide its own behavior.

## Example

A payroll report needs to print a description line for every employee in a company, regardless of whether each one is a `Manager` or a `Developer`. Thanks to polymorphism, the report loop calls `describe()` once per employee without needing to know which subclass it actually is.

## Code

```php
<?php

class Employee
{
    public function __construct(protected string $name, protected float $salary)
    {
    }

    public function describe(): string
    {
        return "{$this->name}, earning \${$this->salary}";
    }
}

class Manager extends Employee
{
    public function __construct(string $name, float $salary, private int $teamSize)
    {
        parent::__construct($name, $salary);
    }

    public function describe(): string
    {
        return parent::describe() . ", managing {$this->teamSize} people";
    }
}

class Developer extends Employee
{
    public function __construct(string $name, float $salary, private string $language)
    {
        parent::__construct($name, $salary);
    }

    public function describe(): string
    {
        return parent::describe() . ", writing {$this->language}";
    }
}

$employees = [
    new Manager("Alice", 90000, 5),
    new Developer("Bob", 80000, "PHP"),
];

foreach ($employees as $employee) {
    echo $employee->describe() . "\n"; // Each object runs its own describe().
}
```

## Code Explanation

- `class Manager extends Employee` establishes inheritance: `Manager` automatically has the `$name` and `$salary` properties and gains the `describe()` method, without redeclaring them.
- `parent::__construct($name, $salary);` inside `Manager`'s constructor calls `Employee`'s constructor explicitly, delegating the shared setup work back to the parent rather than duplicating it.
- `describe()` is overridden in both `Manager` and `Developer`, each calling `parent::describe()` to reuse the shared "{$name}, earning..." text and then appending its own specific detail.
- The `$employees` array holds a mix of `Manager` and `Developer` objects, but the variable/array itself only needs to know they are all `Employee`s.
- `foreach ($employees as $employee) { echo $employee->describe() . "\n"; }` calls `describe()` once per object without checking which subclass it is — PHP automatically runs `Manager`'s `describe()` for the manager and `Developer`'s `describe()` for the developer. This is polymorphism in action.

## Common Mistakes

- Forgetting to call `parent::__construct(...)` inside a child class's own constructor, which leaves the inherited properties (`$name`, `$salary`) unset even though the parent class expects them to be initialized.
- Declaring parent properties as `private` when subclasses need direct access to them — `private` is invisible even to child classes; use `protected` when a subclass genuinely needs to reach a property directly.
- Writing manual `instanceof` checks (`if ($employee instanceof Manager) { ... }`) to decide what to do, when overriding a shared method and relying on polymorphism would be simpler and easier to extend with new subclasses later.
- Overriding a method but forgetting `parent::methodName()` when you actually wanted to build on the parent's behavior rather than fully replace it, leading to duplicated or missing logic.

## Best Practices

- Use inheritance only for genuine "is-a" relationships — a `Manager` truly *is* a kind of `Employee`. If two classes merely happen to share a few methods without a real conceptual relationship, prefer composition or traits (from an earlier/later lesson) instead of forcing an artificial inheritance link.
- Keep parent classes focused on what is genuinely common to all children; push anything specific to one subtype down into that subtype instead of bloating the parent.
- When overriding a method to add behavior rather than replace it entirely, call `parent::methodName()` explicitly so the shared behavior isn't silently lost.
- Favor polymorphism (calling the same method name on objects of different subclasses) over long `instanceof`/`switch` chains — it scales better as new subclasses are added.

## Exercise

Create a base class `Shape` with a `protected string $name` (set via constructor) and a method `describe(): string` returning `"{$name}: area not implemented"`. Create two subclasses, `Circle` (with `private float $radius`) and `Rectangle` (with `private float $width, private float $height`), each overriding `describe()` to compute and include the correct area (`area = π × r²` for circle, `width × height` for rectangle), while still starting the description with the shape's name via `parent::describe()`-style reuse of the name if useful. Put several shapes in an array and print each `describe()` result in a loop.

## Solution

```php
<?php

class Shape
{
    public function __construct(protected string $name)
    {
    }

    public function describe(): string
    {
        return "{$this->name}: area not implemented";
    }
}

class Circle extends Shape
{
    public function __construct(string $name, private float $radius)
    {
        parent::__construct($name);
    }

    public function describe(): string
    {
        $area = M_PI * $this->radius ** 2;
        return "{$this->name}: area = " . round($area, 2);
    }
}

class Rectangle extends Shape
{
    public function __construct(string $name, private float $width, private float $height)
    {
        parent::__construct($name);
    }

    public function describe(): string
    {
        $area = $this->width * $this->height;
        return "{$this->name}: area = " . round($area, 2);
    }
}

$shapes = [
    new Circle("Circle A", 3.0),
    new Rectangle("Rectangle B", 4.0, 5.0),
];

foreach ($shapes as $shape) {
    echo $shape->describe() . "\n";
}
// Circle A: area = 28.27
// Rectangle B: area = 20
```

Both `Circle` and `Rectangle` extend `Shape` and reuse its constructor via `parent::__construct($name)` to set the shared `$name` property, then each overrides `describe()` with its own area formula. The loop treats every shape uniformly as a `Shape`, but PHP calls each object's own overridden `describe()`, correctly computing a circle's area versus a rectangle's area without any `instanceof` checks in the loop.

## Mini Quiz

```yaml
- question: "What keyword lets one class inherit properties and methods from another in PHP?"
  options:
    - "implements"
    - "extends"
    - "use"
    - "inherits"
  answer: 1
  explanation: "A class uses 'extends' to inherit from a parent class, gaining its non-private properties and methods automatically."

- question: "Why are Employee's properties declared 'protected' rather than 'private' in this lesson's example?"
  options:
    - "protected properties execute faster than private ones"
    - "So subclasses like Manager and Developer can access them directly via $this, while outside code still cannot"
    - "PHP requires all inherited classes to use protected properties"
    - "There is no real difference between protected and private"
  answer: 1
  explanation: "protected allows access from the declaring class and any subclass, which is exactly what's needed for inheritance, whereas private would hide the property even from child classes."

- question: "In the payroll example, why can the foreach loop call $employee->describe() without checking whether each item is a Manager or a Developer?"
  options:
    - "Because describe() is a static method"
    - "Because of polymorphism: each object automatically runs its own overridden version of describe(), even though the loop only treats them as Employee"
    - "Because Manager and Developer are actually the exact same class"
    - "Because PHP ignores method overriding inside loops"
  answer: 1
  explanation: "Polymorphism means the correct overridden method for each object's actual class runs automatically, letting general code work uniformly across different subclasses."

- question: "What does calling parent::describe() from inside an overriding method do?"
  options:
    - "It deletes the parent class's describe() method"
    - "It calls the parent class's original version of describe(), letting the child build on top of it instead of fully replacing it"
    - "It calls describe() on every subclass at once"
    - "It causes an infinite loop"
  answer: 1
  explanation: "parent::methodName() explicitly invokes the parent class's implementation, which is useful for extending shared behavior rather than duplicating or discarding it."
```
