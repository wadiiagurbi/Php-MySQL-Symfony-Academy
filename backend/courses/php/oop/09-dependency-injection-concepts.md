---
slug: dependency-injection-concepts
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Dependency Injection Concepts"
difficulty: intermediate
order: 9
estimatedMinutes: 12
tags: [php, oop, dependency-injection]
---

## Learning Objective

Understand what a "dependency" is in OOP, why creating dependencies with `new` directly inside a class causes real problems, and how passing dependencies in from outside (dependency injection) solves them — using plain PHP, with no framework involved.

## Explanation

A **dependency** is simply another class (or object) that a class needs in order to do its job. If a class `OrderProcessor` needs to send a confirmation email, and sending email is handled by a separate class `EmailService`, then `OrderProcessor` *depends on* `EmailService` — it cannot do its full job without it.

The most obvious way to get access to a dependency is to create it yourself, right there inside the class that needs it, using `new`:

```php
class OrderProcessor
{
    public function process(Order $order): void
    {
        $mailer = new EmailService(); // created directly, inside the method
        $mailer->send($order->customerEmail, "Your order is confirmed");
    }
}
```

This works, and it is exactly the kind of code a beginner naturally writes first. But it causes real, recurring problems as a codebase grows:

1. **Hard to swap.** If you later want `OrderProcessor` to use a different way of sending notifications (say, `SmsService` instead of, or in addition to, `EmailService`), you have to open up `OrderProcessor` and edit its internals — even though the actual *order processing logic* hasn't changed at all.
2. **Hard to test.** If `EmailService` actually sends real emails over the network, then every single test of `OrderProcessor` would also send real emails, which is slow, unreliable, and undesirable. Because `OrderProcessor` creates its own `EmailService` internally, there is no way for a test to substitute a fake, safe version instead.
3. **Hidden requirements.** Looking at `OrderProcessor`'s constructor (or its list of properties) tells you nothing about what it needs — the dependency is buried inside a method body, discoverable only by reading every line of code.

**Dependency injection** solves this by flipping who is responsible for creating the dependency. Instead of a class creating what it needs internally, the dependency is created *outside* the class and handed ("injected") in — almost always through the constructor:

```php
class OrderProcessor
{
    public function __construct(private EmailService $mailer)
    {
    }

    public function process(Order $order): void
    {
        $this->mailer->send($order->customerEmail, "Your order is confirmed");
    }
}
```

Now `OrderProcessor` no longer decides *how* its `EmailService` is built — it just declares, through its constructor, "I need something that can send things via `EmailService`," and whoever creates an `OrderProcessor` is responsible for supplying one. This is often called "constructor injection," the most common form of dependency injection. The dependency is now visible right in the constructor signature, it can be swapped for a different `EmailService` configuration without touching `OrderProcessor` at all, and — crucially for testing — a test can construct `OrderProcessor` with a fake, harmless stand-in object instead of a real emailing class.

Note that this lesson deliberately stays in plain PHP. You are manually writing `new OrderProcessor(new EmailService())` yourself. Later in this course, when you reach Symfony, you'll learn about its **service container**, a framework feature that automates this wiring for you across an entire application — but the underlying idea of "pass dependencies in, don't create them internally" is exactly what you are learning right now.

## Why It Matters

Dependency injection is one of the most important habits separating beginner PHP code from professional, maintainable PHP code. Nearly every real Symfony application is built around constructor injection: controllers, services, and repositories all declare their dependencies as constructor parameters, and Symfony's service container supplies them automatically at runtime. If you don't understand why this pattern exists in plain PHP first, Symfony's automatic wiring will feel like unexplainable magic. Beyond Symfony, this pattern is also what makes automated testing realistic for OOP code — without it, testing anything meaningfully sized becomes painful or impossible.

## Example

An `OrderProcessor` that depends on something capable of sending notifications. Built with dependency injection, it can work with a real `EmailService` in production and a `FakeMailer` in a test, without changing a single line inside `OrderProcessor`.

## Code

```php
<?php

class EmailService
{
    public function send(string $to, string $message): void
    {
        echo "Emailing {$to}: {$message}\n";
    }
}

class OrderProcessor
{
    // The dependency is injected through the constructor, not created inside.
    public function __construct(private EmailService $mailer)
    {
    }

    public function process(string $customerEmail): void
    {
        // OrderProcessor just uses the dependency; it has no idea how it was built.
        $this->mailer->send($customerEmail, "Your order is confirmed");
    }
}

// The caller is responsible for building the dependency and handing it in.
$mailer = new EmailService();
$processor = new OrderProcessor($mailer);

$processor->process("alice@example.com");
```

