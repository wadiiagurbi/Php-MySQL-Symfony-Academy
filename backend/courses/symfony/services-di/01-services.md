---
slug: services
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: services-di
chapterTitle: "Chapter 5 — Services & Dependency Injection"
chapterOrder: 5
title: "What Is a Service?"
difficulty: beginner
order: 1
estimatedMinutes: 10
tags: [symfony, services, architecture]
---

## Learning Objective

Understand what a **service** is in Symfony, how it differs from controllers and entities, and how to create and use a simple service class.

## Explanation

A **service** in Symfony is simply a PHP object that does one useful job for your application — sending emails, calculating prices, generating slugs — and is managed centrally so it can be reused anywhere. Technically, any class inside `src/` (for example `src/Service/`) is automatically registered as a service; there is no special base class to extend and no interface you must implement. What makes it a "service" is not its code, but the fact that Symfony's **service container** (a central registry object that creates services once and hands out the same shared instance wherever one is needed) knows about it and can deliver it on demand.

This differs from the two class types you already know. A **controller** handles one HTTP request and returns a response — it is the entry point, not reusable logic. An **entity** (a PHP class mapped by Doctrine, the database library, to a database table) describes the shape of stored data. A service holds the *behavior*: the rules and operations that work with that data but belong to neither a single web page nor a single database row. For example, a `TaskManager` service might contain the rule "a task title longer than 140 characters is rejected," callable from a web controller, a console command, and an API endpoint alike.

Keeping behavior in services gives you one place to fix bugs and one place to test. If slug generation lives in a `Slugger` service with its own unit test, every controller that uses it benefits — instead of three controllers each containing a slightly different copy of the same slug code drifting out of sync.

## Why It Matters

"Where should this code live?" is the most common design question on Symfony teams, and the default answer is "in a service." New features usually mean either creating a small service or adding a method to an existing one; controllers stay thin by delegating to services. Recognizing service-shaped code (reusable, request-independent logic) is what separates a beginner's tangled controller from a professional codebase.

## Example

Task titles entered by users need URL-friendly **slugs** (short lowercase identifiers like `write-twig-lesson` used in URLs). A `TaskSlugger` service centralizes that transformation so the web controller and the console import command share identical logic.

## Code

```php
<?php

namespace App\Service;

class TaskSlugger
{
    public function slugify(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
```

## Code Explanation

- `namespace App\Service;` places the class in `src/Service/`, where Symfony automatically registers every class as a service — no configuration needed.
- The class extends nothing and implements no interface; a plain PHP class with a clear job is all a service requires.
- `slugify(string $title): string` takes a raw title and returns the URL-friendly slug, using only standard PHP string functions.
- Because the logic lives here instead of inside a controller, any part of the app (controllers, commands, event listeners) can reuse the exact same transformation.
- This class is easy to unit-test in isolation: pass a string in, assert the slug out, with no HTTP requests or databases involved.

## Common Mistakes

- Putting business logic directly in controllers because "it is only used once." Features grow, and the second caller forces a painful extraction that a service would have avoided.
- Making services depend on the HTTP request (reading `$_GET` or requiring a `Request` object) for logic that has nothing to do with the web — this makes the service unusable from console commands and untestable without a fake request.
- Creating one giant `HelperService` or `Utils` class with dozens of unrelated methods. A service should have one clear responsibility, just like any well-designed class.

## Best Practices

- Give services focused names describing their job (`TaskSlugger`, `InvoiceCalculator`) rather than generic ones (`Helper`, `Manager`).
- Keep services independent of HTTP: pass plain values (strings, arrays, entities) as method arguments instead of request objects.
- Write a small unit test for each service — pure input/output logic like slug generation is the easiest code in your app to test, so there is no excuse to skip it.

## Exercise

Your app needs to greet users with "Hello, {name}!" in several places (web pages, console commands, emails). Describe what service class you would create: its name, its method signature, and why a service is the right home for this logic.

## Solution

Create an `App\Service\Greeter` class with a method like `greet(string $name): string` that returns `"Hello, {$name}!"`. A service is the right home because the greeting rule is reusable behavior unrelated to any single request or database row — every caller (controller, command, mailer) shares one implementation, one place to change the wording, and one straightforward unit test.

## Mini Quiz

```yaml
- question: "What is a service in Symfony?"
  options:
    - "A special server process that runs alongside PHP"
    - "A reusable PHP object (usually in src/) that performs one job and is managed by the service container"
    - "A database table that stores application settings"
    - "A JavaScript file loaded by Twig templates"
  answer: 1
  explanation: "A service is an ordinary PHP class doing one useful job; Symfony registers it centrally so any part of the app can reuse the same shared instance."
- question: "How does a service differ from a controller?"
  options:
    - "Services handle HTTP requests while controllers hold reusable logic"
    - "Controllers are entry points tied to one request/response cycle; services hold reusable behavior callable from anywhere"
    - "There is no difference between them"
    - "Services can only be used from the database layer"
  answer: 1
  explanation: "Controllers receive a request and return a response; services encapsulate request-independent logic that controllers (and commands, listeners, and more) delegate to."
- question: "Why keep slug generation in a TaskSlugger service instead of copying it into each controller?"
  options:
    - "Controllers cannot use string functions"
    - "One shared implementation means one place to fix bugs and test, instead of several copies drifting out of sync"
    - "Services run faster than controllers"
    - "PHP forbids duplicate code in controllers"
  answer: 1
  explanation: "Centralizing behavior in a service guarantees every caller uses identical logic and gives bugs and tests a single home."
```
