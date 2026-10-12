---
slug: dtos-and-api-validation
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: api-development
chapterTitle: "Chapter 11 — API Development"
chapterOrder: 11
title: "DTOs and API Validation"
difficulty: advanced
order: 2
estimatedMinutes: 12
tags: [symfony, api, dto, validation]
---

## Learning Objective

Understand what a DTO (Data Transfer Object) is, why an API should not read from or write to Doctrine entities directly, and how to validate incoming JSON using a dedicated DTO class.

## Explanation

In earlier chapters you validated Symfony **Forms** bound directly to entities, and you mapped Doctrine entities straight to database rows. That works well for HTML forms, but an API has a different problem: the JSON body a client sends is not guaranteed to look anything like your `Task` entity, and you generally do *not* want it to. A **DTO — Data Transfer Object** — is the fix: a small, plain PHP class whose only job is to describe the exact shape of data going in or out of your API, completely decoupled from how that data is stored in the database.

Two DTOs typically show up around every API endpoint:

- A **request DTO** (like `TaskRequest`) describes what the client is allowed to send in the request body — which fields exist, their types, and their validation rules.
- A **response DTO** (like `TaskView`) describes exactly what the client receives back — which fields, in what shape, regardless of what extra columns or relations the entity actually has.

Why not just validate and serialize the `Task` entity directly? Several concrete reasons:

1. **The entity has fields the client should never set or see.** `Task` has an `owner` relation (a `User` entity); if you validated a `Task` entity built straight from client JSON, a malicious client could potentially include an `owner` field and reassign a task to someone else. `TaskRequest` simply has no `owner` property — the field cannot exist in incoming data because the DTO's shape does not allow it.
2. **The API's shape and the database's shape are allowed to differ and evolve independently.** You might rename a database column without breaking the API contract (you just adjust the mapping in one place), or add an API-only computed field without touching the database schema at all.
3. **Validation rules for "data coming from a browser JSON body" are not always identical to validation rules for "data allowed to exist in the database at all."** A DTO lets you validate exactly what the API accepts, using Symfony's `Assert` constraints, before that data ever touches Doctrine.
4. **Serializing an entity directly risks leaking data.** Doctrine entities often carry relations, timestamps, or internal fields you never intended to expose over the wire; a response DTO's `jsonSerialize()` method is an explicit allowlist of exactly what goes out.

In this project's real code, `TaskRequest` is the request DTO for `POST`/`PUT /api/tasks`, and `TaskView` is the response DTO returned by every task endpoint. Notice `TaskRequest` has no `id`, no `owner`, and no timestamps — only the fields a client is actually allowed to submit, each annotated with a validation constraint from `Symfony\Component\Validator\Constraints`.

## Why It Matters

Decoupling your API's wire format from your Doctrine schema is one of the most important architectural decisions in a real API, because the two evolve for completely different reasons: the database schema changes to satisfy query performance, normalization, and storage needs, while the API's shape changes to satisfy whatever the frontend (or external consumers) actually need. Without DTOs, every database migration risks silently breaking the API, and every attempt to "just validate the entity" risks exposing internal fields or letting clients set properties they should never control (a classic security bug called **mass assignment**). Professional Symfony APIs consistently use this DTO-in, DTO-out pattern for exactly these reasons.

## Example

A client sends `POST /api/tasks` with `{"title": "Write report", "status": "TODO", "priority": "HIGH"}`. Symfony deserializes this JSON directly into a `TaskRequest` object (not a `Task` entity), then validates that object against its `Assert` constraints. Only after validation passes does application code construct or update an actual `Task` entity using the now-trusted DTO's fields — the untrusted JSON never has a path directly into the database.

## Code

```php
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shape of the JSON body accepted by POST /api/tasks and PUT /api/tasks/{id}.
 * Validating this DTO (instead of the Task entity directly) keeps invalid
 * input from ever reaching Doctrine.
 */
final class TaskRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 140, maxMessage: 'Title cannot be longer than {{ limit }} characters.')]
    public string $title = '';

    public ?string $description = null;

    #[Assert\Choice(choices: ['TODO', 'IN_PROGRESS', 'DONE'], message: 'Status must be TODO, IN_PROGRESS or DONE.')]
    public string $status = 'TODO';

    #[Assert\Choice(choices: ['LOW', 'MEDIUM', 'HIGH'], message: 'Priority must be LOW, MEDIUM or HIGH.')]
    public string $priority = 'MEDIUM';

    #[Assert\Date(message: 'Due date must be a valid date (YYYY-MM-DD).')]
    public ?string $dueDate = null;

    #[Assert\Length(max: 80)]
    public ?string $category = null;
}
```

## Code Explanation

