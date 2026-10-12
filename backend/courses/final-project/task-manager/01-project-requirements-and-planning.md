---
slug: project-requirements-and-planning
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Project Requirements and Planning"
difficulty: advanced
order: 1
estimatedMinutes: 10
tags: [final-project, task-manager, planning]
---

## Learning Objective

By the end of this lesson, you will be able to describe the full scope of the Task Manager final project — its features, its API surface, and its data ownership model — and explain how that scope maps onto the Symfony concepts you already know: entities, DTOs, services, controllers, security, and testing.

## Explanation

Every real Symfony application starts the same way: not with a `php bin/console make:entity` command, but with a plain-language description of what the app needs to do. Before writing any code, a team writes down the *requirements* — the features a user expects — and only then decides how those features map onto routes, entities, and services.

For this final project, the requirements are:

1. **Authentication.** A visitor can register an account (email, password, display name) and log in to receive a token. Every other endpoint requires that token.
2. **Task CRUD.** An authenticated user can create, read, update, and delete tasks. Each task has a `title`, an optional `description`, a `status` (`TODO`, `IN_PROGRESS`, `DONE`), a `priority` (`LOW`, `MEDIUM`, `HIGH`), an optional `dueDate`, and an optional `category`.
3. **Ownership.** A task belongs to exactly one user — the one who created it. No user can see, edit, or delete another user's tasks, even by guessing an ID.
4. **Categories.** Categories are free-form labels a user creates implicitly by typing a name when creating a task (e.g. "Backend", "Groceries"). They're scoped per user, just like tasks.
5. **Filtering, searching, sorting, pagination.** The task list endpoint must support narrowing results by status, priority, or category, searching by keyword in the title/description, sorting by several fields, and paging through results instead of returning everything at once.
6. **Stats.** A dashboard-style endpoint reports how many tasks the current user has in each status.

Translating this into concrete deliverables gives us the actual API surface this project implements: `POST /api/auth/register`, `POST /api/auth/login`, `GET /api/me`, `GET/POST /api/tasks`, `GET/PUT/DELETE /api/tasks/{id}`, `GET /api/categories`, and `GET /api/stats`.

## Why It Matters

