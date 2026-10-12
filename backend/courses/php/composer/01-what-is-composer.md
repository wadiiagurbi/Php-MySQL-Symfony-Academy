---
slug: what-is-composer
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "What is Composer?"
difficulty: intermediate
order: 1
estimatedMinutes: 8
tags: [php, composer, dependencies]
---

## Learning Objective

Understand what a package/dependency manager is, what problem it solves, and why Composer is the standard tool for managing external code in PHP projects.

## Explanation

As you build larger PHP applications, you will constantly run into problems that other developers have already solved well: parsing dates, sending HTTP requests, validating emails, generating PDFs, and so on. A **package** (also called a **library**) is simply someone else's reusable PHP code, usually organized as classes, that solves one of these problems, published so other developers can use it in their own projects.

Before tools like Composer existed, using someone else's library in PHP meant:

1. Manually finding the library's source code (often a `.zip` file or a Git repository).
2. Downloading it and copying its files into your project, usually into some `lib/` folder.
3. Manually adding `require` or `include` statements for every single PHP file the library contains, in the correct order, so classes were available when needed.
4. If the library itself depended on *other* libraries, repeating this whole process for each of those, by hand.
5. If you ever wanted to upgrade the library to a newer version, deleting the old files and repeating the entire process again, hoping nothing broke.

This is slow, error-prone, and gets dramatically worse as a project accumulates more dependencies — a **dependency** here just means "a package your project relies on to work." If library A depends on library B version 2.x, and library C also depends on library B but version 3.x, resolving that conflict by hand is genuinely difficult.

**Composer** is PHP's dependency manager: a command-line tool that automates this entire process. You tell Composer which packages your project needs (by name and version range) in a file called `composer.json`, and Composer:

- Downloads the correct version of each package and any packages *those* packages depend on (resolving version conflicts automatically where possible).
- Stores everything in a `vendor/` folder inside your project.
- Generates a single autoloading file so every class from every installed package becomes available in your PHP code automatically, without you writing a single manual `require` statement (the next lesson covers this "autoloading" mechanism in detail).
- Lets you upgrade, remove, or add packages with a single command, and records exactly which versions are in use so other developers (or servers) can reproduce the exact same setup.

Composer pulls most packages from **Packagist** (packagist.org), the default public package repository for PHP, which is essentially a searchable catalog of virtually every popular open-source PHP package, including the entire Symfony framework itself.

## Why It Matters

Every real-world PHP project you will encounter professionally — and every Symfony project without exception — is managed with Composer. Symfony itself is not one giant framework file; it is dozens of separate Composer packages (`symfony/http-foundation`, `symfony/routing`, `symfony/console`, and many more) that you install and combine using Composer. Before you can run a single line of Symfony code later in this course, you will run a Composer command to create the project and pull in the framework's packages. Understanding what Composer does now — and what problem it replaces — means the commands you'll type later will make sense as "install this thing that manages my code's outside dependencies," not as unexplained ritual.

## Example

A conceptual before/after: manually downloading and requiring a hypothetical string-formatting library's files one by one, versus running a single Composer command that installs it (and anything it needs) and makes it instantly available in code.

## Code

```bash
# The old, manual way (do NOT do this — shown only for contrast):
# 1. Download library .zip from its website or GitHub
# 2. Unzip into lib/some-library/
# 3. Manually require every file:
#    require 'lib/some-library/src/Formatter.php';
#    require 'lib/some-library/src/Helpers.php';
#    ...and so on for every dependency, in the right order.

# The Composer way — one command installs the package,
# resolves anything IT depends on, and sets up autoloading:
composer require monolog/monolog

# After this, any PHP class from the monolog/monolog package
# (and its own dependencies) is available immediately —
# no manual require statements needed, once this line is added:
# require 'vendor/autoload.php';
```

## Code Explanation

