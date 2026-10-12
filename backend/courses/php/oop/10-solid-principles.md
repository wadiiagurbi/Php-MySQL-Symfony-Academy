---
slug: solid-principles
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "SOLID Principles"
difficulty: intermediate
order: 10
estimatedMinutes: 14
tags: [php, oop, solid, design-principles]
---

## Learning Objective

Get a working, plain-English introduction to each of the five SOLID principles, with a small PHP example for each, so you can recognize them in real code and start applying them in your own designs.

## Explanation

SOLID is an acronym for five design guidelines that experienced object-oriented programmers follow to keep classes flexible, understandable, and easy to change safely. You already have all the vocabulary you need (classes, interfaces, inheritance, dependency injection) from earlier lessons in this chapter — SOLID is really just "here's how to use those tools well."

**S — Single Responsibility Principle.** A class should have only one reason to change, meaning it should do one clear job. A class named `Invoice` that both calculates totals *and* saves itself to a file *and* sends an email has three unrelated reasons to change, and a change to how emails are sent risks accidentally breaking invoice calculation. Splitting responsibilities into `Invoice`, `InvoiceRepository`, and `InvoiceMailer` means each class changes only when its one job changes.

**O — Open/Closed Principle.** Code should be open for extension but closed for modification — meaning you should be able to add new behavior without editing and risking existing, already-tested code. This is exactly what interfaces (lesson 7) enable: a function that accepts any `Payable` can support a brand-new `SubscriptionCharge` class tomorrow without a single line inside that function changing.

**L — Liskov Substitution Principle.** Any subclass should be usable anywhere its parent class is expected, without breaking things. If code expects a `Shape` and calls `->area()`, substituting any subclass (`Circle`, `Rectangle`) must behave sensibly and honor what callers reasonably expect from a `Shape`. If a subclass overrides a method in a way that violates the parent's basic promises (for example, a `Bird` subclass `Penguin` that throws an error from a `fly()` method inherited from `Bird`), that violates this principle.

**I — Interface Segregation Principle.** Prefer several small, focused interfaces over one large interface that forces classes to implement methods they don't actually need. A single bloated `WorkerInterface` requiring `code()`, `design()`, and `manageBudget()` would force a `Developer` class to awkwardly implement `manageBudget()` even though writing code has nothing to do with budgets. Splitting it into `CanCode`, `CanDesign`, and `CanManageBudget` lets each class implement only what genuinely applies to it.

