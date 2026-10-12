---
slug: include-and-require
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "include and require"
difficulty: beginner
order: 9
estimatedMinutes: 9
tags: [include, require, code-reuse]
---

## Learning Objective

By the end of this lesson, you will be able to reuse PHP code across multiple files using `include`, `require`, `include_once`, and `require_once`, and you will understand the difference between them and when to use each.

## Explanation

As a website grows beyond a single page, you will quickly notice that many pages share the same pieces: the same header with a logo and navigation menu, the same footer with copyright text, or the same code that connects to a database. Copying and pasting that code into every single file is a maintenance nightmare — if you need to change the navigation menu, you would have to edit every single page that contains a copy of it.

PHP solves this with `include` and `require`. Both statements take a file path and insert that file's content directly into the current script at the point where they are called, as if you had typed that file's code right there yourself.

```php
include 'header.php';
```

This runs `header.php` right there in the middle of the current script, and any variables already defined in the current script are available inside the included file, and vice versa.

The difference between `include` and `require` is how PHP reacts if the file cannot be found:

- `include` will produce a **warning** and then keep running the rest of the script.
- `require` will produce a **fatal error** and **stop the script immediately**.

You choose based on how essential the file is. If a small decorative element is missing, your page can probably still function without it, so `include` might be acceptable. But if the missing file was supposed to set up your database connection or define a function your whole page depends on, continuing to run without it would likely cause more confusing errors later — so `require` is the safer, more honest choice.

There is a second pair of related statements: `include_once` and `require_once`. These behave exactly like `include` and `require`, except that PHP keeps track of which files have already been included with the `_once` version, and if the same file is included a second time, it is simply skipped. This matters because including the same file twice — for example, a file that defines a function or a class — would cause a "cannot redeclare function/class" fatal error the second time PHP tries to process those definitions.

## Why It Matters

Nearly every real PHP website is built from small reusable pieces rather than one giant file. A shared header and footer file, a shared file that opens the database connection, a shared file of helper functions used by many pages — all of these are normally written once and then pulled into every page that needs them using `include` or `require`. This keeps a codebase consistent (one change updates every page that uses that file) and much easier to maintain than duplicating code everywhere.

## Example

A small website has a shared header file with the site's navigation menu, and a separate configuration file that defines the database connection details. The homepage brings both of these together.

## Code

```php
<?php
// config.php would normally define constants or settings, for example:
// define('DB_HOST', 'localhost');

require_once __DIR__ . '/config.php';   // essential setup, only load once
include __DIR__ . '/partials/header.php'; // shared visual header

echo "<main>Welcome to our homepage!</main>";

include __DIR__ . '/partials/footer.php'; // shared visual footer
```

## Code Explanation

- `require_once __DIR__ . '/config.php'` loads the configuration file. `require` is used because the rest of the page likely cannot function correctly without it, and `_once` prevents an error if some other included file also happens to load `config.php`.
- `__DIR__` is a special PHP constant that always contains the full path to the folder of the current file, which makes file paths reliable no matter which page includes them, instead of relying on a guessed relative path.
- `include __DIR__ . '/partials/header.php'` inserts the shared header content (menu, logo, etc.) at this exact spot in the output. `include` is used here because a missing header is unfortunate but probably should not stop the whole page from working.
- The `echo` line in between shows that regular PHP code can sit right between two included files, mixed in naturally.
- `include __DIR__ . '/partials/footer.php'` inserts the shared footer the same way, at the bottom of the page.

## Common Mistakes

- **Including a file that defines a function or class more than once without `_once`.** This causes a fatal "cannot redeclare" error the second time PHP encounters the same definition.
- **Using `include` for something the application cannot run without**, such as a database configuration file, and not noticing when it silently fails with only a warning, leading to confusing errors further down the script.
- **Relying on relative paths like `include 'header.php'`** without accounting for the fact that the "current directory" PHP uses to resolve relative paths depends on which script was originally run, not necessarily the file doing the including. Using `__DIR__` avoids this entirely.
- **Forgetting that variables set in the including file are visible inside the included file, and vice versa**, which can lead to unexpected overwriting of variable values if you are not careful about naming.

## Best Practices

- Use `require` or `require_once` for anything your script truly cannot function without, such as configuration or core setup files.
- Use `include` for optional or purely decorative pieces, where the rest of the page should still work if it is missing.
- Prefer the `_once` variants whenever the included file defines functions, classes, or constants, to avoid redeclaration errors.
- Always build include paths with `__DIR__` (or a similar reliable base) rather than plain relative strings, so your includes keep working no matter where a script is run from.

## Exercise

Create three files in your mind (or on disk if you want to try it): `greeting.php`, which defines a function `sayHello($name)` that echoes a greeting; and a main script that uses `require_once` to load `greeting.php`, then calls `sayHello('Wadii')`.

## Solution

```php
<?php
// greeting.php
function sayHello(string $name): void
{
    echo "Hello, $name! Welcome to the site.";
}
```

```php
<?php
// main.php
require_once __DIR__ . '/greeting.php';

sayHello('Wadii');
```

`require_once` is the right choice here because `greeting.php` defines a function, and functions cannot be redeclared — if this file were ever accidentally included twice (directly or through another included file), a plain `require` or `include` would cause a fatal error the second time, while `require_once` simply skips loading it again. After the file is loaded, `sayHello()` becomes available in `main.php` exactly as if it had been defined there directly, and calling `sayHello('Wadii')` prints the greeting.

## Mini Quiz

```yaml
- question: "What happens if a file loaded with require is missing?"
  options:
    - "PHP shows a warning and continues running"
    - "PHP throws a fatal error and stops the script"
    - "PHP silently ignores it"
    - "PHP automatically creates an empty file"
  answer: 1
  explanation: "require causes a fatal error and halts script execution if the file cannot be found, unlike include which only warns."
- question: "When should you use include_once or require_once instead of include or require?"
  options:
    - "Always, for every single file"
    - "Never, they are deprecated"
    - "When the included file defines functions, classes, or constants that must not be declared twice"
    - "Only when including images"
  answer: 2
  explanation: "The _once variants prevent a file from being loaded a second time, avoiding redeclaration errors for functions, classes, or constants."
- question: "What does the __DIR__ constant provide?"
  options:
    - "The name of the current function"
    - "The full path to the folder containing the current file"
    - "The current date and time"
    - "The visitor's IP address"
  answer: 1
  explanation: "__DIR__ always resolves to the directory of the file it's used in, making include/require paths reliable regardless of how the script was invoked."
```
