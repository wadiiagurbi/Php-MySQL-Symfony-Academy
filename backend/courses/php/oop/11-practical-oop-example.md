---
slug: practical-oop-example
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "A Practical OOP Example"
difficulty: intermediate
order: 11
estimatedMinutes: 15
tags: [php, oop, design, project]
---

## Learning Objective

Combine everything from this chapter — classes, properties, methods, constructors, visibility, encapsulation, inheritance, interfaces, dependency injection, and SOLID thinking — into one small, realistic, working example: a notification system for an online store's order process.

## Explanation

Across this chapter you learned each OOP building block in isolation. Real code, however, uses all of them together at once. This lesson walks through one cohesive example: an `Order` that needs to notify a customer once it ships, using a notification system that can send that message through different channels (email or SMS) without the order's own code needing to know which channel is actually used.

The design pulls together:
- A `Notifier` **interface** (lesson 7) — the contract: anything that can `send(string $to, string $message): void`.
- Two concrete classes, `EmailNotifier` and `SmsNotifier`, each **implementing** that interface differently — polymorphism across unrelated classes.
- An `Order` **class** with **properties** (lesson 2–3) holding its own data (customer email, items, shipped status), guarded by **encapsulation** (lesson 5) — `items` is `private`, only modifiable through a controlled method `addItem()`, and `markShipped()` is the only way to flip the shipped flag, keeping the object always in a valid state.
- `Order`'s **constructor** (lesson 4) requires a `Notifier` to be **injected** (lesson 9) rather than created internally with `new EmailNotifier()` — meaning the exact same `Order` class works whether the store wants to notify by email, SMS, or (in the future) push notification, with zero changes to `Order` itself. This satisfies both the Open/Closed and Dependency Inversion principles from lesson 10.
- A `PrioritySmsNotifier` that **extends** `SmsNotifier` (lesson 6), overriding `send()` to add urgency, demonstrating inheritance and polymorphism working alongside the interface-based design.

Notice how each concept plays a distinct, necessary role — this is what real production code looks like: not one technique used in isolation, but several working together so that each class stays focused (Single Responsibility), depends only on what it truly needs (Dependency Inversion), and can be extended safely (Open/Closed) without ever being rewritten.

## Why It Matters

This is essentially a miniature version of how a real Symfony application is structured: small, focused classes; interfaces defining swappable capabilities; dependencies passed in through constructors; and business rules enforced through encapsulation rather than trusted to whoever happens to be using the object. When you reach Symfony's service container later in this course, you will recognize this exact shape immediately — Symfony automates *wiring* dependencies together, but the classes it wires are built using precisely the principles practiced in this lesson.

## Example

An online store ships an `Order`. Depending on store configuration, shipment notifications go out by email or SMS, and VIP customers get a more urgent SMS variant — all without `Order` ever knowing which notifier it was given.

## Code

```php
<?php

interface Notifier
{
    public function send(string $to, string $message): void;
}

class EmailNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        echo "Email to {$to}: {$message}\n";
    }
}

class SmsNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        echo "SMS to {$to}: {$message}\n";
    }
}

// Inheritance + polymorphism: overrides send() to add urgency for VIPs.
class PrioritySmsNotifier extends SmsNotifier
{
    public function send(string $to, string $message): void
    {
        parent::send($to, "URGENT: " . $message);
    }
}

class Order
{
    private array $items = [];
    private bool $shipped = false;

    // Dependency injection: Order receives a Notifier, it never creates one itself.
    public function __construct(
        private string $customerEmail,
        private Notifier $notifier
    ) {
    }

    public function addItem(string $item): void
    {
        if ($this->shipped) {
            throw new \LogicException("Cannot add items after the order has shipped.");
        }
        $this->items[] = $item;
    }

    public function markShipped(): void
    {
        if (empty($this->items)) {
            throw new \LogicException("Cannot ship an order with no items.");
        }

        $this->shipped = true;
        $itemList = implode(", ", $this->items);
        $this->notifier->send($this->customerEmail, "Your order ({$itemList}) has shipped!");
    }
}

// Same Order class, two completely different notification channels:
$emailOrder = new Order("alice@example.com", new EmailNotifier());
$emailOrder->addItem("Wireless Mouse");
$emailOrder->markShipped();

$vipOrder = new Order("+15551234567", new PrioritySmsNotifier());
$vipOrder->addItem("Laptop Stand");
$vipOrder->markShipped();
```

## Code Explanation

