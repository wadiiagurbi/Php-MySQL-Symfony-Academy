---
slug: installing-packages
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "Installing Packages with Composer"
difficulty: beginner
order: 3
estimatedMinutes: 9
tags: [php, composer, packages]
---

## Learning Objective

By the end of this lesson, you will be able to add a new package to a PHP project with `composer require`, explain what changes in `composer.json` and `composer.lock`, and use an installed package in your code.

## Explanation

**Installing** a package means downloading someone else's reusable PHP code into your project and registering it as a dependency. The command for this is `composer require vendor/package`. The word **require** here means "my project now requires this package in order to work."

When you run this command, Composer performs several steps. It looks the package up on **Packagist**, the public catalog of PHP packages, and picks the newest version matching your constraints. It then downloads that package plus anything it depends on into the `vendor/` folder. It updates `composer.json` to record the new dependency and updates (or creates) `composer.lock`, a file that records the exact version installed of every package, including indirect ones. Finally, it regenerates `vendor/autoload.php` so the new classes are available immediately.

After installation, you include that single autoload file once in your PHP script, and every installed class becomes usable with no further `require` statements.

## Why It Matters

Almost no professional PHP project is written entirely from scratch. Logging, email validation, HTTP requests, and date handling are all solved problems with well-tested packages. Knowing how to install a package correctly means you can build on reliable code instead of reinventing it, and the lock file guarantees every server and teammate runs identical versions.

## Example

A contact-form script needs to validate email addresses and log submissions. Instead of writing that logic by hand, the developer installs a validation library and a logger with two Composer commands.

## Code

```bash
# Install a logger and an email validator as project dependencies:
composer require monolog/monolog
composer require egulias/email-validator

# Show what was installed:
composer show monolog/monolog

# Every teammate or server reproduces the exact setup with:
composer install
```

## Code Explanation

- `composer require monolog/monolog` downloads the Monolog logging library, records it in `composer.json`, pins the exact version in `composer.lock`, and refreshes the autoloader.
- `composer require egulias/email-validator` repeats the process for a second package that checks whether email addresses are well formed.
- `composer show monolog/monolog` displays the installed version and description of one package, which helps confirm the installation worked.
- `composer install` reads `composer.lock` and installs exactly the recorded versions, which is the command teammates and servers use instead of `require`.

## Common Mistakes

- **Running `composer require` on a production server to add packages.** New packages should be added in development, tested, committed, and then deployed with `composer install`.
- **Deleting the `vendor/` folder and expecting the code to work.** The folder holds all installed packages; it can be rebuilt with `composer install`, but the application cannot run without it.
- **Forgetting `require 'vendor/autoload.php';` in the script.** Without that single include, none of the installed classes can be found by PHP.

## Best Practices

- Use `composer require` for each new dependency and let Composer edit `composer.json` for you to avoid JSON syntax errors.
- Commit both `composer.json` and `composer.lock` so installs are reproducible everywhere.
- Check a package on Packagist (maintenance activity, install count) before adding it to a real project.

## Exercise

Your project needs an HTTP client to call an external API. Which Composer command adds the popular `guzzlehttp/guzzle` package, and which command would a teammate run afterward to get the identical setup?

## Solution

```bash
# You run this once to add the dependency:
composer require guzzlehttp/guzzle

# Your teammate runs this to reproduce your exact setup:
composer install
```

The first command records the new dependency and creates precise version pins; the second command reuses those pins so both machines run the same code.

## Mini Quiz

```yaml
- question: "What does the command composer require monolog/monolog do?"
  options:
    - "It deletes the monolog package from the project"
    - "It downloads monolog, records it in composer.json, pins versions in composer.lock, and refreshes the autoloader"
    - "It upgrades PHP itself to a newer release"
    - "It sends log messages to the browser"
  answer: 1
  explanation: "composer require looks the package up on Packagist, installs it with its dependencies, and updates composer.json, composer.lock, and the autoloader."
- question: "What is the difference between composer require and composer install?"
  options:
    - "They do exactly the same thing"
    - "require adds a new dependency, while install reproduces the exact versions recorded in composer.lock"
    - "install adds new packages and require removes them"
    - "require only works without an internet connection"
  answer: 1
  explanation: "Use require when adding something new; use install when setting up an existing project from its lock file."
- question: "Why must a PHP script include vendor/autoload.php after installing packages?"
  options:
    - "It is optional and never needed"
    - "It registers automatic class loading so installed package classes can be used without manual require statements"
    - "It deletes old package versions"
    - "It connects PHP to the MySQL server"
  answer: 1
  explanation: "Including vendor/autoload.php once makes every installed class loadable on demand, which is the point of Composer-managed dependencies."
```
