---
slug: keeping-controllers-clean
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: controllers
chapterTitle: "Chapter 3 — Controllers"
chapterOrder: 3
title: "Keeping Controllers Clean"
difficulty: intermediate
order: 3
estimatedMinutes: 11
tags: [symfony, controllers, architecture, best-practices]
---

## Learning Objective

Understand why business logic should not live inside controller methods, what a "thin controller" looks like in practice, and how this idea sets up the concept of services covered in the next chapter.

## Explanation

Every controller example so far in this chapter has been intentionally small: read some input, do a trivial check, return a response. Real applications are tempted to grow controllers far beyond this — adding database queries, validation rules, calculations, and calls to third-party APIs directly inside the controller method, because it's the file you're already looking at and it "just works." This leads to what's commonly called a **fat controller**: a controller method doing far more than its actual job, which is only to receive a request and produce a response.

Recall the Model-View-Controller separation from Chapter 1: the Controller's job is to be the *coordinating glue* between the request and the Model (your data and business rules) — not to *contain* the business rules itself. When a controller method directly runs a complex SQL-like query, calculates a discount, or validates intricate business rules, that logic becomes trapped inside a class that's only reachable through an HTTP request. This causes several concrete problems:

- **You can't reuse the logic.** If you need the same "mark a task overdue" logic from both an API endpoint and a scheduled background command (a real, common need), duplicating the logic across a controller and a command class is how bugs creep in — one gets fixed, the other doesn't.
- **You can't easily test the logic in isolation.** Testing business logic buried in a controller method usually means simulating an entire fake HTTP request just to exercise a calculation that has nothing to do with HTTP at all.
- **The controller becomes hard to read.** A method mixing "extract input," "validate," "query database," "apply business rules," and "format output" in one block is difficult to scan and modify safely.

The fix is to extract non-HTTP-related logic into separate, plain PHP classes — commonly called **services** — that contain the actual business logic, and have the controller simply call into them. A service class has no idea it's being used from a web request at all; it just takes plain PHP values in and returns plain PHP values (or objects) out, which makes it trivially reusable and testable on its own. You will study exactly how to build and use services, and how Symfony makes them available inside your controllers, in the very next chapter — this lesson is deliberately previewing *why* that pattern exists before you learn its mechanics.

A useful rule of thumb: a controller method should read almost like a short, readable summary of "get the input, ask something else to do the real work, return a response" — if you can't summarize a controller method in one sentence without the word "and" appearing three times, it's probably doing too much itself.

## Why It Matters

This is one of the most common code-review comments on real Symfony pull requests: "this business logic should be extracted into a service." Teams that let logic accumulate directly in controllers eventually face APIs that are difficult to change safely, tests that are slow and brittle (because testing anything requires spinning up a fake HTTP request), and duplicated logic scattered across multiple controllers that drifts out of sync over time. Recognizing "this doesn't belong here" early — even before you know exactly where it *should* go — is a mark of someone who understands Symfony's architecture, not just its syntax.

## Example

Consider a task-tracking API endpoint that marks a task complete, and — as a business rule — should also check whether all tasks in the same project are now complete, and if so, mark the project itself as "finished." Cramming this cascading logic directly into the controller method mixes HTTP concerns with a business rule that has nothing to do with HTTP.

## Code

```php
<?php

namespace App\Controller\Api;

use App\Service\TaskCompletionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    // The controller stays "thin": read input, delegate to a service, return a response.
    // TaskCompletionService is a plain PHP class (a "service") containing the actual
    // business rule — you'll learn exactly how it gets here in the next chapter.
    #[Route('/api/tasks/{id}/complete', name: 'api_tasks_complete', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function complete(int $id, TaskCompletionService $taskCompletionService): JsonResponse
    {
        $result = $taskCompletionService->markComplete($id);

        return $this->json([
            'task' => ['id' => $id, 'done' => true],
            'projectFinished' => $result->isProjectNowFinished(),
        ]);
    }
}
```

## Code Explanation