This is exactly how production Symfony teams plan real work: requirements first, API contract second, implementation third. Skipping straight to code without deciding what "ownership" means (should a stranger's task 404 or 403?) or what "filtering" needs to support (exact match? search? both?) is how APIs end up inconsistent. Every earlier lesson in this course — entities, Doctrine relationships, DTOs, validation, security, testing — exists to be *combined* here. The rest of this chapter builds this exact plan, one layer at a time, using the project's real files.

## Example

Here is the requirement-to-implementation map we'll follow for the rest of the chapter:

| Requirement | Symfony concept | Real file |
|---|---|---|
| Register/login | Security component, password hashing, JWT | `AuthController`, `security.yaml` |
| Task data model | Doctrine entities, enums, relationships | `Task`, `TaskStatus`, `TaskPriority`, `TaskCategory` |
| Validated input | DTOs + Validator constraints | `TaskRequest`, `RegisterRequest` |
| Business rules | Service layer | `TaskService` |
| Ownership-scoped queries | Repository + QueryBuilder | `TaskRepository` |
| Consistent JSON shape | Read-model DTO | `TaskView` |
| Consistent errors | Exception listener | `ApiExceptionListener` |
| Confidence the app works | Functional tests | `TaskApiTest`, `AuthTest` |

## Code

```php
<?php
// The API surface this project implements — the contract every later
// lesson in this chapter builds toward. Nothing here is invented; each
// route corresponds to a real controller you will read in this chapter.

// Auth
// POST   /api/auth/register   -> create an account
// POST   /api/auth/login      -> exchange credentials for a JWT (declarative, see security.yaml)
// GET    /api/me              -> the currently authenticated user

// Tasks (all require a valid JWT and are scoped to the caller)
// GET    /api/tasks           -> list, with ?status=&priority=&categoryId=&search=&sortBy=&sortDir=&page=&perPage=
// POST   /api/tasks           -> create
// GET    /api/tasks/{id}      -> show one (404 if not owned by the caller)
// PUT    /api/tasks/{id}      -> update
// DELETE /api/tasks/{id}      -> delete

// Supporting resources
// GET    /api/categories      -> the caller's own categories
// GET    /api/stats           -> task counts grouped by status, for the caller
```

## Code Explanation

This isn't runnable code — it's the contract. Every route is qualified with *who can call it* (an authenticated owner) and *what it returns*. Writing this down before touching a controller is the same discipline as writing an OpenAPI spec or a set of acceptance criteria: it forces you to decide ambiguous cases (what does "list" mean without filters? what happens on an invalid `status` value?) before they become bugs. Note that login has no dedicated controller action — it's handled declaratively by Symfony's `json_login` firewall, which you'll see in the authentication lesson.

## Common Mistakes

- Starting with the database schema before deciding what the API needs to return — this tends to leak internal column names (like `owner_id`) straight into JSON responses.
- Treating "authorization" as one concept, when this project actually needs two different checks: "are you logged in at all" (401) and "do you own this specific resource" (404, by this project's design — explained in a later lesson).
- Designing pagination and filtering as an afterthought, then having to retrofit every query in the repository once the list endpoint is "done."

## Best Practices

- Write the requirements and the endpoint list before creating a single entity.
- Keep a table like the one above mapping every requirement to the concept and file that implements it — it doubles as your project's technical documentation.
- Decide ownership and authorization semantics up front; retrofitting them into existing controllers is far riskier than designing them in from the start.

## Exercise

Extend the requirements list with one new feature: the ability to mark multiple tasks as "DONE" in a single request (a bulk-update). Write down: the HTTP method and route you'd add, what the request body would look like, what response it should return, and which existing project layer (controller, service, repository) would need to change.

## Solution

A reasonable design: `POST /api/tasks/bulk-complete` with a body like `{"ids": [1, 2, 3]}`. The controller would validate the body (a non-empty array of integers), then delegate to a new `TaskService::completeMany(User $owner, array $ids)` method. That service method would ask `TaskRepository` for tasks matching those IDs *and* the owner (never trusting IDs alone — this reuses the same ownership discipline as `findOwnedTask`), set each one's status to `DONE`, and flush once. The response could be `{"updated": 3}` or the updated `TaskView` list. This keeps the same layering (controller → service → repository) you'll see throughout this chapter instead of writing raw SQL in the controller.

## Mini Quiz

```yaml
- question: "Which endpoint in this project has no dedicated controller action?"
  options:
    - "POST /api/auth/register"
    - "POST /api/auth/login"
    - "GET /api/me"
    - "GET /api/tasks"
  answer: 1
  explanation: "Login is handled declaratively by the json_login firewall configured in security.yaml, which delegates token issuance to Lexik JWT's success handler — there is no AuthController method for it."

- question: "According to the project's planned scope, what determines whether a task is visible to a given request?"
  options:
    - "Any authenticated user can see any task"
    - "Tasks are public unless marked private"
    - "A task is only visible to the user who owns it"
    - "Visibility depends on the task's status"
  answer: 2
  explanation: "Ownership is a core requirement: every task belongs to exactly one user, and no other user can see, edit, or delete it."

- question: "Why write the requirements and API contract before creating entities or writing controller code?"
  options:
    - "Symfony requires a specification file to boot"
    - "It forces ambiguous decisions (like authorization semantics) to be made deliberately instead of accidentally"
    - "Doctrine cannot generate migrations without a written spec"
    - "It is only useful for teams larger than one developer"
  answer: 1
  explanation: "Planning surfaces decisions like 404-vs-403 for unowned resources or what fields are filterable before they get baked in inconsistently across files."
```
