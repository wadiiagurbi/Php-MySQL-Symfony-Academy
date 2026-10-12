---
slug: service-container-and-config
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: services-di
chapterTitle: "Chapter 5 — Services & Dependency Injection"
chapterOrder: 5
title: "The Service Container and Config"
difficulty: intermediate
order: 3
estimatedMinutes: 12
tags: [symfony, services, container, configuration]
---

## Learning Objective

Understand what the **service container** does behind the scenes, how **autowiring** and **autoconfiguration** remove manual setup, and how `services.yaml` controls which classes become services.

## Explanation

The **service container** is the central PHP object that knows how to build every service in your application. At its simplest, it is a smart factory: when code needs a `TaskSluggerController`, the container inspects its constructor, sees it needs a `TaskSlugger`, builds that first (recursively resolving *its* dependencies), and then hands over the finished controller. Each service is built once and **shared** by default, meaning every part of the app receives the same instance — efficient and consistent.

Two features make this feel like magic. **Autowiring** matches constructor type-hints to service classes automatically: because there is exactly one service of class `TaskSlugger`, a `TaskSlugger $slugger` parameter resolves with zero configuration. **Autoconfiguration** automatically applies the right tags and setup based on what a class implements — for example, a class implementing `EventSubscriberInterface` (an interface marking "I want to listen to framework events," covered in the Events chapter) is registered as an event subscriber without you declaring anything.

The behavior lives in `config/services.yaml`, a **YAML** (**YAML Ain't Markup Language** — the human-readable configuration format Symfony uses for service, route, and package settings) file. Its `services:` section declares defaults (`autowire: true`, `autoconfigure: true`) and one **resource glob** (a pattern like `App\` mapped to `src/` that bulk-registers every class underneath as a service). You rarely add entries for your own classes; you edit this file mainly to *exclude* things (entities and tests are not services) or to bind scalar values like strings that autowiring cannot guess.

You can inspect the whole system with `php bin/console debug:container`, which lists every registered service, and `debug:autowiring` for injectable type-hints — the fastest way to answer "why can't Symfony build my class?"

## Why It Matters

Container errors ("Cannot autowire argument ...") are among the first confusing walls beginners hit, and they always trace back to these concepts: a missing type-hint, a class outside the registered resource path, or an interface with two implementations and no alias. Knowing how `services.yaml` registers classes turns a scary exception into a five-minute fix, and `debug:container` becomes your go-to diagnostic on any unfamiliar project.

## Example

A notification feature needs a `TaskNotifier` service that depends on the framework's mailer. Autowiring builds the whole chain from type-hints alone, and `debug:container` confirms the new service is registered and shared.

## Code

```php
<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;

class TaskNotifier
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function notifyOwner(string $email, string $taskTitle): void
    {
        // In a real app you would build an Email object here
        // and send it with $this->mailer->send($email).
        // The container injects the configured mailer automatically.
    }
}
```

## Code Explanation

- `namespace App\Service;` puts the class under the `App\` resource glob in `services.yaml`, so it is registered as a service with no manual entry.
- `MailerInterface $mailer` is an interface type-hint: Symfony ships an **alias** (a container rule saying "when someone asks for this interface, give them that concrete service") pointing it at the configured mailer, so autowiring still resolves it.
- The container builds `TaskNotifier` by first resolving the mailer service, then passing it into the constructor — recursive dependency resolution in action.
- The service is shared by default: every controller injecting `TaskNotifier` receives the same instance during a request.
- Running `php bin/console debug:container TaskNotifier` would show the service id, class, and that it is shared and autowired.

## Common Mistakes

- Placing a service class outside `src/` (or another registered resource path) and wondering why autowiring cannot find it — the container only knows about classes the `services.yaml` globs cover.
- Type-hinting an interface with two implementations and getting a "multiple candidates" error. Autowiring needs exactly one match; you must define an alias naming the default.
- Manually instantiating container-managed classes with `new` in production code and bypassing the container — you lose shared instances, configuration, and injected dependencies.

## Best Practices

- Leave `autowire: true` and `autoconfigure: true` on, and let the `App\` resource glob register your classes — add explicit service entries only for special cases (extra arguments, non-standard factories).
- Use `debug:container` and `debug:autowiring` to verify registrations instead of guessing why an injection fails.
- Keep `services.yaml` tidy: defaults at the top, resource registration next, and manual definitions with a comment explaining *why* each one cannot be autowired.

## Exercise

You create `src/Service/WeeklyDigest.php` with constructor `__construct(private TaskRepository $tasks, private MailerInterface $mailer)`. Explain step by step how the container builds it on first use, and name the console command that verifies it is registered.

## Solution

The class is auto-registered by the `App\` resource glob. On first request, the container reads the constructor type-hints, builds (or reuses) the shared `TaskRepository` and the aliased mailer, and passes both into `new WeeklyDigest(...)`. Run `php bin/console debug:container WeeklyDigest` to confirm the service id, class, and wiring.

## Mini Quiz

```yaml
- question: "What does the service container do?"
  options:
    - "It stores uploaded files on disk"
    - "It centrally builds services by resolving constructor dependencies and hands out shared instances"
    - "It compiles Twig templates into PHP"
    - "It runs database migrations in order"
  answer: 1
  explanation: "The container is a smart factory: it recursively resolves each class's constructor type-hints, builds the object graph once, and shares instances across the app."
- question: "What is autowiring?"
  options:
    - "Automatically generating route URLs in Twig"
    - "Automatically matching constructor type-hints to registered services without manual configuration"
    - "Automatically hashing user passwords"
    - "Automatically deploying code to production"
  answer: 1
  explanation: "With autowiring enabled, Symfony reads the types in __construct() and injects the matching services, so most classes need no explicit service definition."
- question: "What is config/services.yaml mainly responsible for?"
  options:
    - "Defining database table schemas"
    - "Declaring autowiring/autoconfiguration defaults and which class namespaces are registered as services"
    - "Writing Twig templates for error pages"
    - "Storing user session data"
  answer: 1
  explanation: "services.yaml sets container defaults and bulk-registers classes (e.g. everything under App\\ in src/) as services, with manual entries only for special cases."
```