- `interface Notifier` defines the single required capability, `send()`. `EmailNotifier`, `SmsNotifier`, and `PrioritySmsNotifier` (through inheritance) all satisfy it.
- `PrioritySmsNotifier extends SmsNotifier` overrides `send()` but reuses the parent's implementation via `parent::send(...)`, only adding an "URGENT:" prefix — inheritance and method overriding from lesson 6.
- `Order`'s constructor takes `Notifier $notifier` as a dependency, injected from outside — `Order` never writes `new EmailNotifier()` itself, so it works identically regardless of which notifier is passed in.
- `private array $items` and `private bool $shipped` are hidden from outside code (encapsulation); the only way to change them is through `addItem()` and `markShipped()`, which enforce the business rules: you cannot add items after shipping, and you cannot ship an empty order.
- `markShipped()` calls `$this->notifier->send(...)`, using whichever notifier was injected — for `$emailOrder`, that's plain email; for `$vipOrder`, it's the urgency-adding `PrioritySmsNotifier`.
- Both `Order` objects run through identical code paths inside `Order` itself, yet produce different real-world behavior purely because of which `Notifier` was injected — this is the practical payoff of interfaces plus dependency injection.

## Common Mistakes

- Letting `Order` construct its own notifier internally (`new EmailNotifier()` inside the constructor), which would remove the flexibility to swap channels and make testing much harder, undoing the entire point of the design.
- Making `items` or `shipped` `public`, which would let any code outside `Order` push items after shipping or flip `shipped` to `true` without going through the validation in `markShipped()`, breaking encapsulation and risking invalid state.
- Forgetting `parent::send($to, ...)` inside the overriding method and instead duplicating `SmsNotifier`'s logic by hand, which would mean any future bug fix to `SmsNotifier::send()` wouldn't automatically apply to `PrioritySmsNotifier`.

## Best Practices

- Design the "shape" of a system (interfaces first, like `Notifier`) before writing every concrete class — this is often called designing "to an interface," and it keeps the door open for future implementations.
- Keep validation rules (like "cannot ship an empty order") inside the class that owns the data being validated, not scattered in whatever code happens to be calling it.
- When a subclass only needs to slightly adjust behavior (like adding an "URGENT:" prefix), override the method and delegate to `parent::` rather than rewriting the whole thing — this keeps the relationship between parent and child behavior clear and avoids duplication.

## Exercise

Extend the example with a `LoggingNotifier` class that also implements `Notifier`. Its `send()` method should print a log line like `"[LOG] Notification sent to {$to}"` and then delegate the actual sending to another injected `Notifier` (in other words, `LoggingNotifier` should itself take a `Notifier` as a constructor dependency, and wrap it). Use it to wrap an `EmailNotifier` when creating an `Order`.

## Solution

```php
<?php

class LoggingNotifier implements Notifier
{
    public function __construct(private Notifier $inner)
    {
    }

    public function send(string $to, string $message): void
    {
        echo "[LOG] Notification sent to {$to}\n";
        $this->inner->send($to, $message);
    }
}

$loggedOrder = new Order("bob@example.com", new LoggingNotifier(new EmailNotifier()));
$loggedOrder->addItem("Keyboard");
$loggedOrder->markShipped();
```

`LoggingNotifier` itself depends on another `Notifier` (dependency injection again, one layer deeper), and because it also implements `Notifier`, `Order` can accept it exactly like any other notifier — it has no idea it's actually talking to a wrapper that logs before delegating. This "wrapping one implementation inside another, both honoring the same interface" pattern is common in real applications and only works because every piece depends on the shared `Notifier` contract rather than on each other's concrete classes.

## Mini Quiz

```yaml
- question: "Why does Order's constructor accept a Notifier as a parameter instead of creating one internally?"
  options:
    - "Because PHP does not allow objects to be created inside constructors"
    - "To allow the same Order class to work with any notification channel without being modified, and to make testing easier"
    - "Because interfaces cannot be used inside constructors"
    - "It has no real benefit, it is just a stylistic choice"
  answer: 1
  explanation: "Dependency injection lets Order stay unchanged while supporting email, SMS, or any future Notifier implementation, and lets tests substitute a fake Notifier instead of triggering real sends."

- question: "Why are the $items and $shipped properties on Order declared private rather than public?"
  options:
    - "Private properties run faster than public ones"
    - "To enforce business rules (like preventing items being added after shipping) by forcing all changes through controlled methods"
    - "PHP requires all array properties to be private"
    - "Private properties are automatically saved to a database"
  answer: 1
  explanation: "Making the properties private and only modifiable through addItem() and markShipped() ensures the object can never be pushed into an invalid state from outside code, which is the purpose of encapsulation."

- question: "What does PrioritySmsNotifier's use of parent::send() achieve?"
  options:
    - "It calls SmsNotifier's implementation and adds an urgency prefix, avoiding duplicating SmsNotifier's own logic"
    - "It creates a brand-new SmsNotifier object from scratch"
    - "It deletes the parent class's send method"
    - "It has no effect since PrioritySmsNotifier already overrides send()"
  answer: 0
  explanation: "parent::send() invokes the parent class's version of the method, letting the subclass reuse and build on that behavior instead of rewriting it, here adding an 'URGENT:' prefix."
```
