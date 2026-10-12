---
slug: design-patterns
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Common Design Patterns in PHP"
difficulty: intermediate
order: 14
estimatedMinutes: 12
tags: [php, oop, patterns]
---

## Learning Objective

By the end of this lesson, you will be able to describe what a design pattern is and implement the Singleton, Factory, and Observer patterns in small PHP examples.

## Explanation

A **design pattern** is a reusable solution template for a recurring design problem — not finished code to copy, but a named shape experienced developers recognize. Patterns have two benefits: they solve the problem well, and their names give teams shorthand ("use a Factory here") instead of long explanations.

Three patterns cover a large share of everyday needs. The **Singleton** guarantees exactly one instance of a class (useful for a configuration holder). The **Factory** centralizes object creation behind a method, so callers request what they want ("a notifier for SMS") without knowing construction details. The **Observer** lets one object (the **subject**) notify many interested objects (the **observers**) when something happens, without knowing who they are in advance.

Each pattern trades simplicity for flexibility, so the skill lies in applying them where they genuinely reduce complexity rather than everywhere possible.

## Why It Matters

Symfony and most PHP libraries are built from these patterns: services behave like shared single instances, form and validator components use factories, and the event dispatcher you will meet later is the Observer pattern at framework scale. Recognizing the shapes now makes framework code read as familiar variations instead of unfamiliar inventions.

## Example

An online shop needs order notifications. A `NotifierFactory` creates the right channel (email or SMS), and an `OrderSubject` alerts every attached observer (logger, mailer) whenever an order ships.

## Code

```php
<?php

interface OrderObserver
{
    public function onOrderShipped(int $orderId): void;
}

class OrderSubject
{
    private array $observers = [];

    public function attach(OrderObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function shipOrder(int $orderId): void
    {
        foreach ($this->observers as $observer) {
            $observer->onOrderShipped($orderId);
        }
    }
}
```

## Code Explanation

- `OrderObserver` is an **interface** (a contract listing required methods) so any class with `onOrderShipped()` can observe, regardless of its other behavior.
- `attach()` collects observers into the `$observers` array without the subject needing to know their concrete classes.
- `shipOrder()` loops over every attached observer and notifies each one — this notification loop is the heart of the Observer pattern.
- Callers add new reactions (a logger, an SMS sender) by writing a small observer class and attaching it, with no changes to `OrderSubject` itself.

## Common Mistakes

- **Making everything a Singleton.** Shared single instances hide dependencies and complicate tests; most classes should be ordinary, injectable objects.
- **Building a Factory with only one product.** A factory that always creates the same class adds indirection with no benefit.
- **Letting observers secretly depend on each other's order.** Observers should each stand alone, since notification order is an implementation detail.

## Best Practices

- Reach for a pattern when you feel the specific pain it addresses (many creation branches, many notification targets), not in advance.
- Program to interfaces (`OrderObserver`) rather than concrete classes so new variants plug in freely.
- Keep pattern code small and readable; a pattern that obscures the logic costs more than it saves.

## Exercise

Design (in words plus class names) a Factory for the shop's notifiers: a `NotifierFactory::create(string $channel)` method returning either an `EmailNotifier` or an `SmsNotifier` sharing a `Notifier` interface. When would you add a third notifier?

## Solution

Define a `Notifier` interface with `send(string $to, string $message): void`, implement it in `EmailNotifier` and `SmsNotifier`, and write `NotifierFactory::create()` with a `match` on `$channel` returning the right class. Add a third notifier (for example `PushNotifier`) the moment a new channel is needed — the factory gains one branch while callers stay unchanged.

## Mini Quiz

```yaml
- question: "What is a design pattern?"
  options:
    - "A finished library you install with Composer"
    - "A reusable, named solution template for a recurring design problem"
    - "A PHP syntax error category"
    - "A database table layout tool"
  answer: 1
  explanation: "Patterns describe proven shapes like Factory or Observer that developers adapt to their own code and discuss by name."
- question: "What does the Factory pattern centralize?"
  options:
    - "Database backups"
    - "Object creation behind a method so callers avoid construction details"
    - "HTML page styling"
    - "Password hashing algorithms"
  answer: 1
  explanation: "A factory method picks and builds the right class for the caller, isolating construction logic in one place."
- question: "How do subject and observers relate in the Observer pattern?"
  options:
    - "Observers rewrite the subject's source code"
    - "The subject notifies attached observers of events without knowing their concrete classes"
    - "The subject deletes its observers after each event"
    - "Observers share one database row"
  answer: 1
  explanation: "The subject holds interface-typed observers and calls their update method, staying decoupled from each implementation."
```
