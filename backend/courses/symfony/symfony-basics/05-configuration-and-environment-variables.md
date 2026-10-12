---
slug: configuration-and-environment-variables
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: symfony-basics
chapterTitle: "Chapter 1 — Symfony Basics"
chapterOrder: 1
title: "Configuration and Environment Variables"
difficulty: intermediate
order: 5
estimatedMinutes: 10
tags: [symfony, configuration, environment, security]
---

## Learning Objective

Understand how Symfony manages configuration through `.env` files and environment variables, why secrets should never be hardcoded, and how the `%env(...)%` syntax connects environment variables to configuration files.

## Explanation

Every real application needs values that differ between environments — your local machine, a teammate's machine, a staging server, and production. Obvious examples include the database connection string, API keys for third-party services, and the secret key used to sign security tokens. These are called **configuration values**, and the subset of them that must stay confidential (database passwords, API keys) are called **secrets**.

If you hardcode a database password directly inside a PHP file, two things go wrong: first, anyone with read access to your source code (including everyone who can see your Git repository) can see the password; second, you cannot easily change the value per environment — your local database almost certainly has different credentials than production, but the file would be identical in both places.

Symfony solves this using **environment variables**: values set outside your PHP code, at the operating-system or process level, which your application reads at runtime. This is not a new PHP concept — plain PHP has always been able to read `getenv('SOME_VAR')` — but Symfony builds a convenient, structured layer on top of it.

