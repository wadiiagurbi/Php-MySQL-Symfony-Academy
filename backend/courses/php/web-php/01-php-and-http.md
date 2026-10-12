---
slug: php-and-http
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "PHP and HTTP"
difficulty: beginner
order: 1
estimatedMinutes: 10
tags: [php, http, web, basics]
---

## Learning Objective

By the end of this lesson, you will understand what HTTP is, how a web browser and a web server talk to each other, and where PHP fits into that conversation.

## Explanation

Up until now, you have written PHP scripts and probably run them from the command line, or seen their output printed directly. But PHP was actually built for the web, and to understand PHP as a web language, you first need to understand **HTTP**.

HTTP stands for **HyperText Transfer Protocol**. A "protocol" is just a fancy word for "an agreed set of rules two things use to communicate." HTTP is the set of rules that web browsers (like Chrome or Firefox) and web servers (computers that store and serve websites) use to talk to each other.

Here is the basic idea: every time you visit a website, your browser sends a **request** to a server, asking for something (a page, an image, some data). The server processes that request and sends back a **response**. This is called the **request-response cycle**, and it is the foundation of everything that happens on the web.

- A **request** contains information like: which website/page you want, what "method" of request it is (more on this in the next lesson), and sometimes extra data (like the contents of a form you filled in).
- A **response** contains the requested content (for example, the HTML of a page) along with a **status code**, a short number that tells the browser what happened. `200` means "OK, here's your page." `404` means "not found." `500` means "the server had an error."

So where does PHP come in? When a request arrives at a web server for a `.php` file, the server does not just send that file's raw text back to the browser. Instead, it runs the PHP code through the PHP interpreter first. The PHP script executes on the server, and whatever it `echo`s or `print`s becomes the HTML that gets sent back as the response. The browser never sees your PHP code — it only sees the final HTML output.

This is why PHP is called a **server-side language**: it runs on the server, before the response is sent, not inside the browser like JavaScript does.

## Why It Matters

Every dynamic website you have ever used — a login page, an online shop, a social media feed — relies on this request-response cycle. Understanding it is essential because:

- It explains why your PHP code never appears in the browser's "View Source" — only its output does.
- It explains why a page can look different for different visitors: the PHP script can generate different HTML based on who is asking and what they asked for.
- Later, when you learn Symfony (a PHP framework), you will see that the entire framework is organized around this exact idea: receive a request, do some work, return a response. Symfony even has classes literally named `Request` and `Response`.

Without understanding HTTP, frameworks like Symfony feel like magic. With it, they feel like a natural, organized way to handle something you already understand.

## Example

Imagine you type `http://example.com/welcome.php` into your browser and press Enter. Here is what happens, step by step:

1. Your browser sends an HTTP **request** to the server that hosts `example.com`, asking for `welcome.php`.
2. The server finds `welcome.php` and hands it to the PHP interpreter to run.
3. The PHP script runs on the server and produces some HTML output using `echo`.
4. The server packages that HTML into an HTTP **response**, along with a status code like `200`.
5. Your browser receives the response and renders the HTML on your screen.

## Code

```php
<?php
// welcome.php
// This script runs on the SERVER. The browser never sees this PHP code.

$visitorTime = date('H:i');

echo "<!DOCTYPE html>";
echo "<html><head><title>Welcome</title></head><body>";
echo "<h1>Hello, visitor!</h1>";
echo "<p>The server's current time is: {$visitorTime}</p>";
echo "</body></html>";
```

## Code Explanation

- `date('H:i')` calculates the current time on the **server**, not the visitor's computer. This is a good example of "work done on the server before the response is sent."
- Every `echo` statement adds a piece of text to the eventual HTTP response body. By the time PHP finishes running, all these pieces are joined together into one complete HTML document.
- The browser that requests this page will only ever see the final HTML (`<!DOCTYPE html><html>...`) — it has no idea a variable called `$visitorTime` or a `date()` function was involved. That entire process happened on the server, invisibly, before the response left it.

## Common Mistakes

- **Thinking PHP runs in the browser.** PHP always runs on the server. If you want code to run inside the browser, on the visitor's own computer, you need JavaScript, not PHP.
- **Expecting to "see" PHP code by viewing a page's source.** Browsers only ever receive the final HTML output. If your PHP code appears as plain text in the browser, it usually means the server is not configured to run PHP files at all (it's just serving the raw file).
- **Confusing the request with the response.** New learners sometimes mix these up. Remember: the browser always sends the request first; the server always sends the response back.

## Best Practices

- Always think of a PHP page as producing a response to a request — this mental model will make debugging much easier ("what did the request contain? what should the response contain?").
- Keep your PHP scripts focused on generating correct, well-formed output. A missing closing tag in your HTML output can break the whole page, even if your PHP logic was perfect.
- When testing locally, use a proper local server (like PHP's built-in server, `php -S localhost:8000`) rather than just opening `.php` files directly in your browser as local files — otherwise, PHP is never actually executed.

## Exercise

Write a PHP script named `time-check.php` that outputs a simple HTML page. The page should display the current server date and time in the format `Y-m-d H:i:s`, inside an `<h1>` tag, and below it a paragraph that says "This page was generated by the server before it was sent to your browser."

## Solution

```php
<?php
$now = date('Y-m-d H:i:s');

echo "<!DOCTYPE html>";
echo "<html><head><title>Time Check</title></head><body>";
echo "<h1>{$now}</h1>";
echo "<p>This page was generated by the server before it was sent to your browser.</p>";
echo "</body></html>";
```

This works because `date('Y-m-d H:i:s')` reads the server's clock at the moment the script runs, and every `echo` call appends more HTML to what will eventually become the full HTTP response body sent back to the browser.

## Mini Quiz

```yaml
- question: "What does HTTP stand for?"
  options:
    - "HyperText Transfer Protocol"
    - "High Traffic Transport Process"
    - "Hosted Text Transfer Point"
    - "HyperText Testing Program"
  answer: 0
  explanation: "HTTP stands for HyperText Transfer Protocol, the set of rules browsers and servers use to communicate."
- question: "Where does PHP code actually run?"
  options:
    - "Inside the visitor's web browser"
    - "On the web server, before the response is sent"
    - "Inside the HTTP request itself"
    - "It runs equally on both the browser and the server"
  answer: 1
  explanation: "PHP is a server-side language: it executes on the server, and only its output (usually HTML) is sent to the browser."
- question: "What does an HTTP status code like 200 or 404 represent?"
  options:
    - "The size of the response in kilobytes"
    - "The version of PHP running on the server"
    - "A short signal telling the browser what happened with the request"
    - "The name of the file that was requested"
  answer: 2
  explanation: "Status codes are short numeric signals in the response: 200 means success, 404 means not found, 500 means server error, and so on."
```
