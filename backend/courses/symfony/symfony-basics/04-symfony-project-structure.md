---
slug: symfony-project-structure
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: symfony-basics
chapterTitle: "Chapter 1 — Symfony Basics"
chapterOrder: 1
title: "The Symfony Project Structure"
difficulty: intermediate
order: 4
estimatedMinutes: 10
tags: [symfony, structure, conventions]
---

## Learning Objective

Understand the purpose of each top-level folder in a Symfony project (`src/`, `config/`, `public/`, `var/`, `templates/`, `tests/`) so you know where to find — and where to put — code.

## Explanation

One of the most valuable things a framework provides is *convention*: an agreed-upon place for every kind of file, so that any developer who has worked on one Symfony project can immediately navigate any other Symfony project. When you created your skeleton project in the previous lesson, Symfony generated (or will generate, as you add packages) the following top-level folders:

- **`src/`** — this is where *your* application code lives: controllers, entities (classes representing database rows), services (classes containing your business logic, covered in Chapter 5), and any other PHP class specific to your app. This is the folder you will spend the vast majority of your time in. It follows PHP's PSR-4 autoloading convention you already know from Composer — the `App\` namespace maps directly to this folder, so `App\Controller\TaskController` lives at `src/Controller/TaskController.php`.
- **`config/`** — configuration files, mostly in YAML, that tell Symfony *how* to wire things together: which bundles are enabled (`config/bundles.php`), routing configuration (`config/routes.yaml`), and settings for individual packages under `config/packages/` (for example, `config/packages/security.yaml` configures authentication rules). You will edit files here far less often than `src/`, but you'll come back to it whenever you install a new package or need to change framework-level behavior.
- **`public/`** — the *only* folder actually exposed to the web server. As covered in the previous lesson, it contains `index.php`, the single front controller, plus things like compiled CSS/JS assets if your app serves any. Nothing sensitive should ever live here, because anything in `public/` can potentially be requested directly by a browser.
- **`var/`** — a folder for files Symfony generates and manages automatically at runtime: cache files (`var/cache/`) that make the framework fast by avoiding re-parsing configuration on every request, and log files (`var/log/`) that record what happened during requests, errors, and warnings. You rarely edit anything here by hand, but you will often *look* at `var/log/dev.log` while debugging, and you'll clear `var/cache/` when Symfony's cached configuration seems out of date.
- **`templates/`** — Twig template files (`.html.twig`) for any HTML pages your application renders. In an API-focused project like the one built in this course — where most responses are JSON returned straight from a controller — this folder is used sparingly, mainly for things like server-rendered admin screens or HTML email bodies. You'll cover Twig itself in Chapter 4.
- **`tests/`** — automated test classes for your application, mirroring the structure of `src/` (for example, a test for `src/Service/TaskService.php` conventionally lives at `tests/Service/TaskServiceTest.php`). Symfony integrates with PHPUnit for testing, building on the same testing mindset you'd apply to any PHP project.

Because these folders exist by convention rather than being hardcoded as magic, Symfony tools (and other developers) can rely on finding things in predictable places — this is the same benefit conventions give you within a single class (consistent method names) applied at the scale of an entire project.

## Why It Matters

When you join an existing Symfony codebase professionally — which is extremely common, since most Symfony work is maintaining and extending applications that already exist — you will not get a guided tour. Knowing that business logic belongs in `src/`, that `config/packages/*.yaml` controls how individual packages behave, and that `var/log/dev.log` is the first place to check when something breaks unexpectedly lets you become productive in an unfamiliar codebase within minutes instead of hours. It also means that when you write your own code, you instinctively put it where any other Symfony developer would expect to find it.

## Example

Suppose a request to your task-tracking API fails with a 500 error. As a developer who knows the project structure, you don't guess randomly — you open `var/log/dev.log` (or `var/log/prod.log` in production) to see the actual error and stack trace, which will point you to the specific file in `src/` that needs fixing.

## Code

```bash
# Typical top-level layout of a Symfony skeleton-based project
task-tracker/
├── bin/
│   └── console            # command-line tool for Symfony's built-in commands
├── config/
│   ├── bundles.php         # which bundles/packages are enabled
│   ├── routes.yaml         # top-level routing configuration
│   └── packages/
│       └── security.yaml   # per-package configuration (e.g., authentication rules)
├── public/
│   └── index.php           # the single front controller — the only web-exposed file
├── src/
│   ├── Controller/
│   │   └── TaskController.php
│   ├── Entity/
│   │   └── Task.php
│   └── Service/
│       └── TaskService.php
├── templates/
│   └── base.html.twig      # Twig layout, used for any server-rendered HTML
├── tests/
│   └── Service/
│       └── TaskServiceTest.php
├── var/
│   ├── cache/               # auto-generated, safe to delete
│   └── log/
│       └── dev.log          # check this first when debugging
└── .env                     # environment variables (next lesson)
```

