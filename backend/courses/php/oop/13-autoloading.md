---
slug: class-autoloading
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Autoloading Classes in PHP"
difficulty: beginner
order: 13
estimatedMinutes: 10
tags: [php, oop, autoloading]
---

## Learning Objective

By the end of this lesson, you will be able to explain how class autoloading works, register a simple autoloader with `spl_autoload_register()`, and connect the idea to Composer's PSR-4 autoloader.

## Explanation

In the early days of PHP, every class file needed a manual `require` before the class could be used. For ten classes that meant ten `require` lines in the right order — fragile and tedious. **Autoloading** removes that chore: you register a function that, given a class name, finds and includes the right file automatically the first time the class is mentioned.

The registration function is `spl_autoload_register()`, which takes a **callback** (a function passed as a value to be called later). When PHP encounters an unknown class such as `Acme\Shop\Basket`, it calls each registered autoloader with that full name. A minimal autoloader converts backslashes to directory separators, appends `.php`, and requires the result if it exists.

Composer builds a faster, cached version of this same idea from your `composer.json` PSR-4 mappings. Understanding the manual version first makes Composer's generated `vendor/autoload.php` feel like a natural upgrade rather than magic.

## Why It Matters

Every modern PHP project — and the entire Symfony framework — depends on autoloading. Classes are referenced by namespace across hundreds of files with zero manual includes. Knowing what happens behind `new Basket()` turns "class not found" errors from mysteries into straightforward path-mapping bugs.

## Example

A tiny shop project keeps classes under `src/` following PSR-4: `Acme\Shop\Basket` lives in `src/Basket.php`. A five-line autoloader maps the prefix to the folder so the class loads on first use.

## Code

```php
<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'Acme\\Shop\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$basket = new \Acme\Shop\Basket();
echo $basket->count() . ' items in basket.';
```

## Code Explanation

- `spl_autoload_register(...)` hands PHP a callback to invoke whenever an unknown class is referenced.
- The guard clause ignores classes outside the `Acme\Shop\` prefix so other autoloaders (or Composer's) can handle them.
- `str_replace('\\', '/', ...)` converts namespace separators into folder separators, turning `Cart\Basket` into `Cart/Basket`, then appends `.php` for the file path.
- `new \Acme\Shop\Basket()` triggers the callback on first mention, loading `src/Basket.php` just in time with no manual `require`.

## Common Mistakes

- **Mismatching class name and filename.** `Basket` must live in `Basket.php` with matching capitalization, or the built path points nowhere.
- **Registering the autoloader after using the class.** Registration must run first, typically at the top of the entry script.
- **Writing a second custom autoloader instead of using Composer's.** One-off autoloaders teach the concept, but real projects should rely on Composer's tested implementation.

## Best Practices

- Follow PSR-4 exactly (prefix to folder, sub-namespaces to subfolders) so any autoloader can resolve your classes.
- Register autoloaders once at the application entry point, never deep inside class files.
- Prefer Composer's autoloader in real projects and keep hand-written ones for learning exercises.

## Exercise

Map the prefix `Acme\Blog\` to the folder `lib/` in an autoloader callback. In which file would class `Acme\Blog\Posts\Archive` be found?

## Solution

The callback strips the `Acme\Blog\` prefix, converts `Posts\Archive` to `Posts/Archive`, and requires `lib/Posts/Archive.php`. The class therefore lives in `lib/Posts/Archive.php` relative to the project.

## Mini Quiz

```yaml
- question: "What does spl_autoload_register() do?"
  options:
    - "It deletes unused classes"
    - "It registers a callback PHP calls to load an unknown class on first use"
    - "It connects PHP to MySQL"
    - "It formats code in PSR-12 style"
  answer: 1
  explanation: "The registered function receives the missing class name and includes the matching file, so no manual require is needed."
- question: "How does a PSR-4 autoloader turn a class name into a path?"
  options:
    - "It picks a random file from the project"
    - "It strips the mapped prefix, converts backslashes to slashes, and appends .php"
    - "It downloads the file from Packagist every time"
    - "It reads the database for a file location"
  answer: 1
  explanation: "Prefix-to-folder mapping plus separator conversion is the entire PSR-4 rule, which is why matching names and paths matters."
- question: "Why do real projects prefer Composer's autoloader over hand-written ones?"
  options:
    - "Hand-written loaders are illegal in production"
    - "Composer's loader is tested, cached, and handles all vendor packages plus your own mappings uniformly"
    - "Composer's loader makes classes run faster line by line"
    - "There is no difference at all"
  answer: 1
  explanation: "Composer generates one optimized map covering every dependency and your own code, replacing fragile custom callbacks."
```
