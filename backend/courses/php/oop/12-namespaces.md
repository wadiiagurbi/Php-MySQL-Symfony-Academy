---
slug: namespaces
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: oop
chapterTitle: "Chapter 5 — Object-Oriented PHP"
chapterOrder: 5
title: "Namespaces in PHP"
difficulty: beginner
order: 12
estimatedMinutes: 10
tags: [php, oop, namespaces]
---

## Learning Objective

By the end of this lesson, you will be able to organize classes into namespaces, import them with `use`, and resolve name collisions between same-named classes.

## Explanation

A **namespace** is a named container that groups related classes, much like folders group files. Without namespaces, every class name in a project (including all vendor libraries) would share one flat list, and two libraries defining a `Logger` class would collide irreparably.

You declare a namespace at the very top of a file with `namespace Acme\Blog;`, where backslashes separate levels. From then on, the class `Post` in that file is globally known as `Acme\Blog\Post`, its **fully qualified name**. Other files bring it into scope with an **import** line like `use Acme\Blog\Post;`, after which the short name `Post` works. If two imported classes share a short name, you alias one: `use Acme\Blog\Post as BlogPost;`.

Namespace prefixes also drive PSR-4 autoloading: the prefix `Acme\Blog\` mapped to `src/` tells Composer exactly where each namespaced class file lives.

## Why It Matters

Namespaces are what let hundreds of Composer packages coexist in one project without stepping on each other. Symfony code is namespaced throughout (`Symfony\Component\HttpFoundation\Request`), so reading and writing `use` imports fluently is required for everything that follows in this course.

## Example

A project uses two `Logger` classes: one from its own code (`Acme\Blog\Logger`) and one from a vendor package (`Vendor\Monitor\Logger`). Aliased imports let one script use both side by side.

## Code

```php
<?php

namespace Acme\Blog;

class Logger
{
    public function log(string $message): string
    {
        return '[blog] ' . $message;
    }
}
```

## Code Explanation

- `namespace Acme\Blog;` places this `Logger` inside the project's own container, so its full name is `Acme\Blog\Logger`.
- The class itself is short and focused: `log()` prefixes a message with `[blog]` and returns it.
- A vendor class named `Vendor\Monitor\Logger` can now coexist because the two full names differ, even though both short names are `Logger`.
- A consumer file would write `use Acme\Blog\Logger as BlogLogger;` and `use Vendor\Monitor\Logger;` to employ both without ambiguity.

## Common Mistakes

- **Putting code before the `namespace` line.** Only `declare` may precede it; anything else triggers a fatal error.
- **Forgetting the `use` import and calling the short name.** PHP then looks for the class in the current namespace instead of the intended one.
- **Treating namespaces as folders that must be created manually.** Folders matter for PSR-4 file lookup, but the namespace itself is declared in code, not created on disk.

## Best Practices

- Give every file exactly one namespace matching its PSR-4 path, and one class per file.
- Import each class with `use` at the top instead of writing fully qualified names inline throughout the body.
- Alias with `as` whenever two useful classes share a short name, choosing a descriptive local name.

## Exercise

You need both `Acme\Shop\Basket` and `Vendor\Promo\Basket` in one script. Write the two `use` statements (aliasing at least one) and instantiate each class once.

## Solution

```php
<?php

use Acme\Shop\Basket;
use Vendor\Promo\Basket as PromoBasket;

$shopBasket = new Basket();
$promoBasket = new PromoBasket();
```

Each import brings one `Basket` into scope under a distinct local name, so both classes can be constructed without collision.

## Mini Quiz

```yaml
- question: "What problem do namespaces solve?"
  options:
    - "They make PHP run faster"
    - "They group classes into containers so same-named classes from different authors can coexist"
    - "They replace the need for classes entirely"
    - "They encrypt database passwords"
  answer: 1
  explanation: "Namespaces prefix class names with a vendor path, so Acme\\Blog\\Logger and Vendor\\Monitor\\Logger are distinct classes."
- question: "What does the line use Acme\\Blog\\Post; do?"
  options:
    - "It deletes the Post class"
    - "It imports the class so the short name Post can be used in this file"
    - "It creates a database table named posts"
    - "It downloads the class from the internet"
  answer: 1
  explanation: "A use import maps the fully qualified name to a short local name for the rest of the file."
- question: "How do you use two classes that share the same short name?"
  options:
    - "It is impossible to use both"
    - "Alias at least one with as, for example use Vendor\\Promo\\Basket as PromoBasket;"
    - "Rename the files on disk randomly"
    - "Merge both classes into one file"
  answer: 1
  explanation: "Aliasing gives each class a distinct local name, removing the ambiguity between the two short names."
```
