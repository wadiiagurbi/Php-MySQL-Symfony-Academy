---
slug: symfony-validator-and-constraints
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: validation
chapterTitle: "Chapter 8 — Validation"
chapterOrder: 8
title: "The Symfony Validator and Constraints"
difficulty: intermediate
order: 1
estimatedMinutes: 12
tags: [symfony, validation, constraints]
---

## Learning Objective

By the end of this lesson you will be able to validate both Doctrine entities and plain DTO (Data Transfer Object) classes using Symfony's Validator component and built-in constraints, understand what a "constraint" is, and know how to write a simple custom constraint.

## Explanation

Until now, if you wanted to check that a piece of data was valid — a title isn't blank, an email looks like an email, a number is within range — you'd write `if` statements by hand: `if (empty($title)) { throw ... }`, repeated everywhere that data enters your application. This works for one field in one place, but it doesn't scale: the same rules get duplicated and can drift out of sync, and there's no single place that documents "what does a valid Task actually look like?"

Symfony's **Validator** component solves this by letting you declare rules — called **constraints** — directly on a class's properties, instead of scattering `if` checks everywhere. A constraint is a small object describing one rule, like "must not be blank" (`Assert\NotBlank`) or "must be at most 140 characters" (`Assert\Length(max: 140)`). You attach one or more constraints to a property using PHP 8 attributes, and then hand the whole object to Symfony's `ValidatorInterface`, which checks every constraint and returns a list of violations (empty if everything passed).

Crucially, validation is **not** tied to Doctrine entities. You can validate:

- **A Doctrine entity directly** — e.g., `Task` already has `#[Assert\NotBlank]` and `#[Assert\Length(max: 140)]` on its `$title` property in the reference project, so the same class that maps to the database also declares its own validity rules.
- **A plain DTO (Data Transfer Object)** — a simple PHP class with no Doctrine mapping at all, used purely to represent and validate incoming data (like a registration request body) before it's turned into, or used alongside, an entity. This is extremely common for API request payloads, because the shape of "what a client is allowed to send" often differs slightly from the shape of "what gets stored" — for example, a registration DTO includes a plaintext `password` field that never gets stored as-is (see the Security chapter), while the `User` entity only ever stores the hashed version.

When built-in constraints (`NotBlank`, `Length`, `Email`, `Range`, `Choice`, and others) aren't enough, you can write a **custom constraint**: a constraint class describing the rule's configuration, plus a **validator** class containing the actual logic that decides pass/fail. This keeps custom business rules ("this must be a valid task status transition," "this username can't be a reserved word") declarative and reusable, the same way built-in constraints are.

## Why It Matters

Centralizing validation rules as attributes on the class itself means the rules live in exactly one place, are visible just by opening the class, and are enforced consistently everywhere that class is validated — a controller, a form (next chapter), a console command, or a test. Compare this to hand-written `if` checks duplicated across three different controllers that all accept task data slightly differently — a very real, common source of bugs where one code path validates correctly and another forgets a check. It also cleanly separates "is this data well-formed" (validation) from "is this data allowed given business/security rules" (application logic), which keeps both easier to reason about.

## Example

Validating a plain registration DTO before ever touching the database or creating a `User` entity:

```php
$errors = $validator->validate($registerRequest);

if (count($errors) > 0) {
    // build a list of field => message from $errors and return 422 to the client
}
```

## Code

```php
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * A plain DTO — no Doctrine mapping at all — representing the shape of an
 * incoming registration request, validated before a User entity is ever built.
 */
class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    public string $email;

    #[Assert\NotBlank(message: 'Display name is required.')]
    #[Assert\Length(min: 2, max: 120, minMessage: 'Display name must be at least {{ limit }} characters.')]
    public string $displayName;

    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters.')]
    public string $password;
}
```

```php
<?php

namespace App\Controller\Api;

use App\DTO\RegisterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_auth_register_validated', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var RegisterRequest $data */
        $data = $serializer->deserialize($request->getContent(), RegisterRequest::class, 'json');

        // Every #[Assert\...] constraint on RegisterRequest is checked here.
        $errors = $validator->validate($data);

        if (count($errors) > 0) {
            $violations = [];
            foreach ($errors as $error) {
                $violations[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['error' => ['fields' => $violations]], 422);
        }

        // $data is now known-valid; safe to proceed to hashing + persisting a User.
        return $this->json(['email' => $data->email], 201);
    }
}
```

## Code Explanation

