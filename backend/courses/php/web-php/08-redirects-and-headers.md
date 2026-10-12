---
slug: redirects-and-headers
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Redirects and HTTP Headers"
difficulty: beginner
order: 8
estimatedMinutes: 10
tags: [headers, redirects, http]
---

## Learning Objective

By the end of this lesson, you will understand what HTTP headers are, how to send them in PHP with the `header()` function, and how to redirect a visitor's browser to another page using `header('Location: ...')`.

## Explanation

Whenever a browser and a server exchange an HTTP request or response (as introduced in the very first lesson of this chapter), they don't only exchange the visible content — the HTML page, the image, the text. They also exchange **headers**: extra pieces of metadata attached to the request or response, sent separately from the main content ("body"). Headers describe things about the message itself, such as what type of content is included, how long it is, whether it should be cached, or, importantly for this lesson, whether the browser should go somewhere else entirely.

In PHP, you can send a custom HTTP header using the built-in `header()` function, passing it a string in the form `"Header-Name: value"`. One of the most common and useful headers is `Location`. When a response includes a `Location` header, it tells the browser: "Don't show this response — go load this other URL instead." This is called a **redirect**.

To perform a redirect in PHP:

```php
header('Location: https://example.com/thank-you.php');
exit;
```

The `exit` (or `die`) call after `header()` is important. Sending the `Location` header tells the browser where to go, but PHP will still keep running the rest of your script unless you stop it. If your script kept running and produced more output after redirecting, that output would be wasted (the browser is leaving anyway) and, worse, could sometimes cause errors.

There is one strict rule with `header()`: it must be called **before any actual output** has been sent — no `echo`, no raw HTML outside of PHP tags, not even a blank line before `<?php` at the very top of the file. This is because headers are always sent first in an HTTP response, before the body; once PHP has started sending body content, it is too late to add or change headers, and calling `header()` at that point will trigger a "headers already sent" warning and be ignored.

## Why It Matters

Redirects are everywhere in real web applications. A common and important pattern: after a visitor submits a form (as covered in the forms lesson) and the data is successfully processed, a well-built application redirects the browser to a new page (like a "thank you" or confirmation page) instead of just printing a result directly. This avoids a common problem: if the visitor hits the browser's refresh button after a successful redirect, it only reloads that harmless confirmation page — not the original form submission, which could otherwise be accidentally submitted a second time. Redirects are also used to send a visitor to a login page when they try to access something that requires being logged in, or to send them back to where they were after logging in successfully.

## Example

A visitor tries to view an "account" page, but they are not logged in (there is no active session, as covered in the sessions lesson). Instead of showing an error, the script redirects them straight to the login page.

## Code

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// If we reach this point, the visitor is logged in.
echo "Welcome to your account page!";
```

## Code Explanation

- `session_start()` must run first so PHP can check whether this visitor already has an active session, as explained in the sessions lesson.
- `isset($_SESSION['user_id'])` checks whether the session already stores a logged-in user's ID. If it does not, the visitor is treated as not logged in.
- `header('Location: /login.php')` sends the `Location` header, instructing the browser to navigate to `/login.php` instead of displaying this page's content.
- `exit;` immediately stops the script from running any further, so the `echo` line below never runs for a visitor who is being redirected.
- Only a visitor who is confirmed logged in reaches the final `echo` line and actually sees the account page's content.

## Common Mistakes

- **Sending any output before calling `header()`.** Even a single space or blank line before `<?php` at the top of a file counts as output and will break the redirect with a "headers already sent" error.
- **Forgetting `exit` after a redirect header.** The rest of the script keeps running, potentially producing visible output or performing actions that should not happen for a visitor who is being redirected away.
- **Using a relative path that resolves unexpectedly.** `header('Location: login.php')` (without a leading slash) is resolved relative to the current URL's path, which can send the visitor to the wrong place if the script is not at the root — using an absolute path or full URL is usually safer.
- **Trying to combine a redirect with visible HTML content in the same response.** A `Location` header is meant to be the entire response; it does not make sense to also try to display a page at the same time.

## Best Practices

- Always place `header()` calls as early as possible in your script, ideally right after any checks that decide whether a redirect is needed, before any HTML or `echo` statements.
- Always call `exit` (or `die`) immediately after a redirect header, to guarantee no further code runs.
- Use full, unambiguous URLs or absolute paths (starting with `/`) for the `Location` value to avoid confusion about where the browser will actually go.
- Use redirects after successful form submissions (redirect to a confirmation page) to prevent duplicate submissions if the visitor refreshes the page.

## Exercise

Write a script that checks if a GET query parameter called `confirmed` is present and equal to `"1"`. If it is, print `"Thank you for confirming!"`. If it is not present (or not equal to `"1"`), redirect the visitor to `/confirm.php`.

## Solution

```php
<?php
if (isset($_GET['confirmed']) && $_GET['confirmed'] === '1') {
    echo "Thank you for confirming!";
} else {
    header('Location: /confirm.php');
    exit;
}
```

This checks the `confirmed` query parameter using `isset()` first (to avoid a warning if it's missing) combined with a strict comparison to `'1'`. If the condition is true, the confirmation message is printed directly. Otherwise, the `header()` call redirects the browser to `/confirm.php`, and `exit` stops the script immediately so no further code can run after the redirect is sent.

## Mini Quiz

```yaml
- question: "What is an HTTP header?"
  options:
    - "The visible HTML content of a page"
    - "Extra metadata sent along with a request or response, separate from the body"
    - "A type of PHP variable"
    - "A CSS styling rule"
  answer: 1
  explanation: "Headers carry metadata about the request or response, such as content type or where to redirect, separate from the visible body content."
- question: "Which header value tells the browser to navigate to a different URL?"
  options:
    - "Content-Type"
    - "Location"
    - "Redirect-To"
    - "Body"
  answer: 1
  explanation: "Setting the Location header instructs the browser to load the specified URL instead of the current response."
- question: "Why must exit be called right after header('Location: ...')?"
  options:
    - "It is not actually necessary"
    - "To stop the rest of the script from running and producing unwanted output after the redirect is sent"
    - "It makes the redirect happen faster"
    - "exit is required syntax for header() to work at all"
  answer: 1
  explanation: "Without exit, PHP continues executing the script even though the browser is about to navigate away, which can cause wasted or problematic output."
- question: "What causes a 'headers already sent' error?"
  options:
    - "Calling header() twice in the same script"
    - "Calling header() after some output (like echo or HTML) has already been sent"
    - "Using session_start() before header()"
    - "Redirecting to an external website"
  answer: 1
  explanation: "Headers must be sent before any body content; once output has started, PHP can no longer add or change headers."
```
