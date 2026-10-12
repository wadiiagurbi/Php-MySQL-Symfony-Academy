---
slug: event-dispatcher-listeners-and-subscribers
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: events
chapterTitle: "Chapter 13 — Events"
chapterOrder: 13
title: "The Event Dispatcher, Listeners, and Subscribers"
difficulty: advanced
order: 1
estimatedMinutes: 14
tags: [symfony, events, event-dispatcher, architecture]
---

## Learning Objective

Understand the general problem event-driven code solves, then learn how Symfony's Event Dispatcher, listeners, and subscribers let different parts of an application react to something happening without being directly coupled to each other.

## Explanation

**The general problem.** Imagine a `TaskService::update()` method that marks a task as `DONE`. Now imagine the business grows and suddenly three unrelated things need to happen whenever a task becomes done: send the owner a congratulatory email, update a "tasks completed" statistic on their profile, and notify a connected Slack webhook. The naive approach is to add all three directly inside `TaskService::update()`. This works today, but it has a real cost: `TaskService` — a class whose actual job is "manage tasks" — now also needs to know about email, statistics, and Slack. Every new reaction to "a task was completed" means editing this same method again, and testing `TaskService::update()` now requires mocking three unrelated concerns just to check that a task's status changed correctly.

The underlying problem is **coupling**: the code that *causes* something ("a task just became done") should not need to know, or care, *who reacts to it*. What you want instead is a way for `TaskService` to announce "a task was just completed" and let any number of independent, unrelated pieces of code — written now or added months later — react to that announcement, without `TaskService` ever importing or calling them directly. This general pattern (something happens → interested parties react, without the source knowing who they are) is called **event-driven** design, and it shows up constantly in software beyond Symfony (browser DOM events like `onClick` follow exactly this pattern: your button doesn't know or care what code runs when it's clicked).

**Symfony's implementation: the Event Dispatcher.** Symfony provides this pattern through its `EventDispatcherInterface`. The flow has three parts:

1. **An Event class** — a small, plain object carrying whatever data is relevant (e.g., a `TaskCompletedEvent` carrying the `Task` that was just completed).
2. **Dispatching the event** — the code that knows something happened (e.g., `TaskService`) calls `$eventDispatcher->dispatch(new TaskCompletedEvent($task))`, and its job is over. It has zero knowledge of what, if anything, happens next.
3. **Listening for the event** — separate classes register themselves as interested in that event type, and Symfony calls them automatically whenever it is dispatched.

There are two conventional ways to write a listener in Symfony 6.4:

- **`#[AsEventListener]` attribute** — put this attribute directly on a class's `__invoke()` method (or any method), specifying the event, and Symfony auto-registers it. This is the simplest style for a class that listens to one event, exactly like this project's real `ApiExceptionListener`, which listens for Symfony's built-in `kernel.exception` event and turns any exception on an `/api/*` route into a clean JSON error response.
- **`EventSubscriberInterface`** — implement this interface's static `getSubscribedEvents()` method, returning an array mapping event names to method names. This style is preferred when one class needs to listen to *several* related events, or when you want the list of subscribed events declared explicitly in one place rather than scattered across attributes.

Both styles are registered automatically by Symfony (no manual configuration needed) as long as the class is a service — a detail you have already seen with controllers and commands using attributes instead of separate config files.

## Why It Matters

The Event Dispatcher is how Symfony itself is built internally — nearly every "hook point" in the framework (a request coming in, a response about to be sent, an exception being thrown, a security check happening) is implemented as an event that framework and application code alike can listen to, without Symfony's core code needing to know about your application at all. Learning this pattern is not just "one more Symfony feature" — it is the mechanism that keeps a growing application's cross-cutting concerns (logging, notifications, cache invalidation, auditing) from turning every core service into an ever-growing pile of unrelated side effects. Teams that never learn this pattern tend to end up with exactly the fat-service and fat-controller problems this course's final chapter examines directly.

## Example

`ApiExceptionListener`, already present in this project, listens for Symfony's built-in `kernel.exception` event using `#[AsEventListener(event: 'kernel.exception')]`. Whenever *any* code, anywhere in the application, throws an uncaught exception during an `/api/*` request, Symfony dispatches this event automatically, and `ApiExceptionListener::__invoke()` runs, inspecting the exception and calling `$event->setResponse(...)` to replace Symfony's default HTML error page with a clean JSON body. No controller anywhere needs a `try`/`catch` block for this to work — the listener reacts centrally, once, to an event dispatched by the framework itself.

## Code

```php
<?php

namespace App\Event;

use App\Entity\Task;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched whenever a task transitions to DONE. Carries just enough data
 * for any listener to react without needing to reload anything itself.
 */
final class TaskCompletedEvent extends Event
{
    public function __construct(public readonly Task $task)
    {
    }
}
```

```php
<?php

namespace App\Service;

use App\DTO\TaskRequest;
use App\Entity\Task;
use App\Entity\TaskStatus;
use App\Event\TaskCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function update(Task $task, TaskRequest $request): Task
    {
        $wasAlreadyDone = TaskStatus::DONE === $task->getStatus();

        $task->setTitle($request->title);
        $task->setStatus(TaskStatus::from($request->status));

        $this->entityManager->flush();

        if (!$wasAlreadyDone && TaskStatus::DONE === $task->getStatus()) {
            $this->eventDispatcher->dispatch(new TaskCompletedEvent($task));
        }

        return $task;
    }
}
```

```php
<?php

namespace App\EventListener;

use App\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TaskCompletedEvent::class)]
class LogTaskCompletionListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(TaskCompletedEvent $event): void
    {
        $this->logger->info('Task completed', [
            'taskId' => $event->task->getId(),
            'title' => $event->task->getTitle(),
        ]);
    }
}
```

