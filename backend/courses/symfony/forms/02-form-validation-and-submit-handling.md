---
slug: form-validation-and-submit-handling
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: forms
chapterTitle: "Chapter 9 — Forms"
chapterOrder: 9
title: "Form Validation and Submit Handling"
difficulty: intermediate
order: 2
estimatedMinutes: 11
tags: [symfony, forms, validation]
---

## Learning Objective

By the end of this lesson you will be able to handle an incoming request with a Symfony form using `handleRequest()`, check whether it was submitted and valid with `isSubmitted()`/`isValid()`, and understand how validation errors flow back to the caller.

## Explanation

Defining a form type (previous lesson) only describes a form's shape. Actually using it in a controller to process an incoming request follows a consistent, three-step pattern in Symfony:

1. **Create the form**, bound to an object — either a brand-new one (for creating) or an existing entity fetched from a repository (for editing) — using `$this->createForm(TaskType::class, $task)`.
2. **Hand the form the current request** with `$form->handleRequest($request)`. This single call does a lot: it detects whether the request's method/data actually corresponds to a submission of *this* form, and if so, reads the submitted values and writes them onto the bound object's properties (via its setters) — but it does **not** yet tell you whether those values were valid.
3. **Check submission and validity** with `if ($form->isSubmitted() && $form->isValid())`. `isSubmitted()` answers "was this form actually submitted in this request" (as opposed to, say, a `GET` request just rendering an empty form). `isValid()` triggers the Symfony Validator component against the bound object — the same `#[Assert\...]` constraints from the previous chapter — and returns whether every constraint passed.

This separation matters: a form can be submitted-but-invalid (the common case where a user needs to see error messages instead of a success page), or not-submitted-at-all (just rendering an empty form for the first visit) — very different situations that a controller must handle differently, and `isSubmitted()`/`isValid()` being separate checks is exactly what allows that.

When validation fails, the form object itself accumulates errors *per field*, accessible through each field's `$form->get('title')->getErrors()`, or all of them via `$form->getErrors(true)` (the `true` means "include errors from child fields too"). In a traditional server-rendered Symfony app, re-rendering the form template automatically displays these errors next to each field. In an API context — which is how this platform's backend is built, serving a Vue frontend rather than server-rendered Twig templates — the more useful move is to convert those errors into a structured JSON response the frontend can map onto its own form fields, the same field-keyed shape you built with the Validator component directly in the previous chapter.

## Why It Matters

This `handleRequest()` → `isSubmitted()` → `isValid()` pattern is extremely standard across Symfony codebases — recognizing it (and its API-friendly JSON-error variant) is expected of anyone claiming Symfony experience. Getting the submitted/valid distinction right also avoids a very real bug: treating every submission as successful without checking `isValid()` first would let invalid data (a blank title, a description with disallowed characters) flow straight into `persist()`/`flush()`, defeating the entire point of declaring constraints in the first place.

## Example

Creating a new task through a form-backed API endpoint, returning either the created task's ID or a field-by-field error list:

```php
$task = new Task($user, ''); // placeholder title; the form will overwrite it
$form = $this->createForm(TaskType::class, $task);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $entityManager->persist($task);
    $entityManager->flush();
    // success
} elseif ($form->isSubmitted()) {
    // build and return field errors
}
```

## Code

```php
<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class TaskFormController extends AbstractController
{
    #[Route('/api/tasks/form', name: 'api_task_create_form', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = new Task($user, '');

        $form = $this->createForm(TaskType::class, $task);

        // Reads the submitted JSON/form data and maps it onto $task's
        // setters — but does NOT tell us yet whether the data is valid.
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->json(['error' => ['message' => 'No form data submitted.']], 400);
        }

        if (!$form->isValid()) {
            $fieldErrors = [];
            foreach ($form->getErrors(true) as $error) {
                $origin = $error->getOrigin();
                $field = $origin?->getName() ?? 'form';
                $fieldErrors[$field] = $error->getMessage();
            }

            return $this->json(['error' => ['fields' => $fieldErrors]], 422);
        }

        // Submitted AND valid: safe to persist.
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['id' => $task->getId(), 'title' => $task->getTitle()], 201);
    }
}
```

## Code Explanation

- `new Task($user, '')` creates a new, not-yet-valid `Task` purely so the form has an object to bind to and populate — the empty title is immediately overwritten by whatever the form submission provides.
- `$this->createForm(TaskType::class, $task)` builds a form instance from the `TaskType` form type (previous lesson), bound to `$task`.
- `$form->handleRequest($request)` reads the current `Request` and, if it matches this form's expected method/name, writes submitted values onto `$task` via its setters — this happens regardless of whether those values are ultimately valid.
- `$form->isSubmitted()` is checked first and independently: a `GET` request or an unrelated request would leave this `false`, and the endpoint returns early with a clear "no data submitted" response rather than proceeding.
- `$form->isValid()` triggers the Validator component against `$task` (using the same constraints declared on the `Task` entity) and returns whether everything passed.
- `$form->getErrors(true)` collects every validation error, including from nested fields, and `$error->getOrigin()?->getName()` identifies which field each error belongs to — this is exactly what lets the response map errors back onto individual form fields for the frontend, the same structured shape used with the Validator component directly.
- Persisting only happens in the final branch, once both `isSubmitted()` and `isValid()` are confirmed true.