- `final class TaskRequest` — a plain PHP class with no Doctrine mapping attributes at all; it is not an entity, it never gets persisted, it exists purely to describe an incoming shape.
- Public typed properties (`public string $title = '';`) give the DTO sensible defaults and let Symfony's serializer assign values to them directly by property name when deserializing JSON.
- `#[Assert\NotBlank]` and `#[Assert\Length(max: 140)]` are Symfony Validator constraints, the same ones you used on Form-bound entities earlier in the course — here they are attached to the DTO instead, so the rules apply the instant the object exists, before any Doctrine code runs.
- `#[Assert\Choice(choices: [...])]` restricts `status` and `priority` to a fixed set of strings, rejecting anything else (e.g., a typo like `"Todo"`) with a 422 rather than letting an invalid value reach the `TaskStatus` enum conversion later.
- Notice there is no `id`, `owner`, `createdAt`, or `updatedAt` property — a client cannot set any of these through this DTO no matter what extra JSON keys it sends, because the serializer only populates properties that exist on the target class.

## Common Mistakes

- Adding `#[ORM\Column]` mapping attributes to a request DTO out of habit — a DTO is not an entity and should never be Doctrine-mapped; mixing the two concerns defeats the entire point of separating them.
- Validating the entity itself after manually copying fields from an array (`$task->setTitle($data['title'])`), which reintroduces exactly the mass-assignment risk DTOs are meant to prevent, since nothing stops a stray `$task->setOwner(...)` line from trusting client input.
- Reusing the same DTO class for both request and response. A request DTO deliberately has *no* `id` (the client can't set it); a response DTO deliberately *includes* `id` (the client needs to read it). Conflating the two either leaks fields or blocks legitimate ones.
- Forgetting to add a validation constraint to a new field added to a request DTO, silently allowing arbitrary unvalidated data through.

## Best Practices

- Give a request DTO sensible default property values (as `TaskRequest` does with `status = 'TODO'`) so a client that omits an optional field still produces a valid object.
- Keep DTOs "dumb" — plain data holders with validation constraints, no business logic. Business logic (like deciding what happens when a task's status becomes `DONE`) belongs in a Service, not the DTO.
- Name request and response DTOs clearly and consistently (`TaskRequest` / `TaskView`, or `CreateTaskRequest` / `TaskResponse`) so any developer can tell direction at a glance.
- Let a single reusable helper (like this project's `RequestValidator::fromJson()`) handle the decode-and-validate boilerplate, rather than repeating deserialization and validation logic in every controller action.

## Exercise

A `TaskCategory` entity has `id`, `name`, and an `owner` relation. Design a request DTO, `CategoryRequest`, for the `POST /api/categories` endpoint that lets a client create a category by name only. Include an appropriate validation constraint.

## Solution

```php
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CategoryRequest
{
    #[Assert\NotBlank(message: 'Category name is required.')]
    #[Assert\Length(max: 80, maxMessage: 'Category name cannot exceed {{ limit }} characters.')]
    public string $name = '';
}
```

This DTO exposes only `name` — there is no `id` (assigned by the database) and no `owner` (assigned by the server from the authenticated user, never from client input), which prevents a client from creating a category on someone else's behalf or specifying its own ID.

## Mini Quiz

```yaml
- question: "What is the main purpose of a request DTO like TaskRequest?"
  options:
    - "To be persisted directly to the database by Doctrine"
    - "To describe exactly what shape of data an API endpoint accepts, decoupled from the entity, so it can be validated before touching the database"
    - "To replace the need for a Task entity entirely"
    - "To store cached copies of entities for performance"
  answer: 1
  explanation: "A request DTO's whole purpose is to define and validate the accepted input shape independently of how data is stored, keeping untrusted input away from the entity until it has been validated."

- question: "Why does TaskRequest have no 'owner' property?"
  options:
    - "Because Symfony forbids relations in DTOs"
    - "Because owner is unimportant"
    - "So a client cannot assign or reassign a task's owner through the API request body (preventing mass assignment)"
    - "Because owner is always null for new tasks"
  answer: 2
  explanation: "Leaving 'owner' out of the DTO means the API cannot be tricked into accepting an owner value from client input; the server assigns the true owner from the authenticated user instead."

- question: "What is the risk of validating and persisting a Doctrine entity built directly from raw client JSON, without a DTO in between?"
  options:
    - "There is no risk; it is the recommended Symfony pattern"
    - "It runs slower than using a DTO"
    - "A client could potentially set fields it should never control, such as an owner or internal ID (mass assignment)"
    - "Doctrine entities cannot be validated at all"
  answer: 2
  explanation: "Without a DTO acting as an allowlist of accepted fields, nothing prevents unexpected or malicious JSON keys from mapping onto sensitive entity properties."
```