## Code Explanation

- `class EmailService` is the dependency: a class that knows how to perform one job (sending a message).
- `public function __construct(private EmailService $mailer)` declares that `OrderProcessor` requires an `EmailService` to be constructed, and stores it as a private property called `mailer`, all in one line using constructor property promotion.
- Inside `process()`, `$this->mailer->send(...)` uses the injected dependency — `OrderProcessor` never writes `new EmailService()` itself.
- `$mailer = new EmailService();` and `$processor = new OrderProcessor($mailer);` show the dependency being built *outside* `OrderProcessor` and passed in explicitly when the object is created.
- Because the dependency is injected, you could substitute `new OrderProcessor($someOtherMailerObject)` — as long as that object has a compatible `send()` method (ideally guaranteed with an interface, as covered in lesson 7) — without changing `OrderProcessor`'s code at all.

## Common Mistakes

- Creating dependencies with `new` inside methods or the constructor body of the class that needs them, which silently reintroduces the exact problems dependency injection is meant to avoid.
- Injecting so many dependencies that a constructor has seven or eight parameters — often a sign the class is doing too much and should be split into smaller, more focused classes.
- Forgetting that dependency injection is just a pattern, not a specific PHP keyword or feature — it's simply "receive what you need as a parameter instead of manufacturing it internally."
- Assuming dependency injection requires a framework. As this lesson shows, it works perfectly well in plain PHP; frameworks like Symfony only automate the wiring, they don't invent the concept.

## Best Practices

- Prefer injecting dependencies through the constructor rather than through setter methods called after construction — this guarantees an object is never left in a state where a required dependency is missing.
- Depend on an interface (see lesson 7) rather than a concrete class whenever practical, so the dependency can be swapped for any compatible implementation, including fakes used in tests.
- Keep the class responsible for *building* the dependency graph (like the code at the bottom of the example, creating `$mailer` and `$processor`) separate from the classes that *use* the dependencies — don't mix construction logic into business logic.

## Exercise

Create a `Logger` class with a method `log(string $message): void` that echoes the message prefixed with `"[LOG] "`. Create a `ReportGenerator` class that depends on a `Logger`, injected through its constructor, with a method `generate(string $reportName): void` that logs a message like `"Generating report: {$reportName}"` using the injected logger. Wire it all together by creating a `Logger`, injecting it into a `ReportGenerator`, and calling `generate()`.

## Solution

```php
<?php

class Logger
{
    public function log(string $message): void
    {
        echo "[LOG] {$message}\n";
    }
}

class ReportGenerator
{
    public function __construct(private Logger $logger)
    {
    }

    public function generate(string $reportName): void
    {
        $this->logger->log("Generating report: {$reportName}");
    }
}

$logger = new Logger();
$reportGenerator = new ReportGenerator($logger);
$reportGenerator->generate("Monthly Sales");
```

`ReportGenerator` never creates its own `Logger` — it receives one through its constructor and stores it as `$this->logger`. Because the dependency is injected, a test could pass in a fake logger that just records messages in an array instead of printing them, without needing to modify `ReportGenerator` at all.

## Mini Quiz

```yaml
- question: "What is a 'dependency' in the context of OOP?"
  options:
    - "Any variable declared inside a method"
    - "Another class or object that a class needs in order to do its job"
    - "A syntax error caused by missing a semicolon"
    - "A property that has no default value"
  answer: 1
  explanation: "A dependency is another class or object that a given class relies on to perform its work, such as OrderProcessor relying on EmailService to send notifications."

- question: "What is the main problem with a class creating its dependencies internally using 'new'?"
  options:
    - "PHP does not allow using 'new' inside a class method"
    - "It makes the dependency impossible to swap or replace with a fake version for testing"
    - "It makes the code run more slowly at runtime"
    - "It causes a syntax error at parse time"
  answer: 1
  explanation: "When a class builds its own dependencies internally, there is no way to substitute a different implementation (like a test fake), and any change to how the dependency is built requires editing the class itself."

- question: "What is 'constructor injection'?"
  options:
    - "Injecting a dependency by editing the class file directly at runtime"
    - "Passing a class's required dependencies in as constructor parameters, rather than creating them inside the class"
    - "A PHP built-in function for creating objects"
    - "A way to inject SQL queries into a database"
  answer: 1
  explanation: "Constructor injection means a class receives its dependencies as constructor parameters supplied by whoever creates the object, instead of instantiating them internally."
```