## Common Mistakes

- **Skipping the `isSubmitted()` check.** Calling `isValid()` on a form that was never actually submitted (e.g., an initial `GET` request) can produce confusing "invalid" results for data that was never provided in the first place — always check both, in that order.
- **Persisting before checking `isValid()`.** This is the most consequential mistake: without the `isValid()` guard, invalid or malicious data flows straight into the database, defeating the entire purpose of declaring constraints.
- **Assuming `handleRequest()` already validates.** It only reads and maps data onto the bound object; it never runs constraint checks by itself — that's what `isValid()` is for, as a distinct, later call.
- **Not handling the "submitted but invalid" case explicitly.** Silently returning a generic error instead of field-specific messages makes it much harder for a frontend to show the user exactly what to fix.

## Best Practices

- Always follow the same order: `handleRequest()`, then `isSubmitted()`, then `isValid()` — never skip a step or reorder them.
- In an API context, always translate form errors into a structured, field-keyed JSON response rather than relying on server-rendered Twig error display.
- Keep the object passed into `createForm()` in a state that's safe to discard if validation fails — don't perform any side effects (persist, external calls) before `isValid()` confirms success.
- Reuse the exact same form type for both the "create" and "edit" flow when the fields are the same, passing an existing entity instead of a new one for edits.

## Exercise

Adapt the `create()` action above into an `edit(int $id, ...)` action that loads an existing `Task` by ID (404 if not found), binds the same `TaskType` form to it, handles the request, and on success returns the updated task's title and status — otherwise returns the same kind of field error response.

## Solution

```php
#[Route('/api/tasks/{id}/form', name: 'api_task_edit_form', methods: ['PATCH'])]
public function edit(int $id, Request $request, TaskRepository $taskRepository, EntityManagerInterface $entityManager): JsonResponse
{
    $task = $taskRepository->find($id);

    if (null === $task) {
        return $this->json(['error' => ['message' => 'Task not found.']], 404);
    }

    $form = $this->createForm(TaskType::class, $task);
    $form->handleRequest($request);

    if (!$form->isSubmitted()) {
        return $this->json(['error' => ['message' => 'No form data submitted.']], 400);
    }

    if (!$form->isValid()) {
        $fieldErrors = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()?->getName() ?? 'form';
            $fieldErrors[$field] = $error->getMessage();
        }

        return $this->json(['error' => ['fields' => $fieldErrors]], 422);
    }

    $entityManager->flush(); // $task is already tracked; no persist() needed

    return $this->json(['id' => $task->getId(), 'title' => $task->getTitle(), 'status' => $task->getStatus()]);
}
```

The key difference from `create()`: the `Task` is fetched via the repository instead of constructed new, so it's already tracked by the EntityManager — only `flush()` is needed on success, matching the CRUD pattern from Chapter 6, not a fresh `persist()`.

## Mini Quiz

```yaml
- question: "What does $form->handleRequest($request) do?"
  options:
    - "It validates the form and returns true or false"
    - "It reads the submitted data from the request and maps it onto the bound object's setters, without yet checking validity"
    - "It persists the bound object to the database"
    - "It renders the form as HTML"
  answer: 1
  explanation: "handleRequest() detects and reads submission data, mapping it onto the bound object — but validity is a separate, later check via isValid()."
- question: "Why check both isSubmitted() and isValid(), rather than just isValid()?"
  options:
    - "isValid() alone is sufial and isSubmitted() is redundant"
    - "isSubmitted() distinguishes 'no data provided yet' (e.g. an initial GET) from 'data was provided but failed validation', which need different handling"
    - "isSubmitted() checks database connectivity"
    - "isValid() throws an exception if called before isSubmitted()"
  answer: 1
  explanation: "isSubmitted() and isValid() answer different questions; skipping isSubmitted() can misinterpret a request that never submitted the form as simply 'invalid'."
- question: "What must be true before it's safe to persist/flush an entity bound to a form?"
  options:
    - "Only that handleRequest() was called"
    - "Only that isSubmitted() is true"
    - "Both isSubmitted() and isValid() must be true"
    - "Nothing needs to be checked; forms are always valid after handleRequest()"
  answer: 2
  explanation: "Persisting should only happen once both isSubmitted() (data was actually provided) and isValid() (that data passed every constraint) are confirmed true."
```
