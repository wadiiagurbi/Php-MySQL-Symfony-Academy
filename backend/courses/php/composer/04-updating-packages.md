---
slug: updating-packages
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "Updating Packages with Composer"
difficulty: beginner
order: 4
estimatedMinutes: 9
tags: [php, composer, updates]
---

## Learning Objective

By the end of this lesson, you will be able to update project dependencies safely with `composer update`, explain how the lock file changes, and check for outdated packages.

## Explanation

Over time, the packages your project uses release new versions with bug fixes and security patches. **Updating** means asking Composer to move to newer allowed versions within the constraints in `composer.json`. A **constraint** is the version rule you declared, such as `^2.0`, which permits any `2.x` release but not `3.0`.

The command `composer update` re-reads `composer.json`, fetches the newest matching releases, and rewrites `composer.lock` with the new exact versions. You can update everything at once or limit the update to one package with `composer update vendor/package`, which leaves everything else untouched and is the safer habit.

Two helper commands support this workflow. `composer outdated` lists installed packages that have newer versions available, and `composer validate` checks that your `composer.json` and `composer.lock` files are consistent and correctly formatted.

## Why It Matters

Outdated dependencies are one of the most common sources of security problems in PHP applications. Updating in a controlled way — one package at a time, with tests run afterward — lets you receive fixes promptly while keeping the ability to roll back through the lock file if a new release breaks something.

## Example

A blog built six months ago uses Monolog 2.3, but version 2.9 fixes a bug the developer keeps hitting. The developer checks what is outdated, updates only Monolog, and runs the test suite afterward.

## Code

```bash
# See which packages have newer versions available:
composer outdated

# Update a single package (safer than updating everything):
composer update monolog/monolog

# Check that composer.json and composer.lock are still consistent:
composer validate
```

## Code Explanation

- `composer outdated` compares installed versions against Packagist and reports which packages have newer releases, without changing anything.
- `composer update monolog/monolog` allows only that one package (and, if required, its own dependencies) to move to a newer version within the `^2.0` constraint, then rewrites `composer.lock`.
- `composer validate` confirms both JSON files parse correctly and agree with each other, catching mistakes before they reach teammates or servers.

## Common Mistakes

- **Running a full `composer update` right before a release.** Updating every package at once increases the chance that something changes behavior unexpectedly at the worst moment.
- **Editing `composer.lock` manually to force a version.** The lock file is generated; version choices belong in `composer.json` constraints.
- **Updating without running tests afterward.** Even a small patch release can alter behavior, so the test suite should confirm nothing broke.

## Best Practices

- Prefer updating one package at a time so failures are easy to attribute and revert.
- Review a package's changelog before updating a major dependency in a real project.
- Commit the updated `composer.lock` immediately so the whole team shares the new versions.

## Exercise

You want to update only the `symfony/console` package and then confirm your Composer files are still valid. Which two commands do you run, in order?

## Solution

```bash
composer update symfony/console
composer validate
```

The first command moves only that package to the newest allowed release and refreshes the lock file; the second confirms both files remain consistent.

## Mini Quiz

```yaml
- question: "What does composer update monolog/monolog do?"
  options:
    - "It removes monolog from the project"
    - "It moves monolog to the newest version allowed by composer.json and rewrites composer.lock"
    - "It installs PHP itself"
    - "It lists files inside the vendor folder"
  answer: 1
  explanation: "A targeted update re-resolves only the named package within its version constraint and records the new exact version in the lock file."
- question: "Why is updating a single package usually safer than updating everything at once?"
  options:
    - "It is not safer in any way"
    - "Fewer changes make it clear which package caused a problem if something breaks"
    - "Single updates run without an internet connection"
    - "Single updates never change the lock file"
  answer: 1
  explanation: "Limiting the change to one package isolates risk, so any failure points directly at that package."
- question: "What are composer outdated and composer validate used for?"
  options:
    - "They delete the vendor directory"
    - "outdated reports packages with newer releases available, and validate checks the Composer files for errors"
    - "They deploy the application to production"
    - "They create new PHP classes"
  answer: 1
  explanation: "outdated is a read-only report of available upgrades, while validate confirms composer.json and composer.lock are well formed and consistent."
```
