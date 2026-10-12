---
slug: installing-symfony-with-composer
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: symfony-basics
chapterTitle: "Chapter 1 — Symfony Basics"
chapterOrder: 1
title: "Installing Symfony with Composer"
difficulty: intermediate
order: 3
estimatedMinutes: 8
tags: [symfony, composer, setup]
---

## Learning Objective

Learn how to create a new Symfony project using Composer, and understand what actually happens on disk when you do.

## Explanation

You already know Composer as PHP's dependency manager — the tool that downloads libraries listed in `composer.json` into `vendor/`. Symfony itself is distributed as a set of Composer packages, so creating a new Symfony project is really just "ask Composer to download a pre-arranged starting set of Symfony packages, plus a sensible folder structure, into a new directory."

Symfony offers a few different starting points, but the one this course uses — and the one most real-world API-focused projects start from — is the **skeleton**: a minimal, bare-bones project containing just enough to boot Symfony, with almost no extra bundles pre-installed. You add exactly what you need as you go, rather than starting with a large pre-configured application (Symfony also offers a heavier "webapp" starting point with Twig, forms, and security pre-installed, but starting minimal and adding pieces deliberately is a better way to *learn* what each piece actually does).

To create a new project, you run:

```bash
composer create-project symfony/skeleton my-project-name
```

This single command does three things: it creates a new directory named `my-project-name`, downloads the minimal Symfony packages and their dependencies into `my-project-name/vendor/`, and generates the base folder structure (`src/`, `config/`, `public/`, `bin/`, and a starter `composer.json`) that every Symfony project follows by convention. Afterwards, you `cd` into that directory and continue working with Composer exactly as you already know — for example, `composer require symfony/orm-pack` to add Doctrine (database) support later, or `composer require --dev symfony/maker-bundle` to add code-generation helpers for development only.

Because a Symfony skeleton project is a completely ordinary Composer project, there is nothing magical about the install step itself — it does not require any special global tool. Everything you need is `php` and `composer`, both of which you have already been using throughout this course.

## Why It Matters

Understanding that Symfony is "just Composer packages" demystifies the framework and makes it much less intimidating: you are not learning an entirely separate ecosystem, you are applying the exact dependency-management skills from earlier in this course to a specific, well-organized set of packages. This also matters practically: when a real project needs a new capability (say, sending emails, or validating uploaded files), the answer is almost always "require the right Symfony package with Composer," not "write it from scratch." Knowing that Symfony's own installation is Composer-based means you already know how to extend, upgrade, and manage every Symfony project you will ever touch professionally.

## Example

A developer starting a brand-new task-tracking API for this course's running example would open a terminal, navigate to where they keep their projects, and run the create-project command below. Composer would then spend a few moments resolving and downloading packages, after which a new, ready-to-run Symfony skeleton project exists on disk, with its own `composer.json` already listing the installed Symfony components.

## Code

```bash
# Create a new, minimal Symfony project called "task-tracker"
composer create-project symfony/skeleton task-tracker

# Move into the newly created project directory
cd task-tracker

# Start PHP's built-in web server to check the install worked
php -S localhost:8000 -t public
```

## Code Explanation

- `composer create-project symfony/skeleton task-tracker` tells Composer to use the `symfony/skeleton` package as a project template, installing it (and its required dependencies) into a new folder named `task-tracker`. This is the same `composer create-project` mechanism used by many other PHP project templates — Symfony just happens to publish one as `symfony/skeleton`.
- `cd task-tracker` moves your terminal into the new project so subsequent Composer and PHP commands operate on it.
- `php -S localhost:8000 -t public` starts PHP's own built-in development web server, telling it to serve files from the `public/` directory — recall from the previous lesson that `public/index.php` is the single front controller every request should go through. Visiting `http://localhost:8000` in a browser should show Symfony's default "Welcome" page (or a JSON/error response, depending on what routes exist yet), confirming the install succeeded.

## Common Mistakes

- Running `composer create-project` inside an existing project folder instead of in the parent directory — the command creates a brand-new subdirectory for you; you should not `mkdir` and `cd` into an empty folder first.
- Forgetting the `-t public` flag when starting PHP's built-in server, which would serve the entire project root (including `.env` and `vendor/`) instead of routing everything through `public/index.php` as intended.
- Expecting a fresh skeleton project to include database support, security, or Twig out of the box — the skeleton deliberately includes almost nothing beyond routing and the HTTP foundation; every other capability is added deliberately via `composer require` as your application needs it.

## Best Practices

- Always create new Symfony projects from `symfony/skeleton` when you want to understand and control exactly what gets added, rather than starting from a heavier pre-built template that hides decisions from you.
- Commit the generated `composer.json` and `composer.lock` to version control (just like any other Composer project), but never commit `vendor/` — teammates and deployment servers run `composer install` to regenerate it from the lock file.
- After creating a project, immediately check it into version control before adding any packages, so you have a clean baseline to compare against as the project grows.

## Exercise

Write the exact sequence of terminal commands you would run to: (1) create a new Symfony skeleton project named `blog-api`, (2) move into its directory, and (3) start PHP's built-in development server serving the correct directory on port 8080.

## Solution

```bash
composer create-project symfony/skeleton blog-api
cd blog-api
php -S localhost:8080 -t public
```

The first command creates the `blog-api` folder with a minimal Symfony install inside it. The second moves your working directory into that new project. The third starts a local development server bound to port 8080, explicitly pointing it at the `public/` folder so that `public/index.php` handles every incoming request, exactly as Symfony's architecture expects.

## Mini Quiz

```yaml
- question: "What tool is used to create a new Symfony project in this course?"
  options:
    - "A dedicated Symfony CLI binary installed globally"
    - "Composer, via `composer create-project symfony/skeleton`"
    - "npm"
    - "A GUI installer downloaded from symfony.com"
  answer: 1
  explanation: "This course uses Composer exclusively; Symfony packages, including the skeleton starting point, are ordinary Composer packages installed with `composer create-project`."

- question: "What does the symfony/skeleton package provide?"
  options:
    - "A fully featured application with Twig, forms, and security pre-installed"
    - "Only the vendor/ folder, with no folder structure"
    - "A minimal starting project with just enough to boot Symfony, to which you add packages as needed"
    - "A database already populated with sample data"
  answer: 2
  explanation: "The skeleton is intentionally minimal — it boots Symfony and provides the conventional folder structure, but leaves you to add specific capabilities (database, security, templating, etc.) deliberately via Composer."

- question: "Why does `php -S localhost:8000 -t public` specify `-t public`?"
  options:
    - "To tell PHP to only run on port 'public'"
    - "To make Composer install packages into that folder"
    - "To serve files from the public/ directory, so all requests are routed through public/index.php as Symfony expects"
    - "It has no real effect and can be omitted safely"
  answer: 2
  explanation: "The -t flag sets the document root for PHP's built-in server; pointing it at public/ ensures every request goes through public/index.php, matching Symfony's single front-controller architecture."
```
