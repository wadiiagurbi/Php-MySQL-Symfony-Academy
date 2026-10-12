---
slug: assets-and-functions
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: twig
chapterTitle: "Chapter 4 — Twig & Templates"
chapterOrder: 4
title: "Assets and Twig Functions"
difficulty: beginner
order: 4
estimatedMinutes: 10
tags: [symfony, twig, assets, functions]
---

## Learning Objective

Learn how to reference CSS, JavaScript, and image files with Twig's `asset()` function and how to generate links to routes with `path()`, so your templates stay correct in every environment.

## Explanation

**Assets** are the static files your pages need: stylesheets (`.css`), JavaScript files (`.js`), and images. In Symfony they live in the `public/` directory (for example `public/css/app.css`), because only files under `public/` are directly reachable by browsers — everything else in the project stays private to the server. A naive template might hard-code `<link href="/css/app.css">`, and that works until your app is deployed under a subdirectory or behind a **CDN (Content Delivery Network)** — a service that serves your static files from fast servers around the world — at which point every hard-coded URL breaks.

Twig's `asset()` **function** (a helper you call from inside a template, like `{{ asset('css/app.css') }}`) generates the correct public URL for a file, including any versioning or CDN prefix configured for the current **environment** (a named setup such as `dev` for your machine or `prod` for the live server). If the filename gains a version hash for cache-busting in production, `asset()` handles it automatically while hard-coded paths serve stale files.

Similarly, the `path()` function generates URLs to your application's own routes from their names: `{{ path('app_tasks') }}` produces `/tasks` without you writing the path by hand. If you later rename a route's path from `/tasks` to `/my-tasks`, every template using `path('app_tasks')` keeps working, while hard-coded `/tasks` links silently break. (Its sibling `url()` does the same but returns an absolute URL with the domain, for emails and external sharing.)

## Why It Matters

Hard-coded asset paths and links are one of the most common reasons "it works locally but breaks in production." Teams rely on `asset()` and `path()` so that deploys under subdirectories, CDN prefixes, and route renames require zero template edits. Spotting hard-coded `/css/...` or `/tasks` URLs in a code review is a quick, high-value catch.

## Example

The base layout needs to load the global stylesheet, show the project logo, and link the navigation item to the task list page — all in a way that keeps working when the app is deployed under a subdirectory in production.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'taskCount' => 12,
        ]);
    }
}
```

## Code Explanation

- The controller renders `dashboard/index.html.twig` with a `taskCount` variable; it does not deal with asset URLs or link building at all.
- Inside that template, `<link rel="stylesheet" href="{{ asset('css/app.css') }}">` produces the correct stylesheet URL for the current environment, including any versioning prefix.
- `<img src="{{ asset('images/logo.png') }}" alt="Task app logo">` references the logo file stored at `public/images/logo.png` in an environment-safe way.
- `<a href="{{ path('app_tasks') }}">All tasks</a>` builds the navigation link from the route name, so renaming the route's path later never breaks this link.
- `{{ taskCount }}` prints the controller-provided number, showing how passed variables and Twig functions mix naturally in one template.

## Common Mistakes

- Hard-coding asset URLs like `/css/app.css` instead of using `asset()`. They break under subdirectory deploys and skip cache-busting versioning.
- Hard-coding internal links like `<a href="/tasks">` instead of `path('app_tasks')`. Renaming the route path then silently breaks navigation.
- Referencing files that are not under `public/` (e.g., `src/images/logo.png`). Browsers can only load files from the public web root, so images must live in `public/images/`.

## Best Practices

- Always use `asset()` for CSS, JS, and images, and `path()` for internal links — no hard-coded public URLs in templates.
- Store source images and styles under `public/` (or build them there with your frontend pipeline) so `asset()` can resolve them.
- Use `path()` for in-app links and reserve `url()` (absolute URLs) for contexts outside the page, like emails and social sharing meta tags.

## Exercise

In a Twig template, write: (1) a `<link>` tag loading the stylesheet at `css/report.css` via `asset()`, and (2) a link to the route named `app_dashboard` labeled "Dashboard" using `path()`.

## Solution

Write `<link rel="stylesheet" href="{{ asset('css/report.css') }}">` for the stylesheet and `<a href="{{ path('app_dashboard') }}">Dashboard</a>` for the link. Both helpers generate environment-correct URLs: `asset()` resolves the public file path with versioning, and `path()` builds the URL from the route name so future path changes never break the template.

## Mini Quiz

```yaml
- question: "Why should templates use asset() instead of hard-coded paths like /css/app.css?"
  options:
    - "Hard-coded paths load faster in every browser"
    - "asset() generates environment-correct URLs including subdirectory prefixes and cache-busting versions"
    - "Browsers refuse to load hard-coded CSS files"
    - "asset() converts CSS files into JavaScript automatically"
  answer: 1
  explanation: "asset() accounts for the deployment setup (subdirectories, CDN, versioned filenames), while hard-coded paths break as soon as any of those change."
- question: "What does {{ path('app_tasks') }} output in a template?"
  options:
    - "The number of tasks in the database"
    - "The URL of the route named app_tasks, so links survive route path renames"
    - "A list of all registered routes"
    - "The raw Twig source of the task template"
  answer: 1
  explanation: "path() looks up the route by its unique name and generates its current URL, decoupling templates from the literal path string."
- question: "Where must images, CSS, and JS files live so browsers can load them in Symfony?"
  options:
    - "In src/Controller/ next to the controllers"
    - "Under the public/ directory, which is the web root"
    - "In the var/cache/ directory"
    - "Inside the Twig template files themselves"
  answer: 1
  explanation: "Only files under public/ are directly served to browsers; everything outside it stays private to the server."
```