- `RegisterRequest` has no `#[ORM\...]` attributes at all — it's a plain DTO, purely for representing and validating incoming request data, distinct from the `User` entity that eventually gets persisted.
- Each property carries one or more `#[Assert\...]` constraint attributes: `NotBlank` ensures a value was provided, `Email` checks the email format, `Length` bounds string size — each with a custom `message` so API responses are meaningful rather than generic.
- `$serializer->deserialize(...)` (Symfony's Serializer component) turns the raw JSON request body into a `RegisterRequest` object, without yet checking whether the values are valid — deserializing and validating are deliberately separate steps.
- `$validator->validate($data)` runs every constraint attached to `$data`'s class and returns a `ConstraintViolationListInterface` — iterable, empty when everything passes.
- Each violation exposes `getPropertyPath()` (which field failed) and `getMessage()` (the constraint's message, with placeholders like `{{ limit }}` already substituted) — used here to build a field-by-field error response with a `422 Unprocessable Entity` status, a standard convention for "well-formed request, invalid data."

## Common Mistakes

- **Validating after already using the data.** Always validate before acting on a DTO or entity's data (e.g., before hashing a password or writing to the database) — validating as an afterthought defeats the purpose.
- **Assuming deserialization implies validity.** The Serializer only builds the object from JSON; it does not check any `#[Assert\...]` constraint. A separate `$validator->validate()` call is always required.
- **Putting business rules that need external state (like "email already registered") into a constraint instead of application logic.** Constraints are best for self-contained, static rules about the data's shape; checking against the database is usually clearer as an explicit check in the controller/service (as in `AuthController::register`, which checks `findOneByEmail()` separately).
- **Forgetting that entity constraints only run when you explicitly validate them.** Doctrine does not automatically validate entities before `flush()` — if you want an entity validated, you must call `$validator->validate($entity)` yourself, typically before persisting.

## Best Practices

- Use DTOs for request payloads whose shape differs from your entities (e.g., a plaintext password that's never stored as-is) rather than forcing an entity to accept fields it shouldn't.
- Give every constraint a clear, user-facing `message` — default messages are fine for prototypes but poor for real API consumers.
- Keep constraint logic declarative and side-effect-free; anything needing a database lookup or external call belongs in application code, or in a custom constraint with a validator service that's explicitly designed for that lookup.
- Return validation failures with a `422` status and a structured, field-keyed error body so frontend code (like the Vue app powering this platform) can map errors to the right form fields.

## Exercise

Write a custom constraint `#[NoReservedWord]` that can be applied to a string property (like a display name) and fails validation if the value is one of `['admin', 'root', 'system']` (case-insensitive). Sketch both the constraint class and its validator class.

## Solution

```php
<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoReservedWord extends Constraint
{
    public string $message = 'The value "{{ value }}" is a reserved word and cannot be used.';
}
```

```php
<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NoReservedWordValidator extends ConstraintValidator
{
    private const RESERVED = ['admin', 'root', 'system'];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return; // combine with a separate NotBlank if the field is required
        }

        if (in_array(strtolower((string) $value), self::RESERVED, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

Usage on a DTO: `#[NoReservedWord] public string $displayName;`. The constraint class only holds configuration (the message template); the matching `...Validator` class (Symfony finds it by naming convention) contains the actual pass/fail logic, and calls `buildViolation()` only when the rule is broken — exactly mirroring how built-in constraints like `NotBlank` work internally.

## Mini Quiz

```yaml
- question: "What is a Symfony 'constraint'?"
  options:
    - "A database-level rule like a foreign key or unique index"
    - "A declarative rule (e.g. NotBlank, Length) attached to a class property, checked by the Validator component instead of hand-written if-statements"
    - "A Doctrine migration step"
    - "A type hint on a PHP property"
  answer: 1
  explanation: "A constraint is a small, declarative rule object (like NotBlank or Length) attached via attributes, checked by ValidatorInterface, replacing scattered manual if-checks."
- question: "Can a plain PHP class with no Doctrine mapping (a DTO) be validated with Symfony's Validator?"
  options:
    - "No, only Doctrine entities can be validated"
    - "Yes — validation is independent of Doctrine; any class's properties can carry #[Assert\\...] constraints"
    - "Only if the DTO extends AbstractController"
    - "Only if the DTO implements ValidatorInterface"
  answer: 1
  explanation: "The Validator component works on any PHP object's properties/attributes, entity or not — DTOs are a very common validation target, especially for API request bodies."
- question: "In the RegistrationController example, what does $validator->validate($data) return when the RegisterRequest is fully valid?"
  options:
    - "An exception is thrown"
    - "true"
    - "An empty ConstraintViolationListInterface (count() === 0)"
    - "null"
  answer: 2
  explanation: "validate() always returns a ConstraintViolationListInterface; when nothing failed, it's simply empty, which is why the code checks count($errors) > 0."
- question: "What is the correct division of responsibility in a custom constraint?"
  options:
    - "The Constraint class holds configuration (like the message); the matching Validator class contains the actual pass/fail logic"
    - "The Constraint class contains the logic; the Validator class only holds configuration"
    - "Both classes must contain identical logic for redundancy"
    - "Custom constraints do not need a separate validator class"
  answer: 0
  explanation: "A Constraint subclass describes the rule's configuration/metadata (like the message template), while its paired ConstraintValidator class implements the actual validate() logic."
```
