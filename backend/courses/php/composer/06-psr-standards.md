---
slug: psr-standards
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "PSR Standards in PHP"
difficulty: beginner
order: 6
estimatedMinutes: 9
tags: [php, psr, standards]
---

## Learning Objective

By the end of this lesson, you will be able to explain what PSR standards are, recognize PSR-4 autoloading and PSR-12 code style, and describe why shared standards help PHP teams.

## Explanation

**PSR** stands for **PHP Standard Recommendation**: a numbered, public document describing one agreed way of doing something in PHP, published by the **PHP-FIG** (Framework Interop Group), a group of developers from major PHP projects including Symfony. A PSR is not part of the PHP language itself — PHP runs fine without following any PSR — but it is a voluntary agreement that lets code from different authors work well together.

Two PSRs matter most for beginners. **PSR-4** defines autoloading: how a fully qualified class name like `Acme\Blog\Post` maps to a file path like `src/Post.php`. **PSR-12** defines code style: where braces go, how many spaces to indent, how `use` imports are ordered, and similar formatting rules, so code from different projects looks familiar.

Other PSRs cover shared interfaces, such as **PSR-3** for loggers (which is why Monolog can plug into many frameworks) and **PSR-7** for HTTP messages. Following PSRs means your code fits naturally into the wider PHP ecosystem.

## Why It Matters

Before shared standards, every project organized classes differently, so combining two libraries often meant painful rewiring. Because most Composer packages follow PSR-4, they install and autoload side by side with no conflicts. Because many teams follow PSR-12, you can read Symfony, Laravel, or coworker code and feel at home immediately.

## Example

Two developers each publish a small package, and both follow PSR-4 with distinct vendor prefixes. A third developer installs both packages, and the classes from each load correctly with no extra configuration.

## Code

```php
<?php

// PSR-4: one class per file, namespace matches the folder structure.
// File: src/Notifier.php, namespace Acme\Blog, class Notifier.
namespace Acme\Blog;

class Notifier
{
    public function notify(string $message): string
    {
        return 'Notice: ' . $message;
    }
}
```

## Code Explanation

- `namespace Acme\Blog;` declares the class prefix, which under PSR-4 corresponds to the `src/` folder configured in `composer.json`.
- The file is named `Notifier.php` to match the class name `Notifier` exactly, which is a PSR-4 requirement for the mapping to work.
- The brace placement, four-space indentation, and spacing around parentheses follow **PSR-12**, the shared code-style recommendation.
- Because both the naming and the style follow standards, this class can be published as a package and used inside any other PSR-4 project without modification.

## Common Mistakes

- **Treating PSRs as PHP syntax errors.** PHP itself does not enforce PSRs; they are community agreements checked by optional tools, not by the interpreter.
- **Mixing two autoloading schemes in one project.** Sticking to PSR-4 everywhere keeps the class map predictable; custom schemes add confusion.
- **Ignoring style entirely because "it still runs".** Code that runs but looks alien slows down every future reader, including yourself in three months.

## Best Practices

- Use PSR-4 for all new code: one class per file, matching namespace and filename.
- Format new code in PSR-12 style from the start rather than reformatting later.
- When evaluating a third-party package, prefer ones that advertise PSR compliance, since they integrate more cleanly.

## Exercise

A package uses the prefix `Acme\Shop\` mapped to `src/`. Where must the file for class `Acme\Shop\Orders\Receipt` live, and which PSR defines that rule?

## Solution

The file must be `src/Orders/Receipt.php`, because each namespace segment after the prefix becomes a subfolder and the filename matches the class. That mapping rule is defined by **PSR-4**.

## Mini Quiz

```yaml
- question: "What is a PSR in the PHP world?"
  options:
    - "A mandatory PHP language keyword"
    - "A published community recommendation describing one agreed way of doing something"
    - "A type of MySQL database index"
    - "A Composer command for deleting packages"
  answer: 1
  explanation: "PSRs are voluntary standards from the PHP-FIG that help code from different authors interoperate."
- question: "What does PSR-4 standardize?"
  options:
    - "Database passwords"
    - "How class names map to file paths for autoloading"
    - "HTML page layouts"
    - "Server firewall rules"
  answer: 1
  explanation: "PSR-4 defines the rule turning a class like Acme\\Blog\\Post into a path like src/Post.php."
- question: "What does PSR-12 standardize?"
  options:
    - "Network port numbers"
    - "Code style such as indentation, brace placement, and import ordering"
    - "SQL query execution speed"
    - "The price of commercial PHP tools"
  answer: 1
  explanation: "PSR-12 is the shared code-style guide, so code from different projects looks consistent and familiar."
```