By convention, a Symfony project has a `.env` file at its root. This file defines *default* values for environment variables, in the simple `KEY=value` format, and is meant to be committed to version control (it typically contains only safe, non-secret defaults, like the shape of a database URL pointing at a local dev database). Alongside it, you create a `.env.local` file — which is **never committed** to version control (Symfony's default `.gitignore` excludes it) — where you or each teammate puts their own actual secrets and machine-specific overrides. Symfony automatically loads `.env`, then `.env.local` on top of it (letting local values override the defaults), then genuinely different files for other environments like `.env.test`.

Inside your YAML configuration files (in `config/packages/`), you don't hardcode the actual secret value — instead you reference the environment variable using Symfony's special `%env(...)%` syntax. For example, a database configuration might read `DATABASE_URL: '%env(DATABASE_URL)%'`. At runtime, Symfony resolves this by looking up the actual `DATABASE_URL` environment variable (which came from `.env`/`.env.local`) and substituting its value. This means your *configuration structure* (committed to Git, visible to the whole team) never contains the actual *secret value* (kept out of Git, different per environment).

## Why It Matters

Committing secrets to version control is one of the most common and damaging real-world security mistakes — once a secret is in Git history, it is extremely difficult to fully remove, and if the repository is ever exposed (a misconfigured permission, a leaked access token, an open-source mistake), every secret ever committed is compromised. Symfony's `.env` / `.env.local` split, combined with the `%env(...)%` syntax, is specifically designed to make "the safe way" also "the easy way": if you follow the convention, you structurally cannot accidentally commit your production database password, because it never lives in a file Git tracks. This pattern (external config, referenced by placeholder) is also exactly how most cloud hosting platforms expect you to supply configuration, so learning it here transfers directly to deploying real Symfony applications.

## Example

A task-tracking application needs a database connection and a secret key for signing authentication tokens. In `.env` (committed), the team defines a placeholder-shaped default so anyone cloning the repo has something to start from: `DATABASE_URL="mysql://user:password@127.0.0.1:3306/tasks_dev"`. Each developer then creates their own `.env.local` (not committed) with their actual local MySQL credentials, and production servers set real environment variables directly (through the hosting platform, never through a committed file) with the real production database URL and a strong, unique `APP_SECRET`.

## Code

```yaml
# config/packages/doctrine.yaml (excerpt)
# This file is committed to version control. Notice it never contains
# an actual password — only a reference to an environment variable.
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        # 'resolve:' also expands any %parameters% inside the env value itself.

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
```

## Code Explanation

- `url: '%env(resolve:DATABASE_URL)%'` tells Symfony's Doctrine integration (the library used to talk to the database) to read the actual connection string from the `DATABASE_URL` environment variable at runtime, rather than from a literal value in this file.
- The `%env(...)%` syntax is Symfony's general mechanism for pulling any configuration value from the environment, not just database URLs — the same pattern is used for API keys, mail server settings, or any other secret.
- The `resolve:` prefix inside `%env(resolve:DATABASE_URL)%` is a processor that additionally expands any `%parameter%` placeholders that might appear *inside* the environment variable's own value — useful when one config value is built from others.
- Because this YAML file only contains the *reference* `DATABASE_URL`, not an actual connection string with a real password, it is completely safe to commit to Git — the real value lives in `.env.local` (local machines) or the hosting platform's environment variable settings (production), neither of which is tracked by Git.

## Common Mistakes

- Committing `.env.local` to Git "by accident" because it wasn't added to `.gitignore` in a customized project setup — always verify `.env.local` is ignored before your first commit on a new project.
- Putting real secrets directly into the committed `.env` file, reasoning "it's just for local development" — even local-development secrets (like a real third-party API test key) should not be committed, since `.env` is meant only for safe, shareable defaults.
- Forgetting to set the required environment variables on a production server and being confused when the app fails with "environment variable not set" errors — production environments don't have a `.env.local` file at all; you configure real environment variables through your hosting platform directly.

## Best Practices

- Treat `.env` as documentation of *what* variables exist and their shape (safe placeholder values), and `.env.local` / real server environment variables as the *actual* secret values — never mix the two roles.
- Use a strong, randomly generated value for `APP_SECRET` (used by Symfony for security-related signing) in every real environment, and never reuse the same secret between development and production.
- Reference secrets through `%env(...)%` in configuration files consistently, even for values that don't feel sensitive yet (like a feature flag) — it keeps all environment-specific configuration handled the same, predictable way.

## Exercise

Your task-tracking application needs to call a third-party email-sending API, which requires an API key. Describe exactly which file(s) you would edit to add support for this new `MAILER_API_KEY` value, being explicit about what goes in a file committed to Git versus what does not.

## Solution

In `.env` (committed to Git), add a line like `MAILER_API_KEY=` (or a clearly fake placeholder such as `MAILER_API_KEY=changeme`) — this documents that the variable exists and is required, without revealing any real key. In `.env.local` (never committed — already excluded by `.gitignore`), each developer adds the real line, e.g. `MAILER_API_KEY=sk_live_actualkeyvalue123`, using their own valid test/development key. In the relevant configuration file (for example, `config/packages/mailer.yaml` or wherever the mailer service reads its key), reference it as `'%env(MAILER_API_KEY)%'` rather than pasting the actual key value. In production, the real key is set as an actual environment variable on the hosting platform itself, again never inside a file tracked by Git.

## Mini Quiz

```yaml
- question: "What is the main purpose of the .env.local file in a Symfony project?"
  options:
    - "To store safe default values that are committed to Git for the whole team"
    - "To hold real secrets and machine-specific overrides, and it is excluded from version control"
    - "To configure routing"
    - "To replace the need for a database entirely"
  answer: 1
  explanation: ".env.local holds actual secret values and per-machine overrides. It is excluded from version control by default so real credentials never end up in Git history."

- question: "In config/packages/doctrine.yaml, what does '%env(DATABASE_URL)%' do?"
  options:
    - "Hardcodes the literal string 'DATABASE_URL' as the connection string"
    - "Tells Symfony to read the actual value from the DATABASE_URL environment variable at runtime"
    - "Creates a new environment variable named DATABASE_URL"
    - "Deletes the DATABASE_URL variable"
  answer: 1
  explanation: "The %env(...)% syntax is a placeholder that Symfony resolves at runtime by looking up the named environment variable's actual value, keeping the real secret out of the committed configuration file."

- question: "Why is hardcoding a production database password directly in a PHP or YAML file considered a serious mistake?"
  options:
    - "It makes the application run slower"
    - "PHP does not allow string literals in configuration files"
    - "Anyone with access to the source code (including Git history) would see the real secret, and it cannot easily differ per environment"
    - "It has no real downside as long as the repository is private"
  answer: 2
  explanation: "Hardcoded secrets are visible to everyone with source/Git access, are difficult to fully remove once committed, and cannot vary cleanly between local, staging, and production environments — which is exactly what environment variables solve."
```
