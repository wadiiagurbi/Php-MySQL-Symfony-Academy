---
slug: cookies
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Cookies"
difficulty: beginner
order: 6
estimatedMinutes: 10
tags: [cookies, http, state]
---

## Learning Objective

By the end of this lesson, you will understand what a cookie is, how to create one with `setcookie()`, how to read cookies with the `$_COOKIE` superglobal, and how cookies differ from the sessions you learned about in the previous lesson.

## Explanation

A **cookie** is a small piece of text data that a server asks a visitor's browser to store. Once the browser has stored a cookie for a website, it automatically sends that cookie back to the server on every later request to that same website, until the cookie expires or is deleted.

Think of it like a sticker the server puts on the visitor's browser the first time they visit. Every time that browser comes back, it shows the sticker again, and the server can read what is written on it. This is how a website can "recognize" the same browser across multiple separate visits, even days or weeks apart — something that is otherwise impossible, because, as you learned in the sessions lesson, HTTP is stateless and normally treats every request as unrelated to the last.

In PHP, you create a cookie with the built-in `setcookie()` function. It takes the cookie's name, its value, and (optionally) when it should expire, among other options. Once a cookie has been set and the browser sends it back on a following request, PHP makes it available to your script through the `$_COOKIE` superglobal array, keyed by the cookie's name — very similar to how `$_GET`, `$_POST`, and `$_SESSION` work.

An important detail: `setcookie()` must be called before any actual HTML or text output is sent from your script, because it works by adding information to the HTTP response headers, and headers must always be sent before the body of the response (you will look at this rule more closely in the headers lesson).

## Why It Matters

Cookies are the foundation of many everyday web features. A "remember me" checkbox on a login form, a shopping cart that survives even if the visitor closes the browser and comes back tomorrow, a website remembering your preferred language or dark/light theme, or an analytics tool recognizing a returning visitor — all of these commonly rely on cookies. Sessions alone are not enough for this, because a PHP session is normally deleted once the browser is closed; a cookie can be told to survive for days, months, or years.

## Example

A simple website wants to remember a visitor's favorite color across visits, without requiring them to log in. When the visitor picks a color, the server stores it in a cookie. On every later visit, the server reads the cookie and greets the visitor using their remembered color preference.

## Code

```php
<?php
// Setting a cookie that remembers the visitor's favorite color for 30 days
$favoriteColor = 'blue';
$thirtyDaysInSeconds = 30 * 24 * 60 * 60;

setcookie(
    'favorite_color',      // cookie name
    $favoriteColor,        // cookie value
    time() + $thirtyDaysInSeconds, // expiration timestamp
    '/'                    // path: available across the whole site
);

// Reading a cookie on a later request
if (isset($_COOKIE['favorite_color'])) {
    $color = htmlspecialchars($_COOKIE['favorite_color']);
    echo "Welcome back! Your favorite color is $color.";
} else {
    echo "Hello, new visitor! We don't know your favorite color yet.";
}
```

## Code Explanation

- `time() + $thirtyDaysInSeconds` calculates a Unix timestamp 30 days in the future. `setcookie()` uses this to tell the browser when the cookie should be deleted automatically. If you leave out the expiration (or set it to `0`), the cookie becomes a "session cookie" that disappears when the browser itself is closed — not to be confused with a PHP session.
- The `'/'` path argument tells the browser the cookie should be sent for every page on the site, not just the exact folder where it was set.
- On a later request, `$_COOKIE['favorite_color']` contains whatever value the browser sent back, exactly as PHP received it from the request headers.
- `htmlspecialchars()` is used before echoing the cookie value, because a cookie's value is user-supplied data — a visitor (or an attacker) could manipulate their own cookies, so, just like with `$_GET` and `$_POST`, you should never trust it blindly.

## Common Mistakes

- **Calling `setcookie()` after output has already started.** If your script has already echoed something, or even has a blank line or space before the opening `<?php` tag, PHP will throw a "headers already sent" warning and the cookie will not be set.
- **Forgetting that cookie values are strings and are visible/editable by the visitor.** Never store sensitive information (passwords, raw user IDs used for authorization) directly in a cookie's value.
- **Confusing cookie expiration with session lifetime.** Setting a cookie's expiration does not extend how long a PHP session lasts; sessions and cookies are configured separately.
- **Not checking `isset()` before reading `$_COOKIE`.** If the cookie was never set or has expired, accessing the key directly will trigger a warning in modern PHP.

## Best Practices

- Always sanitize or validate cookie values before using them, exactly as you would with `$_GET` or `$_POST`.
- Set the shortest expiration that still meets your feature's need — long-lived cookies that are not necessary increase privacy and security exposure.
- Use the `secure` and `httponly` options (available as additional arguments to `setcookie()`) in real projects so cookies are only sent over HTTPS and cannot be read by JavaScript, reducing the risk of theft.
- Prefer sessions (server-side storage) for sensitive data, and use cookies mainly for small, non-sensitive preferences or identifiers.

## Exercise

Write a PHP script that remembers whether a visitor has already seen a "welcome" banner. The first time someone visits, show the message `"Welcome! This is your first visit."` and set a cookie named `visited` with the value `'yes'`, expiring in 7 days. On any later visit (when the cookie is already set), show `"Welcome back!"` instead.

## Solution

```php
<?php
$sevenDaysInSeconds = 7 * 24 * 60 * 60;

if (isset($_COOKIE['visited'])) {
    echo "Welcome back!";
} else {
    echo "Welcome! This is your first visit.";
    setcookie('visited', 'yes', time() + $sevenDaysInSeconds, '/');
}
```

This works because the very first time the script runs, `$_COOKIE['visited']` does not exist yet, so the "else" branch runs, prints the first-visit message, and sets the cookie. The browser stores that cookie and sends it back automatically on every request for the next 7 days, so the `isset()` check succeeds on all following visits and the "Welcome back!" message is shown instead.

## Mini Quiz

```yaml
- question: "Where is a cookie actually stored?"
  options:
    - "On the server, in a database"
    - "In the visitor's browser"
    - "Inside the PHP session file"
    - "Nowhere, it only exists during one request"
  answer: 1
  explanation: "A cookie is stored by the browser and automatically sent back to the server with future requests to the same site."
- question: "Which PHP superglobal lets you read a cookie's value?"
  options:
    - "$_GET"
    - "$_POST"
    - "$_COOKIE"
    - "$_SESSION"
  answer: 2
  explanation: "$_COOKIE holds the cookies that the browser sent along with the current request, keyed by cookie name."
- question: "Why must setcookie() be called before any HTML output?"
  options:
    - "It is just a coding style convention"
    - "Cookies are sent as part of the HTTP response headers, which must be sent before the body"
    - "PHP requires all function calls at the top of the file"
    - "It has no real requirement, it is only a recommendation"
  answer: 1
  explanation: "setcookie() adds a Set-Cookie header to the response, and HTTP headers must always be sent before any body content."
- question: "What is a key difference between a cookie and a PHP session?"
  options:
    - "A cookie is stored server-side, a session is stored in the browser"
    - "A cookie can persist across browser restarts if given a future expiration; a default PHP session ends when the browser closes"
    - "Sessions cannot store any data"
    - "There is no difference, they are the same feature"
  answer: 1
  explanation: "Cookies live in the browser and can be set to persist long-term, while a typical PHP session is tied to the browser being open and stores its data on the server."
```
