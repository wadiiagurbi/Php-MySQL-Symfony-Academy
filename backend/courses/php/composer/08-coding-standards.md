---
slug: php-coding-standards
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "PHP Coding Standards"
difficulty: beginner
order: 8
estimatedMinutes: 9
tags: [php, style, quality]
---

## Learning Objective

By the end of this lesson, you will be able to format PHP code in PSR-12 style and use an automated fixer to keep a project consistently styled.

## Explanation

A **coding standard** is a set of formatting and naming rules a team agrees to follow: indentation width, brace placement, spacing, line length, and file layout. For PHP, the shared standard is **PSR-12**, which extends the older PSR-2. Following one standard means any PHP developer can read your code without adjusting to a new visual dialect on every project.

Nobody enforces these rules by hand. A **linter** or **fixer** tool reads your files and either reports violations or rewrites the code automatically. The most popular fixer for PHP is **PHP-CS-Fixer**, itself installed as a Composer development dependency. After a short configuration, running one command reformats every file in the project to the chosen rule set.

Typical PSR-12 rules include: four spaces per indent level (never tabs), opening braces for classes and methods on their own line, one `use` import per line in alphabetical order, and declaring strict types where the project requires it.

## Why It Matters

Inconsistent formatting creates constant low-level friction: pull requests fill with style comments instead of logic discussion, merges conflict over whitespace, and newcomers waste time guessing the local conventions. Automated style keeps every file uniform, so reviews focus on behavior and the codebase feels like it was written by one careful author.

## Example

A learner writes a working but messily formatted class with mixed indentation and crowded braces. Running PHP-CS-Fixer rewrites it into clean PSR-12 shape with no behavior change.

## Code

```php
<?php

declare(strict_types=1);

namespace Acme\Blog;

class Post
{
    public function __construct(
        private string $title,
        private string $body
    ) {
    }

    public function headline(): string
    {
        return $this->title . ' — ' . $this->body;
    }
}
```

## Code Explanation

- `declare(strict_types=1);` enables strict type checking for the file, so passing a wrong type raises an error instead of coercing silently.
- The `namespace` line followed by one `use` per line (none needed here) matches PSR-12 import ordering rules.
- The class opening brace sits on its own line, methods indent one level with four spaces, and multi-line parameter lists put each parameter on its own line.
- Spacing like `__construct(` with no space before the parenthesis and `): string` with a single space before the return type follows the standard exactly.

## Common Mistakes

- **Mixing tabs and spaces for indentation.** PSR-12 requires spaces, and mixed files render differently in every editor.
- **Reformatting the entire codebase in one unrelated pull request.** Huge style-only diffs hide real changes; format new code first and migrate old files gradually.
- **Arguing about style instead of automating it.** Human style review is slow and inconsistent; a fixer settles debates permanently.

## Best Practices

- Install PHP-CS-Fixer as a `require-dev` dependency and commit its configuration file.
- Run the fixer before every commit so style violations never reach code review.
- Keep line lengths readable (around 120 characters) even where the standard permits longer lines.

## Exercise

Your teammate's file uses two-space indentation, opening braces on the same line as the class name, and three `use` imports crammed on one line. List the three PSR-12 corrections the fixer would apply.

## Solution

The fixer would convert indentation to four spaces, move the class opening brace onto its own line, and split the imports so each `use` statement occupies its own line in alphabetical order.

## Mini Quiz

```yaml
- question: "What is a coding standard such as PSR-12?"
  options:
    - "A law enforced by the PHP interpreter"
    - "An agreed set of formatting and naming rules that keeps code consistent"
    - "A database backup schedule"
    - "A web server configuration file"
  answer: 1
  explanation: "Coding standards define shared formatting rules so code from many authors reads uniformly; tools then enforce them."
- question: "What does PHP-CS-Fixer do?"
  options:
    - "It deploys applications to production"
    - "It automatically rewrites PHP files to match a configured style rule set"
    - "It deletes unused database tables"
    - "It translates PHP code into JavaScript"
  answer: 1
  explanation: "PHP-CS-Fixer scans project files and reformats violations automatically, so style stays consistent without manual effort."
- question: "Which of these matches PSR-12 style?"
  options:
    - "Tab indentation with braces on the same line everywhere"
    - "Four-space indentation, class braces on their own line, one use import per line"
    - "No indentation at all to save disk space"
    - "Random spacing chosen per file"
  answer: 1
  explanation: "PSR-12 requires four-space indents,own-line braces for classes and methods, and one ordered import per line."
```