- `composer require monolog/monolog` tells Composer: "add the package named `monolog/monolog` (a real, popular PHP logging library, used later in the Symfony ecosystem) to this project." Composer looks it up on Packagist, downloads it and any of its own dependencies, and records it in `composer.json`.
- Composer places the downloaded package files inside a `vendor/` directory, alongside a generated `vendor/autoload.php` file.
- Including that single `vendor/autoload.php` file once (typically at the very top of your application's entry point) is all you ever need to do — from then on, every class from every installed package can be used directly, with PHP automatically loading the right file behind the scenes the first time each class is referenced.
- Contrast this with the manual `require` list in the comment above: that approach does not scale, breaks silently if you get the file order wrong, and completely falls apart once a library itself has its own dependencies.

## Common Mistakes

- Manually downloading a library's source and copying it into a project folder "to keep things simple," which reintroduces exactly the versioning and update headaches Composer exists to eliminate.
- Assuming Composer packages must come from Symfony or be PHP-framework-specific. Packagist hosts general-purpose PHP packages for virtually any purpose — date handling, image manipulation, PDF generation, HTTP clients, and more.
- Confusing Composer (a PHP-specific dependency manager) with a general build tool or a database migration tool. Composer's job is specifically installing and autoloading PHP code dependencies.

## Best Practices

- Always use Composer to add third-party PHP code to a project, rather than manually copying source files — this preserves the ability to update, track, and reproduce your exact dependency versions later.
- Learn to read package names on Packagist as `vendor/package` (e.g., `monolog/monolog`, `symfony/console`) — the first part identifies the publisher/organization, the second the specific package.
- Before adding a new package to a real project, briefly check Packagist for how actively maintained it is (recent releases, reasonable install count) since you are trusting that code to run inside your application.

## Exercise

Without running any commands (this is a conceptual exercise), explain in your own words what would go wrong if you tried to manually copy the files of a PHP library that itself depends on three other libraries, each of which has its own version requirements, into a mid-sized project with 15 other manually-copied libraries already in place.

## Solution

Manually copying a library that depends on three other libraries means you would first need to identify all three of those dependencies yourself, find compatible versions of each (checking that they don't conflict with versions already used by any of your other 15 libraries), download each one, and add correct `require` statements for every file in the correct load order. If any two of your 18 total libraries (15 existing plus 3 new) happen to require different, incompatible versions of some shared underlying dependency, you would have to manually resolve that conflict, potentially by patching code, since there's no tool tracking these relationships for you. Any future update to any one library risks silently breaking another. Composer replaces this entire manual process: it reads each package's declared dependencies and version constraints, computes a working combination automatically (or reports a clear conflict if none exists), downloads everything correctly, and sets up autoloading — turning a task that could take hours or days of careful manual work into a single command that completes in seconds.

## Mini Quiz

```yaml
- question: "What core problem does Composer solve for PHP projects?"
  options:
    - "It makes PHP code run faster at execution time"
    - "It automates downloading, version-resolving, and autoloading of external PHP packages your project depends on"
    - "It replaces the need to write your own PHP code entirely"
    - "It is a database management tool for PHP applications"
  answer: 1
  explanation: "Composer is a dependency manager: it automates finding, downloading, resolving version conflicts for, and autoloading third-party PHP packages, replacing manual downloading and require statements."

- question: "Where does Composer look up publicly available PHP packages by default?"
  options:
    - "GitHub Marketplace"
    - "The PHP core distribution"
    - "Packagist (packagist.org)"
    - "npm"
  answer: 2
  explanation: "Packagist is the default public repository Composer uses to find and download PHP packages, including the many packages that make up the Symfony framework."

- question: "What does the vendor/ directory, created by Composer, contain?"
  options:
    - "Your own application's source code"
    - "The downloaded third-party packages and Composer's generated autoloading files"
    - "A backup of your composer.json file"
    - "Compiled PHP bytecode for faster execution"
  answer: 1
  explanation: "Composer downloads all installed packages into the vendor/ directory and generates an autoload file there, so classes from those packages can be used without manual require statements."
```
