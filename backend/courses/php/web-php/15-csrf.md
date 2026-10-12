---
slug: csrf-protection
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Cross-Site Request Forgery (CSRF) Protection"
difficulty: intermediate
order: 15
estimatedMinutes: 11
tags: [php, security, csrf, forms]
---

## Learning Objective

By the end of this lesson, you will be able to explain how CSRF attacks trick logged-in users and protect your own forms with per-session tokens.

## Explanation

**Cross-site request forgery**, abbreviated **CSRF**, is an attack that rides on a victim's existing login. Browsers automatically attach **cookies** (small pieces of stored login state) to every request for a site, even requests triggered by a different, malicious site. If you are logged into `shop.example.com` and visit an attacker's page containing `<img src="https://shop.example.com/delete-account">`, your browser obediently sends the delete request with your cookies, and the shop cannot tell you never meant it.

The standard defense is a **CSRF token**: a long random string stored in the user's **session** (server-side per-visitor storage) and embedded as a hidden field in every state-changing form. When the form is submitted, the server compares the submitted token with the session copy using `hash_equals()`, a comparison function designed to be safe against timing attacks. An attacker's site cannot read the token (it never appears in URLs or cookies), so forged requests fail the check.

## Why It Matters

Without CSRF protection, any action behind a login — changing an email address, placing an order, deleting content — can be triggered by luring the victim to a hostile page. Tokens close that gap with little code, which is why Symfony forms include them automatically and plain-PHP forms must add them by hand.

## Example

A "change email" form at an online shop is protected: the form embeds the session token in a hidden input, and the processing script rejects any submission whose token does not match.

## Code

```php
<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
    echo 'Email changed for user ' . (int) $_SESSION['user_id'];
    exit;
}
```

## Code Explanation

- `session_start()` enables the per-visitor session store where the server-side token copy lives.
- `bin2hex(random_bytes(32))` generates a 64-character unpredictable token once per session; `random_bytes()` is PHP's secure random generator.
- On `POST`, the script reads the submitted `csrf_token` and compares it with the session copy using `hash_equals()`, which compares strings safely.
- A mismatch answers `403 Forbidden` and stops immediately, so no state change runs; a match proceeds to the real action.

## Common Mistakes

- **Protecting only "important" forms.** Every POST that changes state needs a token; attackers happily abuse the overlooked ones.
- **Putting the token in the URL.** Tokens in query strings leak through browser history, logs, and referrer headers; hidden form fields plus sessions keep them private.
- **Regenerating the token on every page load.** Over-eager rotation invalidates legitimate open tabs; one token per session is the usual balance.

## Best Practices

- Require POST (never GET) for state-changing actions, and check the CSRF token on each one.
- Generate tokens with `random_bytes()` and compare with `hash_equals()`, never with `==`.
- Invalidate or rotate the session on login and logout so tokens cannot be fixed by an attacker beforehand.

## Exercise

A delete-comment form posts to `delete.php` with a `comment_id` field. Add the hidden CSRF input the form needs and the three-line check `delete.php` must run before deleting.

## Solution

The form needs `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">` after starting the session and creating the token. The handler checks `hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')` and exits with `403` on mismatch before touching any data.

## Mini Quiz

```yaml
- question: "What makes a CSRF attack possible?"
  options:
    - "Browsers automatically send login cookies even with requests triggered by other sites"
    - "PHP code runs too slowly"
    - "Databases reject all POST requests"
    - "Composer installs too many packages"
  answer: 0
  explanation: "Because the browser attaches the victim's cookies to any request for the target site, the server cannot distinguish a forged request by cookies alone."
- question: "How does a CSRF token stop the attack?"
  options:
    - "It makes pages load with different colors"
    - "The server requires a secret session-bound value that attacker sites cannot read or guess"
    - "It deletes all user sessions hourly"
    - "It blocks every POST request entirely"
  answer: 1
  explanation: "Only forms genuinely rendered by your application contain the correct token, so cross-site forgeries fail the comparison."
- question: "Which functions generate and compare CSRF tokens safely?"
  options:
    - "rand() and =="
    - "random_bytes() for generation and hash_equals() for comparison"
    - "echo and print"
    - "json_encode and json_decode"
  answer: 1
  explanation: "random_bytes produces unpredictable tokens and hash_equals compares them without leaking information through timing."
```
