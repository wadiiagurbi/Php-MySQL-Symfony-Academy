---
slug: dto-and-layers
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: architecture
chapterTitle: "Chapter 15 — Real-World Architecture"
chapterOrder: 15
title: "DTOs and Layer Boundaries"
difficulty: intermediate
order: 2
estimatedMinutes: 12
tags: [symfony, architecture, dto, validation]
---

## Learning Objective

Learn what a **DTO (Data Transfer Object)** is, how DTOs keep layers decoupled and validation centralized, and how data flows from request through DTO to entity.

## Explanation

A **DTO (Data Transfer Object)** is a small, purpose-built class whose only job is carrying data across a boundary — typically from the HTTP layer into the application layer. Unlike an **entity** (a Doctrine-mapped class representing a database row, with identity and lifecycle), a DTO has no database mapping, no id, and no persistence behavior: it is a typed container like `CreateTaskRequest { string $title; bool $notify }` describing "what the caller asked for." The controller builds (or, with Symfony's `#[MapRequestPayload]` attribute, auto-receives) the DTO, validation runs against it, and only a valid DTO reaches the service — which then decides how entities are built from it.

This indirection buys three things. First, **decoupling**: the service signature depends on a stable, intention-revealing object instead of framework (`Request`) or storage (`Task` entity) types, so the HTTP layer can evolve (new form fields, renamed JSON keys) without rippling into business logic. Second, **centralized validation**: Symfony **constraints** (attribute rules like `#[Assert\NotBlank]` and `#[Assert\Length]` declaring what valid data looks like) live on the DTO's properties, so web forms, JSON APIs, and console imports all enforce identical rules by sharing or mirroring one class — instead of triplicating `if`-checks. Third, **security**: mass-assignment attacks (a client smuggling `{"role":"ADMIN"}` into a payload bound directly to an entity) become impossible, because only the DTO's declared fields are ever read, and the service sets privileged fields explicitly.

The flow is always one direction: request data → DTO → validated DTO → service → entities → persistence. Entities never leak outward either — responses use read models or serializer groups (Advanced 05) rather than raw entities where the contract matters.

## Why It Matters

"Bind the request straight to the entity, it is faster" is the shortcut behind over-posting vulnerabilities and validation drift across web/API entry points. Professional teams accept the tiny cost of a DTO class per operation because it makes every input contract explicit, reviewable, and testable — reviewers can approve a use case by reading one 20-line class instead of tracing request handling across three files.

## Example

Task creation arrives both as an HTML form and as JSON (`{"title": "..."}`). A single `CreateTaskData` DTO with a `NotBlank` + length constraint validates both entry points identically before the service builds the entity.

## Code

```php
<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTaskData
{
    public function __construct(
        #[Assert\NotBlank(message: 'Please give the task a title.')]
        #[Assert\Length(max: 140, maxMessage: 'Keep titles under {{ limit }} characters.')]
        public string $title = '',

        public bool $notifyOwner = false,
    ) {
    }
}
```

## Code Explanation

- `App\Dto\CreateTaskData` is a plain class under `src/Dto/` with no Doctrine mapping — it carries input, never database identity.
- `#[Assert\NotBlank]` and `#[Assert\Length]` declare the contract on the property itself, enforced by Symfony's Validator for every entry point using this DTO.
- Constructor promotion with defaults (`= ''`, `= false`) makes the DTO trivial to build by hand in tests: `new CreateTaskData('Buy milk', true)`.
- The `notifyOwner` flag shows DTOs carrying *intent* beyond entity fields — the service reads it to decide whether to send mail, while the entity stays free of request concerns.
- Privileged data (owner, roles, timestamps) is deliberately absent: the service supplies those from the session and server clock, so clients can never forge them.

## Common Mistakes

- Binding request data directly onto entities (`$form->handleRequest($task)` everywhere). Any settable property becomes client-writable, inviting over-posting of roles, ownership, or ids.
- Duplicating validation per entry point (regex in the web controller, different rules in the API). Drift is inevitable — one shared DTO constraint set stays consistent.
- Reusing one "god DTO" across unrelated operations. A creation DTO and an update DTO have different required fields; sharing forcespermissive validation that blurs both contracts.

## Best Practices

- Create one small DTO per operation (`CreateTaskData`, `RenameTaskData`) with constraints on its properties, and let the service own entity construction.
- Keep entities and infrastructure types out of DTOs; pass ids and scalars across the boundary and reload managed objects inside the service.
- Test DTO validation directly (valid/invalid payloads) — it is the cheapest test in the suite and guards every entry point at once.

## Exercise

A "rename task" operation needs a new title (required, max 140 chars) and nothing else. Write the DTO class and explain which mass-assignment attack it prevents compared to binding the request to the `Task` entity.

## Solution

Write `RenameTaskData` with a single `#[Assert\NotBlank] #[Assert\Length(max: 140)] public string $title` property. Compared to entity binding — where a crafted payload like `{"title":"x","owner":99,"done":true}` could overwrite ownership or state — the DTO exposes only `title`, so the service reads nothing else and sets owner/state server-side by construction.

## Mini Quiz

```yaml
- question: "What is a DTO and how does it differ from an entity?"
  options:
    - "A DTO is a database table while an entity is a Twig template"
    - "A DTO is a small unmapped class carrying input across a boundary; an entity is a Doctrine-mapped class representing a persisted row"
    - "They are identical and interchangeable"
    - "A DTO can only contain integer fields"
  answer: 1
  explanation: "DTOs describe what was asked (no identity, no persistence); entities represent stored domain state with lifecycle and mapping."
- question: "How do DTOs prevent mass-assignment (over-posting) attacks?"
  options:
    - "By encrypting the entire request body"
    - "By exposing only declared fields for the service to read, so smuggled properties like role or owner are never bound to anything"
    - "By blocking all POST requests"
    - "By hiding the API documentation"
  answer: 1
  explanation: "The service reads exclusively from the DTO's known properties and sets privileged fields server-side, leaving attacker-supplied extras with nowhere to go."
- question: "Why put validation constraints on the DTO instead of scattering checks in controllers?"
  options:
    - "Controllers cannot execute conditional statements"
    - "One shared constraint set enforces identical rules for every entry point (forms, APIs, imports) and stays consistent"
    - "DTO constraints make pages render faster"
    - "Validation in controllers is a syntax error"
  answer: 1
  explanation: "Constraints on the DTO define the contract once; all callers validate through it, eliminating the drift of duplicated per-controller checks."
```
