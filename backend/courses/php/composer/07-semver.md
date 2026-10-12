---
slug: semver
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: composer
chapterTitle: "Chapter 6 — Composer & Dependencies"
chapterOrder: 6
title: "Semantic Versioning (SemVer)"
difficulty: beginner
order: 7
estimatedMinutes: 9
tags: [php, composer, versioning]
---

## Learning Objective

By the end of this lesson, you will be able to read a version number like `2.4.1`, explain what major, minor, and patch mean, and write safe Composer version constraints.

## Explanation

**Semantic Versioning**, usually shortened to **SemVer**, is an agreement about what the three numbers in a version like `2.4.1` promise. The format is always `MAJOR.MINOR.PATCH`, and each position has a defined meaning:

- **MAJOR** (the `2` in `2.4.1`) increases when incompatible, breaking changes are made. Code written for version `1.x` may need changes to work with `2.0`.
- **MINOR** (the `4`) increases when new features are added in a way that stays compatible with existing code.
- **PATCH** (the `1`) increases for compatible bug fixes that change no behavior except correcting the bug.

Composer constraints build on this promise. `"monolog/monolog": "^2.4"` uses the **caret** (`^`), which means "allow any `2.x` release from `2.4` upward, but not `3.0`." The **tilde** (`~2.4.0`) is stricter, allowing only `2.4.x` patches. Writing `>=2.4 <3.0` states the same range in long form.

## Why It Matters

Version numbers are how you judge the risk of an update. A patch release is usually safe to accept immediately, while a major release deserves reading the changelog and running your tests. Correct constraints let Composer install fixes automatically while shielding you from breaking changes you have not reviewed yet.

## Example

A project requires `monolog/monolog` at `^2.4`. Version `2.9.1` appears with a bug fix, and version `3.0.0` appears with a rewritten API. Composer accepts the first automatically but refuses the second until the developer explicitly allows it.

## Code

```json
{
    "require": {
        "php": ">=8.1",
        "monolog/monolog": "^2.4",
        "symfony/console": "~6.2.0"
    }
}
```

## Code Explanation

- `"monolog/monolog": "^2.4"` permits `2.4.0` up to (but not including) `3.0.0`, so minor features and patches install freely while breaking major changes stay out.
- `"symfony/console": "~6.2.0"` is narrower: it permits `6.2.1`, `6.2.2`, and other patches, but not `6.3.0`, which is useful when even minor additions feel risky.
- `"php": ">=8.1"` requires interpreter version 8.1 or newer with no upper bound, since newer PHP releases stay compatible with most code.
- Together, these three lines let `composer update` safely collect fixes while blocking upgrades that could demand code changes.

## Common Mistakes

- **Using `*` or `dev-main` for everything.** An unbounded constraint invites breaking changes at the worst possible time.
- **Treating a minor bump as always risk-free.** Most minors are compatible, but reading the changelog before updating important packages is still wise.
- **Pinning exact versions like `2.4.1` everywhere.** Exact pins block even safe security patches; caret ranges are usually the better default.

## Best Practices

- Prefer caret constraints (`^2.4`) for libraries that follow SemVer, since they balance safety with freshness.
- Reserve exact pins for rare cases where even a patch release must be reviewed first.
- Read the changelog before crossing a major version boundary such as `2.x` to `3.0`.

## Exercise

Your project lists `"acme/logger": "^1.3"`. Which of these versions would Composer accept: `1.3.0`, `1.9.2`, `2.0.0`? Explain your answer in one sentence per version.

## Solution

`1.3.0` is accepted because it is the minimum allowed release. `1.9.2` is accepted because caret `^1.3` permits any compatible `1.x` release. `2.0.0` is refused because a new major version may contain breaking changes and needs explicit approval.

## Mini Quiz

```yaml
- question: "In the version 2.4.1, what do the three numbers mean under SemVer?"
  options:
    - "Year, month, and day of release"
    - "Major (breaking changes), minor (compatible features), patch (compatible fixes)"
    - "Number of files, classes, and methods"
    - "They are random and carry no meaning"
  answer: 1
  explanation: "SemVer defines MAJOR for incompatible changes, MINOR for compatible features, and PATCH for compatible bug fixes."
- question: "What does the constraint ^2.4 allow?"
  options:
    - "Only exactly version 2.4.0"
    - "Any 2.x version from 2.4 upward, but not 3.0.0"
    - "Any version including 3.0 and 4.0"
    - "Only versions below 2.4"
  answer: 1
  explanation: "The caret keeps the major version fixed at 2 while allowing newer minors and patches, blocking the potentially breaking 3.0."
- question: "Why is pinning every dependency to an exact version usually a poor default?"
  options:
    - "Exact pins make installs slower"
    - "Exact pins block even safe security and bug-fix patches from installing"
    - "Exact pins are invalid syntax in composer.json"
    - "Exact pins delete the vendor folder"
  answer: 1
  explanation: "Caret ranges accept compatible fixes automatically, while exact pins freeze everything until edited by hand."
```