## Code Explanation

- `bin/console` is a script you run from the terminal (e.g., `php bin/console cache:clear`) to execute Symfony's built-in commands — you'll use it throughout this course for tasks like clearing the cache or running database migrations.
- `config/bundles.php` is how Symfony knows which installed Composer packages should be "activated" as part of the framework — when you `composer require` a new Symfony package, it's common for an entry to be added here automatically.
- `public/index.php` being the *only* file directly under `public/` in this example underscores that everything else the application needs (your code, configuration, vendor libraries) lives outside the web-accessible folder, which is an important security boundary.
- `src/Controller/`, `src/Entity/`, `src/Service/` show the conventional subfolder-per-concept organization inside `src/` — you are not required to use exactly these subfolder names, but following the convention makes your code instantly familiar to other Symfony developers.
- `var/cache/` is marked "safe to delete" because Symfony regenerates it automatically the next time it's needed; deleting it never causes data loss, only a slightly slower next request while it rebuilds.

## Common Mistakes

- Putting application PHP classes directly under `public/` "to make them easier to find" — this both breaks Symfony's autoloading conventions and, worse, can expose your source code or accidentally executable scripts directly to the internet.
- Manually editing files inside `var/cache/` to "fix" a bug — any change there is deleted the moment Symfony regenerates the cache; the actual configuration or code causing the issue lives elsewhere (usually `config/` or `src/`).
- Ignoring `var/log/dev.log` and trying to debug purely by reading source code when an unexpected error occurs — the log almost always contains the exact exception message and stack trace, saving significant time.

## Best Practices

- Keep `src/` organized by concept (`Controller/`, `Entity/`, `Service/`, `Repository/`, etc.) rather than dumping every class in one folder — this mirrors what you'll see in nearly every real Symfony codebase.
- Never commit `var/cache/` or `var/log/` to version control (Symfony's default `.gitignore` already excludes them) since they are machine-generated and environment-specific.
- When something behaves unexpectedly after a configuration change, run `php bin/console cache:clear` before assuming your code is wrong — stale cached configuration is a common source of confusing bugs.

## Exercise

A teammate says: "I added a new PHP class with my business logic for calculating task due dates, and I put it in `public/DueDateCalculator.php` so it's easy to find." Explain what is wrong with this choice and where the class should actually live, including the correct namespace it should use.

## Solution

Putting `DueDateCalculator.php` in `public/` is a mistake for two reasons: first, `public/` is the only folder directly exposed to the web server, so depending on server configuration this PHP file could potentially be requested and exposed directly, which is a security risk; second, it breaks Symfony's autoloading convention, since the `App\` namespace is mapped to `src/`, not `public/`. The class should live in `src/Service/DueDateCalculator.php` (since it's a piece of business logic, not a controller or entity), with the namespace `App\Service;` and class declaration `class DueDateCalculator { ... }`, matching the PSR-4 autoloading rule that maps `App\Service\DueDateCalculator` to `src/Service/DueDateCalculator.php`.

## Mini Quiz

```yaml
- question: "Which folder is the only one directly exposed to the web server in a standard Symfony project?"
  options:
    - "src/"
    - "config/"
    - "public/"
    - "var/"
  answer: 2
  explanation: "public/ contains index.php, the single front controller, and is the only folder that should ever be reachable directly by a web request; all other folders sit outside the web root."

- question: "Where should you look first when trying to diagnose an unexpected 500 error in a Symfony app?"
  options:
    - "config/bundles.php"
    - "var/log/dev.log (or prod.log)"
    - "public/index.php"
    - "tests/"
  answer: 1
  explanation: "Symfony logs detailed error information, including stack traces, to var/log/dev.log in the development environment, making it the fastest way to diagnose an error."

- question: "What is stored in var/cache/, and what happens if you delete it?"
  options:
    - "User-uploaded files; deleting it causes permanent data loss"
    - "Auto-generated performance cache files; Symfony safely regenerates them automatically"
    - "Your application's source code; deleting it breaks the app permanently"
    - "Database credentials"
  answer: 1
  explanation: "var/cache/ holds Symfony's compiled configuration and other performance caches. It is machine-generated and always safely rebuilt automatically the next time it's needed."
```
