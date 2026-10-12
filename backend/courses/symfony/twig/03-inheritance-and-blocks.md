---
slug: inheritance-and-blocks
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: twig
chapterTitle: "Chapter 4 — Twig & Templates"
chapterOrder: 4
title: "Inheritance and Blocks"
difficulty: beginner
order: 3
estimatedMinutes: 11
tags: [symfony, twig, inheritance, blocks]
---

## Learning Objective

Understand Twig template inheritance: how a base layout defines reusable **blocks**, and how child pages extend it and override only the parts that differ.

## Explanation

Without inheritance, every page in your app would repeat the same HTML boilerplate: the `<head>` section, navigation bar, and footer copied into dozens of files. Changing one menu link would mean editing every file — a maintenance nightmare. **Template inheritance** solves this the same way PHP class inheritance does: you define the shared structure once in a parent template, and child templates inherit it while overriding only specific named sections.

The parent template (by convention `templates/base.html.twig` in Symfony projects) declares **blocks** — named placeholders for content that children may replace — using `{% block title %}Default title{% endblock %}`. The text between the tags is the default, shown when no child overrides that block. A typical base layout defines blocks like `title` (the page `<title>`) and `body` (the main content), wrapping them in the full HTML skeleton with navigation and footer.

A child template starts with `{% extends 'base.html.twig' %}` on its very first line, which tells Twig "start from the parent layout." It then overrides blocks by redeclaring them: `{% block body %}...child HTML...{% endblock %}`. Anything in the child file *outside* a block is ignored, which surprises beginners — only block content gets injected into the parent's skeleton. A child can also call `{{ parent() }}` inside a block to include the parent's default content plus its own additions, useful for appending extra stylesheets or scripts.

## Why It Matters

Real Symfony projects often have hundreds of templates sharing one or two layouts. Inheritance means a redesign of the navigation or footer touches exactly one file. During onboarding, finding `base.html.twig` immediately answers "where does the site header come from," and every new page you build starts with a two-line `extends` plus the blocks it needs.

## Example

The task app's pages should all share one layout with a navigation bar and footer. The task-list page extends the base layout, sets its own page title, and fills only the `body` block with the task list markup.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('page/about.html.twig', [
            'pageTitle' => 'About our task app',
        ]);
    }
}
```

## Code Explanation

- The controller stays simple: it renders `page/about.html.twig` and passes a `pageTitle` variable, knowing nothing about layouts or blocks.
- The child template `page/about.html.twig` begins with `{% extends 'base.html.twig' %}` to inherit the shared HTML skeleton.
- It overrides `{% block title %}{{ pageTitle }}{% endblock %}` so the browser tab shows the per-page title instead of the default.
- It overrides `{% block body %}` with the about-page content; the navigation bar and footer come automatically from the base layout without being repeated.
- Adding a new page later means creating one small template with two blocks — no copying of headers or footers required.

## Common Mistakes

- Writing content in a child template outside any `{% block %}`. Twig silently ignores it, leaving beginners staring at a blank page wondering why their HTML never appears.
- Putting `{% extends %}` anywhere except the very first line of the child template — Twig requires it to be first (comments aside), or it throws an error.
- Overriding a block name that does not exist in the parent (a typo like `{% block boddy %}`). Twig renders nothing for it instead of warning you, so double-check block names against the base layout.

## Best Practices

- Keep one `base.html.twig` with well-named blocks (`title`, `body`, plus `stylesheets` and `javascripts` for per-page assets) and extend it from every page.
- Use `{{ parent() }}` when a child only wants to *add to* a block (like an extra script tag) rather than replace it entirely.
- Put reusable fragments that are not full pages (a task row, a pagination widget) in separate partial templates and pull them in with `{% include %}` instead of duplicating markup across children.

## Exercise

The base layout defines `title` and `body` blocks. Write the complete `page/about.html.twig` child template that extends `base.html.twig`, sets the title block to "About", and puts a paragraph "We love organized tasks." in the body block.

## Solution

Start the file with `{% extends 'base.html.twig' %}`, then add `{% block title %}About{% endblock %}` and `{% block body %}<p>We love organized tasks.</p>{% endblock %}`. The extends line pulls in the shared layout; each block replaces the matching placeholder in the parent while everything else (navigation, footer) is inherited untouched.

## Mini Quiz

```yaml
- question: "What is the purpose of template inheritance in Twig?"
  options:
    - "To copy the entire parent file into each child automatically"
    - "To define shared page structure once in a parent layout that children extend and customize via blocks"
    - "To connect templates directly to the database"
    - "To make templates render faster by skipping the controller"
  answer: 1
  explanation: "Inheritance keeps one copy of the shared skeleton (head, nav, footer) in the parent; children override only named blocks with their page-specific content."
- question: "What happens to HTML written in a child template outside any {% block %}?"
  options:
    - "It appears at the top of the rendered page"
    - "It is ignored; only block content is injected into the parent layout"
    - "It replaces the entire parent layout"
    - "It causes a database migration to run"
  answer: 1
  explanation: "When a template extends a parent, Twig renders the parent skeleton and only substitutes the child's block contents; stray markup outside blocks is discarded."
- question: "What does {{ parent() }} do inside an overridden block?"
  options:
    - "It deletes the parent template file"
    - "It renders the parent block's default content at that spot, so the child can add to it instead of replacing it"
    - "It redirects to the parent page URL"
    - "It lists all parent templates in the project"
  answer: 1
  explanation: "parent() outputs what the parent block would have rendered, letting the child wrap or extend the default content rather than discarding it."
```