## Code Explanation

- `final class TaskCompletedEvent extends Event` — extending Symfony's base `Event` class marks this as a proper event object; the `public readonly Task $task` property is the only data listeners need, kept intentionally small.
- `TaskService`'s constructor now injects `EventDispatcherInterface` alongside `EntityManagerInterface` — dispatching an event is just another method call on an injected service, no special setup required.
- The `$wasAlreadyDone` check ensures the event only fires on the actual *transition* into `DONE`, not on every update to an already-done task — a detail easy to miss and important for correctness.
- `$this->eventDispatcher->dispatch(new TaskCompletedEvent($task));` is the entire extent of what `TaskService` knows about anything reacting to completion — it has no reference to logging, email, or Slack, and adding a fourth listener later requires zero changes to this method.
- `#[AsEventListener(event: TaskCompletedEvent::class)]` on `LogTaskCompletionListener` registers it for this specific custom event, the same attribute style used by the real `ApiExceptionListener`, just pointed at a custom event class instead of Symfony's built-in `kernel.exception`.
- `__invoke(TaskCompletedEvent $event)` — Symfony infers which event this listener should receive from the type-hint on its single parameter, so the event class does not need to be repeated if you omit the `event:` argument on newer Symfony versions (this project's style, following `ApiExceptionListener`, states it explicitly for clarity).

## Common Mistakes

- Dispatching an event but forgetting that dispatching does nothing by itself — an event with zero registered listeners simply does nothing, silently. This is expected behavior, not a bug, but it can be confusing when first learning the pattern (you dispatch, nothing visibly happens, and it's easy to assume something is broken).
- Putting business logic *inside* the event object itself (e.g., a method on `TaskCompletedEvent` that sends an email) — an event should be a plain data carrier; the reaction belongs in a listener.
- Using `EventSubscriberInterface` for a single, simple, one-event listener when `#[AsEventListener]` would be far less boilerplate — reserve `EventSubscriberInterface` for classes that genuinely need to react to multiple related events in one place.
- Forgetting that listener execution order matters when multiple listeners subscribe to the same event and one depends on another having already run; both listener styles support an explicit `priority` you can set if ordering matters.

## Best Practices

- Name events for what *happened*, in the past tense (`TaskCompletedEvent`, `UserRegisteredEvent`), not for what should happen next — the event describes a fact, not an instruction.
- Keep event objects small and immutable (`readonly` properties) — they are a snapshot of "what happened," not a place to accumulate mutable state as listeners run.
- Prefer dispatching your own domain events (like `TaskCompletedEvent`) over hooking business logic onto Symfony's low-level kernel events (like `kernel.response`) — your own events describe things in your application's own vocabulary, which stays meaningful as the app grows.
- Keep each listener focused on one reaction (logging, emailing, updating a stat) rather than one giant listener doing everything for an event — this mirrors the same single-responsibility thinking you already apply to services.

## Exercise

Sketch (class + `__invoke` signature only, no need for real email-sending code) a second listener, `SendCompletionEmailListener`, that reacts to the same `TaskCompletedEvent` used above but sends an email to the task's owner instead of logging. Use the `#[AsEventListener]` attribute style.

## Solution

```php
<?php

namespace App\EventListener;

use App\Event\TaskCompletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;

#[AsEventListener(event: TaskCompletedEvent::class)]
class SendCompletionEmailListener
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function __invoke(TaskCompletedEvent $event): void
    {
        // Build and send an email to $event->task->getOwner() using $this->mailer.
    }
}
```

Because both `LogTaskCompletionListener` and `SendCompletionEmailListener` independently listen for `TaskCompletedEvent`, `TaskService::update()` does not change at all to support this second reaction — this is exactly the decoupling the event dispatcher exists to provide.

## Mini Quiz

```yaml
- question: "What general problem does event-driven code (dispatching an event and having listeners react) solve?"
  options:
    - "It makes PHP code run faster"
    - "It lets code that causes something to happen announce that fact without needing to know or call whichever other code wants to react to it"
    - "It replaces the need for services entirely"
    - "It is only useful for handling HTTP exceptions"
  answer: 1
  explanation: "Event-driven design decouples the code that triggers something from the code that reacts to it, so reactions can be added or removed independently without modifying the original triggering code."

- question: "In this project's real ApiExceptionListener, which Symfony event does it listen for, and using which registration style?"
  options:
    - "TaskCompletedEvent, using EventSubscriberInterface"
    - "kernel.exception, using the #[AsEventListener] attribute"
    - "kernel.request, using a YAML configuration file"
    - "kernel.response, using a manual service registration"
  answer: 1
  explanation: "ApiExceptionListener uses #[AsEventListener(event: 'kernel.exception')] to react to Symfony's built-in exception event and convert it into a JSON response for API routes."

- question: "When is EventSubscriberInterface generally preferred over the #[AsEventListener] attribute?"
  options:
    - "Never; the attribute style is always strictly better"
    - "When a single class needs to react to several related events, declared together in one getSubscribedEvents() method"
    - "Only when using PHP versions older than 8"
    - "Only for events dispatched by third-party bundles"
  answer: 1
  explanation: "EventSubscriberInterface is useful when one class subscribes to multiple events, since getSubscribedEvents() centralizes that mapping in one place rather than spreading multiple attributes across methods."

- question: "What should a custom event class like TaskCompletedEvent typically contain?"
  options:
    - "The logic for every possible reaction to the event"
    - "A database connection for listeners to query directly"
    - "Just the data relevant to what happened (e.g., the completed Task), kept small and immutable"
    - "A list of all registered listener class names"
  answer: 2
  explanation: "An event object should be a lightweight, immutable data carrier describing what happened; the reactions themselves belong in separate listener classes, not in the event."
```
