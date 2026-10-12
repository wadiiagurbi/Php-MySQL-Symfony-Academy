---
slug: interfaces-and-abstract-classes
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Interfaces and Abstract Classes"
difficulty: intermediate
order: 7
estimatedMinutes: 12
tags: [php, oop, interfaces, abstract-classes]
---

## Learning Objective

Understand what an interface is (a contract that guarantees certain methods exist, with no implementation of its own) and what an abstract class is (a partial blueprint that cannot be instantiated directly), and learn when to use each.

## Explanation

In the previous lesson, `Circle` and `Rectangle` both extended `Shape` and overrode `area()`. That worked because they were genuinely related — both really "are" shapes. But sometimes you want to guarantee that several *unrelated* classes all provide a certain method, without forcing them to share a common parent class. This is what **interfaces** are for.

An **interface** declares a set of method signatures (name, parameters, return type) that any class implementing it must provide — but an interface itself contains no actual code, no method bodies, no properties. It is a pure **contract**: "any class that implements me promises to have these methods." You declare one with the `interface` keyword, and a class agrees to the contract using `implements`:

```php
interface Notifiable
{
    public function send(string $message): void;
}

class EmailNotifier implements Notifiable
{
    public function send(string $message): void
    {
        echo "Emailing: $message\n";
    }
}
```

`EmailNotifier` is not related to any other notifier by inheritance — an `SmsNotifier` could `implements Notifiable` too, with completely different internal code, and both would be usable anywhere a `Notifiable` is expected. This is polymorphism again, but based on a *shared contract* rather than a shared parent class. A class can implement multiple interfaces at once (unlike `extends`, which only allows one parent), which is one reason interfaces are so flexible: `class EmailNotifier implements Notifiable, Loggable { ... }`.

An **abstract class** sits between a regular class and an interface. It is declared with the `abstract` keyword, and it *can* contain real properties and fully-implemented methods (shared code, just like a normal parent class), but it can also declare **abstract methods** — methods with no body, that every subclass is *forced* to implement. Crucially, you cannot do `new` directly on an abstract class; it only exists to be extended.

```php
abstract class PaymentMethod
{
    public function __construct(protected float $amount) {}

    public function describe(): string
    {
        return "Payment of $" . number_format($this->amount, 2);
    }

    abstract public function charge(): bool;
}

class CardPayment extends PaymentMethod
{
    public function charge(): bool
    {
        echo "Charging card...\n";
        return true;
    }
}
```

Here, `describe()` is fully written once in `PaymentMethod` and inherited by every subclass for free, while `charge()` has no implementation in the parent at all — each subclass *must* provide its own, or PHP will refuse to let that subclass be instantiated.

So when do you choose which? Use an **interface** when you just need to guarantee "this class has these methods," especially across otherwise-unrelated classes, or when a class already needs to extend something else (since PHP only allows one `extends` but many `implements`). Use an **abstract class** when several related classes should share both a contract *and* some real, common implementation code, avoiding duplication.

## Why It Matters

Interfaces are the backbone of how Symfony (and most professional PHP frameworks) achieve flexibility: framework code is written to depend on an interface like `LoggerInterface` rather than a specific logging class, so you can swap in your own implementation without changing the framework's code at all. This pattern — "depend on the contract, not the specific class" — is what allows huge applications to remain testable and swappable, and you'll rely on it constantly once you reach dependency injection and Symfony's service container later in this course.

## Example

A notification system where `EmailNotifier` and `SmsNotifier` both implement a shared `Notifiable` interface, and code that sends a notification doesn't need to know which specific one it received.

## Code

```php
<?php

interface Notifiable
{
    public function send(string $message): void;
}

class EmailNotifier implements Notifiable
{
    public function send(string $message): void
    {
        echo "Email sent: $message\n";
    }
}

class SmsNotifier implements Notifiable
{
    public function send(string $message): void
    {
        echo "SMS sent: $message\n";
    }
}

function notifyAll(array $notifiers, string $message): void
{
    foreach ($notifiers as $notifier) {
        $notifier->send($message); // works for ANY Notifiable, regardless of class
    }
}

$notifiers = [new EmailNotifier(), new SmsNotifier()];
notifyAll($notifiers, "Your order has shipped!");
```

## Code Explanation

