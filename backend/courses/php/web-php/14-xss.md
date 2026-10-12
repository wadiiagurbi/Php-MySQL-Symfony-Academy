---
slug: xss-prevention
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Cross-Site Scripting (XSS) Prevention"
difficulty: beginner
order: 14
estimatedMinutes: 10
tags: [php, security, xss, html]
---

## Learning Objective

By the end of this lesson, you will be able to explain what cross-site scripting (XSS) is and neutralize it by escaping output with `htmlspecialchars()`.

## Explanation

**Cross-site scripting**, abbreviated **XSS**, is an attack in which a visitor stores or sends JavaScript that later runs in another visitor's browser. Suppose a comment form saves raw input and the page later echoes it with `echo $comment;`. If someone submits `<script>alert('hacked')</script>` as their "comment", every future reader's browser executes that script. Real attacks steal sessions, rewrite pages, or send victims to malicious sites.

The core defense is **output escaping**: converting characters with special meaning in HTML (`<`, `>`, `&`, quotes) into harmless display entities (`&lt;`, `&gt;`, and so on) at the moment you print data. PHP's function for this is `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. The `ENT_QUOTES` flag means both single and double quotes are converted, and `'UTF-8'` declares the character encoding so conversion is correct.

Escaping on output (rather than when saving) keeps the stored data clean and lets each output context decide its own protection.

## Why It Matters

Any page that displays user-provided content — comments, usernames, product reviews, search terms echoed back — is an XSS opportunity if it prints raw data. One unescaped `echo` can compromise every visitor who views the page, which is why escaping output is a non-negotiable habit for all PHP web work.

## Example

A guestbook page lists visitor comments stored earlier. One comment contains a hostile script tag. Escaping each comment on display renders the attack as visible text instead of running it.

## Code

```php
<?php

$comments = [
    'I love this shop!',
    '<script>alert("hacked")</script>',
];

foreach ($comments as $comment) {
    echo '<p>' . htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') . '</p>';
}
```

## Code Explanation

- `$comments` holds two entries: a normal message and a hostile string containing a script tag.
- `htmlspecialchars($comment, ENT_QUOTES, 'UTF-8')` converts `<`, `>`, and both quote types into display entities, so the script tag becomes inert visible text.
- Wrapping the result in `<p>...</p>` keeps legitimate page markup (the paragraph tags we wrote) intact while neutralizing markup inside the data.
- The `foreach` loop applies the same protection to every comment, so no entry can slip through unescaped.

## Common Mistakes

- **Escaping input when saving instead of output when printing.** Stored escaping corrupts data and fails when the same value is used in a non-HTML context like JSON.
- **Escaping only some echoes.** Attackers need just one unescaped spot, so every print of untrusted data must be escaped.
- **Using `strip_tags()` as the whole defense.** It removes known tags but misses event-handler tricks; escaping is the reliable default.

## Best Practices

- Escape every piece of untrusted data at the moment you output it into HTML.
- Always pass `ENT_QUOTES` and `'UTF-8'` to `htmlspecialchars()` explicitly rather than relying on defaults.
- For URLs or JavaScript contexts, use the escaping function designed for that context instead of reusing HTML escaping.

## Exercise

A search page echoes the visitor's query back with `echo $_GET['q'];`. Rewrite that single line so a query like `<img src=x onerror=alert(1)>` displays as harmless text.

## Solution

```php
<?php

echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8');
```

The null coalescing operator handles a missing parameter, and `htmlspecialchars()` neutralizes every HTML-significant character in the query.

## Mini Quiz

```yaml
- question: "What is cross-site scripting (XSS)?"
  options:
    - "A database backup technique"
    - "An attack where injected scripts run in other visitors browsers"
    - "A way to style pages with CSS"
    - "A Composer command for installing packages"
  answer: 1
  explanation: "XSS plants hostile markup or scripts in content your application later displays, so victims browsers execute it."
- question: "What does htmlspecialchars() do?"
  options:
    - "It deletes rows from the database"
    - "It converts HTML-significant characters into harmless display entities"
    - "It hashes passwords for storage"
    - "It sends email notifications"
  answer: 1
  explanation: "htmlspecialchars turns characters like < and > into entities such as &lt;, so data displays as text instead of markup."
- question: "When should you escape untrusted data for HTML output?"
  options:
    - "At the moment you output it, on every echo of untrusted data"
    - "Only once a year during maintenance"
    - "Never, escaping breaks all pages"
    - "Only for administrator accounts"
  answer: 0
  explanation: "Output-time escaping on every untrusted echo guarantees no display path is missed and keeps stored data clean."
```
