---
slug: sessions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Sessions"
difficulty: beginner
order: 5
estimatedMinutes: 12
tags: [php, sessions, state, web]
---

## Learning Objective

By the end of this lesson, you will understand why HTTP is "stateless," what a PHP session is and why it solves that problem, and how to use `session_start()` and the `$_SESSION` superglobal array to remember information about a visitor across multiple requests.

## Explanation

You already know that every visit to a web page is a separate request-response cycle: the browser sends a request, the server sends back a response, and that particular exchange is then finished. Here is the important detail this lesson adds: by default, the server has **no memory** of any previous request. Each request is handled completely independently, as if it were the very first one it had ever seen from anyone. This property is called being **stateless** — there is no built-in "state," or memory, carried from one request to the next.

This creates an obvious problem. Imagine a visitor logs into a website by submitting a form with their username and password. That login request is processed, and a response is sent back saying "welcome." But then the visitor clicks a link to view their account page — that click sends a brand new, completely separate request. Without some way to carry information forward, the server has no way of knowing this new request came from the same visitor who just logged in. It would have to ask them to log in again on every single page.

A **session** is PHP's solution to this problem. A session is a way to store data about one specific visitor that persists across multiple separate requests, for as long as that visitor keeps browsing the site. You start or resume a session in a PHP script by calling `session_start()`, which must happen before any output is sent to the browser. Once a session is started, you can store data in it using another superglobal array, `$_SESSION`, exactly like you would use any associative array: `$_SESSION['username'] = 'alice';`.

Here is the part that makes it work across requests: the very first time a session is started for a visitor, PHP generates a unique **session ID**, a long random string that identifies that visitor's session. PHP sends that ID back to the browser inside a small piece of data called a **cookie** (a cookie is a small piece of information a server asks the browser to store and automatically send back on every future request to the same site — you will look at cookies in more depth in the next lesson). On every following request, the browser automatically sends that same session ID back, and calling `session_start()` tells PHP to look up the matching session data on the server and load it back into `$_SESSION`, exactly as it was left.

## Why It Matters

Sessions are what make "logged in" experiences possible at all. Once a visitor logs in successfully, a real application stores something like `$_SESSION['user_id'] = 42;`. On every subsequent page, the script checks whether `$_SESSION['user_id']` is set; if it is, the visitor is treated as logged in, without having to re-enter their password on every click. Shopping carts on e-commerce sites work the same way: items you add are stored in the session, and they are still there when you move to the checkout page, because the session data survives from one request to the next. Without sessions, every single page load would be a fresh start with no memory of anything — no logins, no shopping carts, no personalized settings — which would make the modern interactive web essentially impossible.

## Example

Consider a very small login system: one page lets a visitor "log in" by setting a session value, and a second page checks that value to decide whether to show a welcome message or a "please log in" message.

## Code

```php
<?php
// This single file demonstrates the idea using two clearly separated
// stages. In a real application, "Stage 1" would be login.php, handling
// one request, and "Stage 2" would be account.php, handling a later,
// completely separate request from the same visitor.

session_start();

// --- Stage 1: this is what login.php would do when the visitor logs in ---
$_SESSION['username'] = 'alice';
$_SESSION['logged_in_at'] = date('Y-m-d H:i:s');
echo "You are now logged in as alice.\n";

// --- Stage 2: this is what account.php would do on a later request ---
if (isset($_SESSION['username'])) {
    $name = htmlspecialchars($_SESSION['username']);
    $time = htmlspecialchars($_SESSION['logged_in_at']);
    echo "Welcome back, {$name}! You logged in at {$time}.";
} else {
    echo "You are not logged in. Please log in first.";
}
```

## Code Explanation