- `interface Notifiable { public function send(string $message): void; }` declares the contract: any implementing class must have a `send()` method with exactly this signature. Note there is no `{ ... }` body — interfaces never contain implementation.
- `class EmailNotifier implements Notifiable` and `class SmsNotifier implements Notifiable` both fulfill the contract with their own, completely independent implementations of `send()`.
- `notifyAll(array $notifiers, string $message)` takes an array of *any* objects that implement `Notifiable`. It has no idea, and does not need to know, whether each one is an `EmailNotifier` or `SmsNotifier`.
- `$notifier->send($message);` calls whichever `send()` belongs to the real object's actual class — this is polymorphism through an interface rather than through inheritance.
- Because both classes satisfy the same contract, adding a third notifier type later (e.g., `PushNotifier implements Notifiable`) would require zero changes to `notifyAll()`.

## Common Mistakes

- Trying to instantiate an abstract class directly, e.g. `new PaymentMethod(50)` — PHP raises a fatal error because abstract classes exist only to be extended, never created directly.
- Putting method bodies inside an interface. Interfaces can only declare method signatures — attempting to write `{ ... }` code inside an interface method is a syntax error.
- Forgetting to implement every abstract method from an abstract parent (or every method from an interface) in a concrete subclass — PHP will refuse to let you instantiate that subclass until all required methods are provided.

## Best Practices

- Name interfaces clearly, often ending in `Interface` in PHP/Symfony convention (e.g., `NotifiableInterface`), so it's immediately obvious in code that a type is a contract rather than a concrete class.
- Prefer depending on an interface type in function/method parameters (like `Notifiable $notifier`) rather than a specific concrete class, so your code works with any current or future implementation.
- Use an abstract class instead of an interface specifically when you have real, shared code to avoid duplicating across subclasses — if there's nothing to share beyond the method signatures, a plain interface is simpler and more flexible.

## Exercise

Create an interface `Shape` with a method `area(): float`. Create two classes, `Square` and `Triangle`, that both implement `Shape` with correct area formulas (`side * side` for a square; `0.5 * base * height` for a triangle). Write a function `printAreas(array $shapes): void` that prints the area of each shape in a mixed array, using the shared interface.

## Solution

```php
<?php

interface Shape
{
    public function area(): float;
}

class Square implements Shape
{
    public function __construct(private float $side) {}

    public function area(): float
    {
        return $this->side * $this->side;
    }
}

class Triangle implements Shape
{
    public function __construct(private float $base, private float $height) {}

    public function area(): float
    {
        return 0.5 * $this->base * $this->height;
    }
}

function printAreas(array $shapes): void
{
    foreach ($shapes as $shape) {
        echo "Area: " . $shape->area() . "\n";
    }
}

printAreas([new Square(4), new Triangle(6, 3)]); // Area: 16, Area: 9
```

`Square` and `Triangle` share no inheritance relationship at all — they are unrelated classes that both simply agree to the `Shape` contract by implementing `area()`. `printAreas()` works with either one (or any future `Shape` implementation) without any type-checking logic, because it only relies on the guarantee the interface provides.

## Mini Quiz

```yaml
- question: "What is the key difference between an interface and an abstract class?"
  options:
    - "There is no real difference; they are interchangeable in PHP"
    - "An interface can contain method implementations, but an abstract class cannot"
    - "An interface only declares method signatures with no implementation, while an abstract class can mix fully-implemented methods with abstract ones that subclasses must implement"
    - "Abstract classes can be implemented by multiple classes at once, but interfaces cannot"
  answer: 2
  explanation: "Interfaces are pure contracts with no code at all, while abstract classes can provide real shared implementation alongside abstract methods subclasses are forced to define."

- question: "Why can a class implement multiple interfaces but extend only one class?"
  options:
    - "This is just a PHP limitation with no design reasoning behind it"
    - "PHP is designed this way to avoid the ambiguity of inheriting conflicting implementations from multiple parents, while still allowing a class to satisfy many independent contracts"
    - "Interfaces are actually a special kind of class in disguise"
    - "It is not true; PHP allows multiple extends as well"
  answer: 1
  explanation: "Because interfaces carry no implementation, there's no ambiguity in implementing several at once. Multiple inheritance of implemented code, however, can create conflicts about which parent's method body should apply, so PHP restricts extends to one parent."

- question: "What happens if you try to run 'new PaymentMethod(50)' where PaymentMethod is declared as an abstract class?"
  options:
    - "It works exactly like a normal class"
    - "PHP throws a fatal error, because abstract classes cannot be instantiated directly"
    - "It creates an object but with all abstract methods returning null"
    - "It silently converts PaymentMethod into a concrete class"
  answer: 1
  explanation: "Abstract classes exist only to be extended by concrete subclasses; PHP disallows creating an instance of the abstract class itself."
```
