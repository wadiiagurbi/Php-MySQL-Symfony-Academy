---
slug: serialization
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Serialization with the Serializer"
difficulty: intermediate
order: 5
estimatedMinutes: 11
tags: [symfony, serializer, json, api]
---

## Learning Objective

Understand what **serialization** is, how Symfony's Serializer converts objects to JSON (and back), and how **groups** control exactly which fields each endpoint exposes.

## Explanation

**Serialization** is the process of converting a rich PHP object (an entity with private properties, dates, and relations) into a portable format like JSON or XML; **deserialization** is the reverse trip from incoming JSON back into objects. APIs live on both directions: every JSON response your controllers return serializes something, and every JSON request body deserializes into something. Hand-building these arrays with `$task->getTitle()` calls works for one endpoint but becomes inconsistent and leak-prone across dozens — the Serializer component centralizes the rules.

The Serializer **normalizes** (converts an object into a plain array, the middle step) using property info and getters, then **encodes** (turns that array into a JSON string) in one `$serializer->serialize($task, 'json')` call. Two mechanisms keep output precise. **Groups** are labels you attach to properties with `#[Groups(['task:read'])]` attributes and activate per call via context (`['groups' => ['task:read']]`): only properties carrying an active group appear in the output. This is how the same `Task` entity safely serves a public list (id, title, done) and an admin detail view (plus owner email, internal notes) without duplicating classes.

**Circular references** (object A points to B which points back to A — e.g., task → owner → tasks) would loop forever, so the Serializer detects them and either omits the repeat or calls your configured handler. Deserialization mirrors the flow: `$serializer->deserialize($json, Task::class, 'json')` builds and fills an object, honoring a separate write group so read-only fields like `id` can never be mass-assigned from request data.

## Why It Matters

Over-exposed APIs ("why does the task list include the owner's password hash?") are serialization misconfigurations, and they rank among the most embarrassing production incidents. Groups give reviewers a single attribute per property to audit, and consistent serializer usage replaces twenty slightly different hand-rolled `toArray()` methods with one predictable pipeline — including proper date formatting and relation handling for free.

## Example

The public task-list API must return each task's id, title, and done flag — but never internal fields like the owner's email. A `task:read` group on exactly those three properties plus a context-aware serialize call enforces the contract.

## Code

```php
<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class TaskApiController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks', methods: ['GET'])]
    public function list(TaskRepository $tasks, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($tasks->findAll(), 'json', [
            'groups' => ['task:read'],
        ]);

        return JsonResponse::fromJsonString($json);
    }
}
```

## Code Explanation

- `SerializerInterface $serializer` injects the framework-configured serializer (normalizers plus JSON encoder pre-wired) with zero setup.
- `$tasks->findAll()` loads entities; the controller never loops over getters to build arrays by hand.
- `serialize(..., 'json', ['groups' => ['task:read']])` normalizes each task then encodes to JSON, including only properties tagged with the `task:read` group.
- Properties without the group (owner email, internal notes, password-adjacent data) are silently excluded — the exposure contract lives on the entity attributes.
- `JsonResponse::fromJsonString($json)` wraps the pre-encoded string without double-encoding it (passing it to `$this->json()` instead would escape the JSON into a string).

## Common Mistakes

- Serializing entities with no groups, exposing everything including relations, internal flags, and (in user endpoints) password hashes — the classic data leak.
- Passing pre-encoded JSON into `$this->json()`, which encodes it *again* so clients receive an escaped string instead of an object.
- Using the same group for reading and writing, letting clients set server-managed fields like `id`, `createdAt`, or ownership by simply including them in request bodies.

## Best Practices

- Define separate read and write groups (`task:read`, `task:write`) and tag each property deliberately — default to hidden, expose by choice.
- Keep date formats and relation depth consistent via serializer context/normalizer config rather than per-controller string juggling.
- Handle circular references explicitly (max-depth or a circular-reference handler) instead of discovering infinite loops in production logs.

## Exercise

A `User` entity has `id`, `email`, `password` (hash), and `displayName`. The public profile endpoint must return only id, email, and displayName. State the group setup and the serialize call that guarantees the hash never leaks.

## Solution

Tag `id`, `email`, and `displayName` with `#[Groups(['user:read'])]` and leave `password` untagged (invisible to every group). Serialize with `$serializer->serialize($user, 'json', ['groups' => ['user:read']])` — untagged properties are excluded by construction, so even a future developer cannot leak the hash through this endpoint without deliberately adding the attribute.

## Mini Quiz

```yaml
- question: "What do serialization groups do?"
  options:
    - "They compress JSON output to save bandwidth"
    - "They label properties so each serialize call includes only fields carrying an active group, controlling exposure per endpoint"
    - "They group database tables for faster migrations"
    - "They sort API results alphabetically"
  answer: 1
  explanation: "Groups turn exposure into an explicit per-property decision: only properties tagged with a group named in the call's context appear in the output."
- question: "Why is serializing entities without groups dangerous?"
  options:
    - "It makes responses slightly larger than necessary"
    - "Every property serializes, potentially leaking sensitive fields like password hashes and internal flags"
    - "It prevents Twig from rendering templates"
    - "It deletes the entities from the database"
  answer: 1
  explanation: "Without group filtering the serializer includes all readable properties, so secrets and internals ride along into public responses unnoticed."
- question: "What goes wrong when pre-encoded JSON is passed to $this->json()?"
  options:
    - "Nothing; it works identically to JsonResponse::fromJsonString()"
    - "The JSON string gets encoded a second time, so clients receive an escaped string instead of an object"
    - "The database connection is closed"
    - "The route is unregistered"
  answer: 1
  explanation: "$this->json() encodes its input, so feeding it an already-JSON string double-encodes; fromJsonString() wraps pre-encoded JSON correctly."
```