- `session_start()` appears as the very first thing in the script (before any `echo`). It either creates a brand new session, if this visitor does not have one yet, or resumes their existing session, using the session ID sent back automatically in their browser's cookie.
- In "Stage 1," `$_SESSION['username'] = 'alice';` stores a value in the session data. This value is saved on the server, associated with this visitor's unique session ID, not sent back and forth on every request the way `$_GET` or form values are.
- In "Stage 2," which represents a completely separate request that might happen minutes later, `isset($_SESSION['username'])` finds that the value set in "Stage 1" is still there, because both requests would carry the same session ID.
- If a different visitor, with no session data, reached "Stage 2" on its own, `isset($_SESSION['username'])` would be `false`, and they would see the "not logged in" message instead — each visitor's session data is kept separate.

## Common Mistakes

- **Forgetting to call `session_start()` before using `$_SESSION`, or calling it after some output has already been sent.** `session_start()` needs to send a cookie header to the browser, and headers must be sent before any actual page content; calling it late, or omitting it, causes an error or means `$_SESSION` never actually works.
- **Assuming session data is shared between all visitors.** Session data is specific to one visitor's session ID. Two different visitors browsing at the same time have two completely separate `$_SESSION` arrays, even if your code looks the same for both of them.
- **Storing large amounts of data in a session unnecessarily.** Sessions are meant for small, per-visitor pieces of information like a user ID or a few cart items, not for large datasets that belong in a database.
- **Never clearing session data on logout.** Leaving old session values in place after a visitor logs out can let stale or incorrect data leak into a page that should be treating them as logged out.

## Best Practices

- Always call `session_start()` as the very first line of PHP in any script (or page) that needs to read or write `$_SESSION`.
- Store only small, essential pieces of information in the session, such as a user ID or a few preferences — anything to check "who is this visitor" or "what have they chosen so far."
- Use `isset($_SESSION['some_key'])` to check whether a piece of session data exists before relying on it, exactly as you learned to do with `$_GET` and `$_POST`.
- When a visitor logs out, remove their session data explicitly (for example with `session_destroy()`) rather than assuming it will simply disappear.

## Exercise

Write two PHP scripts. The first, `visit-counter.php`, should start a session, and each time it runs, increase a `$_SESSION['visits']` counter by one (starting at 1 if it does not exist yet), then display `"You have visited this page X times."` The second script is not needed — just make sure `visit-counter.php` correctly increases the count every time it is reloaded, because the browser will keep sending the same session ID.

## Solution

```php
<?php
// visit-counter.php
session_start();

if (!isset($_SESSION['visits'])) {
    $_SESSION['visits'] = 0;
}

$_SESSION['visits']++;

echo "You have visited this page " . $_SESSION['visits'] . " times.";
```

This works because `session_start()` reconnects this request to the same session used on the visitor's previous visit, thanks to the session ID stored in their browser's cookie. The `isset()` check makes sure the counter starts at `0` the very first time (when no `visits` key exists yet), and `$_SESSION['visits']++` increases it by one on every request. Because `$_SESSION` persists on the server between requests, reloading the page keeps increasing the same number instead of resetting to `1` every time, which is exactly what would happen if this counter were stored in an ordinary variable instead of the session.

## Mini Quiz

```yaml
- question: "What does it mean that HTTP is 'stateless'?"
  options:
    - "HTTP requests cannot contain any data"
    - "Each request/response cycle happens with no memory of previous requests"
    - "HTTP only works within a single country or state"
    - "The server always crashes after one request"
  answer: 1
  explanation: "Stateless means each request is handled independently, with no automatic memory of earlier requests from the same visitor."
- question: "What must you call before reading or writing $_SESSION in a PHP script?"
  options:
    - "session_start()"
    - "session_open()"
    - "$_SESSION_init()"
    - "Nothing, $_SESSION always works automatically"
  answer: 0
  explanation: "session_start() must be called, before any output, to create or resume the visitor's session so $_SESSION can be used."
- question: "How does PHP know that two separate requests come from the same visitor's session?"
  options:
    - "It checks the visitor's IP address only"
    - "It remembers the order requests were received in"
    - "A unique session ID is sent to the browser and sent back automatically, usually via a cookie"
    - "PHP cannot actually tell requests apart"
  answer: 2
  explanation: "PHP generates a session ID and sends it to the browser as a cookie; the browser automatically returns that same ID on later requests, letting PHP reload the correct session data."
```