- The controller method contains exactly three steps: receive `$id` (from routing) and `$taskCompletionService` (a service, injected automatically — the subject of the next chapter), call one method on the service, and return a `JsonResponse` built from the result.
- All of the actual business rule — "mark this task done, then check if the whole project is now finished, and if so mark the project finished too" — lives inside `TaskCompletionService::markComplete()`, a class that has nothing to do with HTTP, routes, or JSON. It could be called from a console command, a scheduled job, or a test, with zero changes.
- The controller never touches the database directly, never contains an `if` statement implementing the cascading business rule, and never needs to know *how* completion cascades to a project — it only needs to know *that* calling `markComplete($id)` handles it and gives back a result object it can read from (`$result->isProjectNowFinished()`).
- Notice this doesn't remove any capability from the application — the logic still runs, exactly as before — it's purely about *where* the logic lives, which is the entire point of this lesson.

## Common Mistakes

- Believing "the code works, so the structure doesn't matter" — a controller with buried business logic often does work correctly for the current feature, but becomes a liability the moment that logic needs to be reused, tested independently, or modified by someone unfamiliar with the specific controller.
- Extracting logic into a service but still passing the entire `Request` object into it — a service that depends on `Request` is still coupled to HTTP, defeating much of the purpose of extracting it. Pass in plain values (like `$id`, or a small dedicated DTO class) instead, letting the controller be the only place that touches `Request` directly.
- Over-correcting by extracting absolutely everything, including one-line, purely presentational formatting, into services — the goal is separating meaningful business logic and reusable behavior, not eliminating every line of logic from controllers. A controller converting a fetched entity into a JSON-friendly array shape is normal and fine.

## Best Practices

- When writing a new controller method, pause before adding a second or third distinct responsibility (beyond reading input and formatting output) and ask "would this logic make sense outside of an HTTP request?" — if yes, it likely belongs in a service.
- Name services after what they *do* in business terms (`TaskCompletionService`, `InvoiceCalculator`) rather than generic names like `TaskHelper` or `Utils`, which tend to accumulate unrelated logic over time.
- Keep this discipline consistent even for "simple" endpoints — the habit of thin controllers pays off exactly when a simple endpoint unexpectedly grows more complex later, since the separation is already in place.

## Exercise

A colleague wrote a controller method for `POST /api/tasks` that: reads the JSON body, validates the title isn't empty, calculates a due date by adding 7 days to the current date if none was provided, saves the task via a repository, and returns the created task as JSON — all directly inside the controller method. Identify which specific steps should be extracted into a service, and which should reasonably stay in the controller.

## Solution

Reading the JSON body and returning the final JSON response are HTTP-specific concerns and should stay in the controller — they are exactly the "receive input, produce response" job a controller exists for. Validating that the title isn't empty, calculating the default due date, and saving the task via a repository are all business logic with no inherent connection to HTTP — they should be extracted into a service (for example, a `TaskCreationService` with a method like `create(string $title, ?DateTimeImmutable $dueDate): Task`), which the controller calls after reading the request body, passing in plain values rather than the `Request` object itself. This way, the same creation logic (including the due-date default rule) could later be reused by, say, a bulk-import console command, without duplicating the validation or due-date calculation logic a second time.

## Mini Quiz

```yaml
- question: "What is the main problem with a 'fat controller' that contains significant business logic directly?"
  options:
    - "Controllers are not allowed to contain any logic at all under any circumstances"
    - "The logic becomes hard to reuse outside of an HTTP request and hard to test in isolation"
    - "Fat controllers cause Symfony to throw a runtime error"
    - "It makes the application run measurably slower"
  answer: 1
  explanation: "Business logic embedded in a controller is tightly coupled to the HTTP request/response cycle, making it difficult to reuse from other contexts (like console commands) and difficult to test without simulating a full fake request."

- question: "According to the MVC separation covered in Chapter 1, what is the Controller's actual responsibility?"
  options:
    - "To contain all of the application's core business rules"
    - "To act as the coordinating glue between the incoming request and the Model, delegating real logic elsewhere"
    - "To directly manage the database schema"
    - "To render every response as HTML"
  answer: 1
  explanation: "The Controller coordinates: it receives the request, asks the Model (or, practically, a service) to do the real work, and returns an appropriate response — it isn't meant to contain the business rules itself."

- question: "Why is it a mistake to pass the entire Request object into an extracted service?"
  options:
    - "Request objects cannot be passed as method arguments in PHP"
    - "It re-couples the service to HTTP, undermining the reusability and testability gained by extracting it in the first place"
    - "Services are technically unable to accept any arguments"
    - "It causes a routing error"
  answer: 1
  explanation: "The point of moving logic into a service is to decouple it from the HTTP layer. If the service still depends on the Request object, it remains tied to HTTP and loses much of the reusability and easy testability that motivated the extraction."
```
