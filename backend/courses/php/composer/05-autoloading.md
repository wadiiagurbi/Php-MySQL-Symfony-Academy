---
slug: composer-autoloading
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "Autoloading with Composer"
difficulty: beginner
order: 5
estimatedMinutes: 10
tags: [php, composer, autoloading]
---

## Learning Objective

By the end of this lesson, you will be able to explain what autoloading is, configure PSR-4 autoloading for your own classes, and use `vendor/autoload.php` in a script.

## Explanation

**Autoloading** means PHP automatically finds and loads a class file the first time that class is mentioned, instead of you writing a `require` statement for every file. Without it, a project with fifty classes would need fifty `require` lines in exactly the right order.

Composer generates this mechanism for you. When it installs packages, it builds a file called `vendor/autoload.php` containing a map of class names to file paths. Including that one file registers an **autoloader** — a function PHP calls whenever it encounters a class it has not loaded yet — so every installed class resolves on demand.

The same system works for your own code through the `autoload` section of `composer.json`. The standard mapping is called **PSR-4**, which says a class prefix corresponds to a folder: `"Acme\\Blog\\": "src/"` means class `Acme\Blog\Post` lives in `src/Post.php`. After changing autoload settings, you run `composer dump-autoload` to rebuild the map.

## Why It Matters

Manual `require` lists do not scale and break whenever files move. Autoloading removes that entire category of errors, makes third-party packages usable with zero extra wiring, and is the mechanism Symfony itself relies on — every Symfony class you will use later loads through this same autoloader.

## Example

A tiny blog has two classes, `Post` and `Renderer`, in a `src/` folder. The developer maps the `Acme\Blog\` prefix to `src/`, includes the autoloader once, and both classes work with no manual `require` calls.

## Code

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Acme\Blog\Post;
use Acme\Blog\Renderer;

$post = new Post('Hello World', 'My first post body.');
$renderer = new Renderer();

echo $renderer->render($post);
```

## Code Explanation

- `require __DIR__ . '/vendor/autoload.php';` registers Composer's autoloader once, which is the only manual include the whole project needs.
- `use Acme\Blog\Post;` imports the class name so the code can write `Post` instead of the full `Acme\Blog\Post` path.
- `new Post(...)` triggers the autoloader behind the scenes: PHP asks Composer where `Acme\Blog\Post` lives, and Composer loads `src/Post.php` automatically.
- `new Renderer()` and `$renderer->render($post)` work the same way, proving that any number of classes resolve through the single autoload file.

## Common Mistakes

- **Forgetting to include `vendor/autoload.php`.** Without it, PHP reports "class not found" even though the files exist on disk.
- **Mismatching the namespace and the folder.** With PSR-4, `Acme\Blog\Post` must live in `src/Post.php` exactly; a wrong folder or filename breaks loading.
- **Skipping `composer dump-autoload` after adding a new class mapping.** The generated map is stale until rebuilt, so new classes stay invisible.

## Best Practices

- Include `vendor/autoload.php` once at the entry point of your application and nowhere else.
- Follow PSR-4 strictly: one class per file, with the filename matching the class name.
- Run `composer dump-autoload` after changing the `autoload` section or when classes are mysteriously not found.

## Exercise

Your `composer.json` maps `"Shop\\": "src/"`. In which file must the class `Shop\Cart\Basket` live, and which single line at the top of your script enables autoloading?

## Solution

The class `Shop\Cart\Basket` must live in `src/Cart/Basket.php`, because each namespace segment after the prefix becomes a subfolder. The enabling line is `require __DIR__ . '/vendor/autoload.php';`, placed once before any class is used.

## Mini Quiz

```yaml
- question: "What does autoloading do in a PHP project?"
  options:
    - "It compresses image files automatically"
    - "It loads a class file on demand the first time that class is referenced"
    - "It backs up the database every hour"
    - "It upgrades Composer to the newest release"
  answer: 1
  explanation: "Autoloading maps class names to files so PHP includes the right file automatically, removing the need for manual require statements."
- question: "With the mapping Acme\\Blog\\ to src/, where does class Acme\\Blog\\Post live?"
  options:
    - "In vendor/Post.php"
    - "In src/Post.php"
    - "In tests/Post.php"
    - "It can live anywhere without rules"
  answer: 1
  explanation: "PSR-4 turns the part after the prefix into a relative path, so Post maps to src/Post.php."
- question: "When should you run composer dump-autoload?"
  options:
    - "After changing autoload configuration or when new classes are not being found"
    - "Before every single page load in production"
    - "Only when deleting the entire project"
    - "Never, the command does nothing"
  answer: 0
  explanation: "dump-autoload rebuilds the generated class map, which is needed after autoload settings change or when the map is out of date."
```