**D — Dependency Inversion Principle.** High-level code (the part expressing your application's important logic) should depend on abstractions (interfaces), not on low-level concrete details. This is the natural conclusion of dependency injection (lesson 9): `OrderProcessor` should depend on a `Mailer` interface, not directly on a concrete `SmtpEmailService` class, so that swapping the underlying mail transport never requires changing `OrderProcessor`.

Together, these five ideas support each other: dependency injection plus interfaces (D) enables the Open/Closed principle (O), which relies on subclasses genuinely behaving like their parents (L), built from small, purposeful interfaces (I), inside classes that each do one clear job (S).

## Why It Matters

SOLID is not academic trivia — it is the shared vocabulary professional PHP developers use to explain *why* a piece of code is hard to change, and to justify a specific refactor in a code review ("this violates single responsibility, let's split it"). Symfony's own architecture is a showcase of SOLID in action: its service container is built entirely around dependency inversion, its event system relies on small, focused interfaces, and its own core classes are deliberately kept narrow in responsibility. Understanding SOLID now means you'll recognize *why* Symfony is structured the way it is, rather than experiencing it as arbitrary framework conventions.

## Example

Five tiny, separate examples — one per letter — each showing the problem and the SOLID-aligned fix in a sentence or two of code, focused on clarity rather than being one large connected system.

## Code

```php
<?php

// S — Single Responsibility: Invoice only calculates; saving/emailing live elsewhere.
class Invoice
{
    public function __construct(private float $subtotal, private float $taxRate)
    {
    }

    public function total(): float
    {
        return $this->subtotal * (1 + $this->taxRate);
    }
}

// O — Open/Closed: new discount types can be added without touching this function.
interface Discount
{
    public function apply(float $amount): float;
}

class PercentageDiscount implements Discount
{
    public function __construct(private float $percent)
    {
    }

    public function apply(float $amount): float
    {
        return $amount * (1 - $this->percent / 100);
    }
}

function applyDiscount(float $amount, Discount $discount): float
{
    return $discount->apply($amount); // Works with ANY current or future Discount.
}

// L — Liskov Substitution: any Shape subclass must work wherever Shape is expected.
abstract class Shape
{
    abstract public function area(): float;
}

class Square extends Shape
{
    public function __construct(private float $side)
    {
    }

    public function area(): float
    {
        return $this->side ** 2; // Behaves sensibly, just like any other Shape.
    }
}

// I — Interface Segregation: small, focused interfaces instead of one bloated one.
interface CanCode
{
    public function writeCode(): string;
}

class Developer implements CanCode
{
    public function writeCode(): string
    {
        return "Writing code...";
    }
}

// D — Dependency Inversion: OrderNotifier depends on an abstraction, not a concrete class.
interface Mailer
{
    public function send(string $to, string $message): void;
}

class OrderNotifier
{
    public function __construct(private Mailer $mailer)
    {
    }

    public function notify(string $customerEmail): void
    {
        $this->mailer->send($customerEmail, "Order confirmed");
    }
}

echo (new Invoice(100, 0.2))->total() . "\n";
echo applyDiscount(50.0, new PercentageDiscount(10)) . "\n";
echo (new Square(4))->area() . "\n";
echo (new Developer())->writeCode() . "\n";
```

## Code Explanation

- `Invoice` only computes a total; it has no save-to-file or send-email logic mixed in, satisfying Single Responsibility.
- `applyDiscount(float $amount, Discount $discount)` depends only on the `Discount` interface. Adding a new `FlatAmountDiscount implements Discount` tomorrow requires zero changes to `applyDiscount` — this is the Open/Closed principle in action.
- `Square extends Shape` and correctly implements `area()` behaving exactly as any caller expecting a `Shape` would assume — no surprises, no broken promises — demonstrating Liskov Substitution.
- `CanCode` is a small, single-purpose interface; `Developer` only implements what it actually needs, illustrating Interface Segregation instead of forcing it to implement unrelated abilities.
- `OrderNotifier` depends on the `Mailer` interface in its constructor, not on any specific concrete mail-sending class, which is Dependency Inversion combined with the constructor injection from lesson 9.

## Common Mistakes

- Treating SOLID as strict rules to apply everywhere immediately, even in tiny scripts — over-engineering a five-line script into six classes and three interfaces is itself a mistake; SOLID pays off as complexity grows.
- Confusing Liskov Substitution with "any subclass compiles without error." The principle is about *behavioral* correctness — a subclass that technically overrides a method but violates its intended meaning still breaks the principle even if PHP raises no error.
- Building one giant interface "to be safe" instead of several small ones, which then forces unrelated classes to implement methods that make no sense for them.

## Best Practices

- Use Single Responsibility as a quick smell test: if you struggle to describe a class's job in one sentence without using "and," it probably has more than one responsibility.
- Reach for interfaces plus dependency injection together (Dependency Inversion) as your default way of connecting classes that represent different concerns, rather than hard-coding concrete class names throughout your logic.
- Apply SOLID gradually as a codebase grows and pain points appear (hard to test, hard to extend) rather than as an upfront checklist for every single class from day one.

## Exercise

Take this poorly designed class and identify which SOLID principle it violates, then fix it:

```php
class ReportManager
{
    public function generateAndEmailReport(array $data, string $email): void
    {
        $report = "Report: " . implode(", ", $data);
        echo "Emailing to {$email}: {$report}\n"; // Sending email directly inside.
    }
}
```

## Solution

This violates the Single Responsibility Principle: `ReportManager` both builds report content and sends email, meaning a change to email logic risks affecting report generation and vice versa. The fix splits the responsibilities:

```php
<?php

class ReportBuilder
{
    public function build(array $data): string
    {
        return "Report: " . implode(", ", $data);
    }
}

interface Mailer
{
    public function send(string $to, string $message): void;
}

class SimpleMailer implements Mailer
{
    public function send(string $to, string $message): void
    {
        echo "Emailing to {$to}: {$message}\n";
    }
}

class ReportManager
{
    public function __construct(private ReportBuilder $builder, private Mailer $mailer)
    {
    }

    public function generateAndEmailReport(array $data, string $email): void
    {
        $report = $this->builder->build($data);
        $this->mailer->send($email, $report);
    }
}

$manager = new ReportManager(new ReportBuilder(), new SimpleMailer());
$manager->generateAndEmailReport(["Sales up 10%", "New customers: 50"], "boss@example.com");
```

Now `ReportBuilder` only builds report text, `SimpleMailer` (behind the `Mailer` interface) only sends messages, and `ReportManager` coordinates the two via injected dependencies — each piece has one clear job and can be changed or replaced independently.

## Mini Quiz

```yaml
- question: "Which SOLID principle is violated when a single class both calculates data AND sends emails AND saves files?"
  options:
    - "Liskov Substitution Principle"
    - "Single Responsibility Principle"
    - "Interface Segregation Principle"
    - "Open/Closed Principle"
  answer: 1
  explanation: "A class handling multiple unrelated jobs has more than one reason to change, which is exactly what the Single Responsibility Principle warns against."

- question: "What does the Open/Closed Principle recommend?"
  options:
    - "Code should be open for extension (adding new behavior) but closed for modification (not editing existing, working code)"
    - "All class properties should be declared public"
    - "Every class must have exactly one method"
    - "Classes should never use inheritance"
  answer: 0
  explanation: "Open/Closed means you should be able to add new functionality, often via new classes implementing an existing interface, without altering already-working code."

- question: "What is the core idea behind the Dependency Inversion Principle?"
  options:
    - "Dependencies should always be created with the 'new' keyword inside the class that needs them"
    - "High-level code should depend on abstractions (like interfaces) rather than on specific concrete implementations"
    - "A class should never have more than one dependency"
    - "Inheritance should be avoided entirely in favor of interfaces"
  answer: 1
  explanation: "Dependency Inversion says important application logic should rely on abstract contracts (interfaces) rather than being tightly coupled to specific concrete classes, making it easier to swap implementations."

- question: "Why would a single large interface requiring writeCode(), designUI(), and manageBudget() be a problem for a Developer class?"
  options:
    - "PHP does not allow classes to implement interfaces with more than one method"
    - "It forces Developer to implement methods unrelated to its actual job, violating Interface Segregation"
    - "It would cause a fatal syntax error"
    - "It violates the Single Responsibility Principle instead of Interface Segregation"
  answer: 1
  explanation: "Interface Segregation recommends small, focused interfaces so classes only need to implement methods genuinely relevant to them, rather than being forced to fulfill an oversized contract."
```
