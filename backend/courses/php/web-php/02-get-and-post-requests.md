---
slug: get-and-post-requests
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "GET and POST Requests"
difficulty: beginner
order: 2
estimatedMinutes: 10
tags: [php, http, get, post]
---

## Learning Objective

By the end of this lesson, you will understand what an HTTP "method" is, the difference between GET and POST requests, and how to read the data they carry using PHP's `$_GET` and `$_POST` superglobal arrays.

## Explanation

In the previous lesson, you learned that a browser sends a **request** to a server and the server sends back a **response**. Every request also carries a **method**, which is a word that tells the server what kind of action the browser wants to perform. The two methods you will use most often as a beginner are **GET** and **POST**.

A **GET** request asks the server to *retrieve* something — a page, a search result, a list of products. Any extra data that goes along with a GET request is attached directly to the end of the URL, after a question mark, in what is called a **query string**. For example: `http://example.com/search.php?term=laptop&sort=price`. Here, `term=laptop` and `sort=price` are pieces of data, written as `key=value` pairs separated by `&`. Because this data lives in the URL itself, it is visible to anyone looking at the address bar, it gets saved in the browser's history, and it can be bookmarked.

A **POST** request asks the server to *do* something with data — submit a login form, save a new blog post, process a payment. The data for a POST request is not attached to the URL. Instead, it is sent inside the **body** of the request, a separate section of the request that is invisible in the address bar. This makes POST the right choice whenever the data is sensitive (like a password) or whenever submitting it should change something on the server (like creating an order).

PHP makes reading this data easy through two **superglobal arrays** — arrays that PHP fills in automatically and that are available inside every script, everywhere, without you needing to create them yourself:

- `$_GET` holds every key/value pair from the query string of a GET request.
- `$_POST` holds every key/value pair sent in the body of a POST request.

Both are ordinary associative arrays, so you access a value the same way you would with any array, using its key: `$_GET['term']` or `$_POST['username']`.

## Why It Matters

Almost every interactive feature on the web depends on this distinction. A product search box on an online shop uses GET, because searching does not change anything on the server, and because a shareable, bookmarkable URL like `?term=laptop` is genuinely useful — you can send that link to a friend and they will see the same search results. A login form or a "buy now" button uses POST, because logging in or buying something changes state on the server, and because you never want a password or credit card number sitting in plain view in a URL or saved in browser history.

Real frameworks, including Symfony which you will study later, build entire routing systems around this same GET/POST distinction: a route can be restricted to only respond to GET, only to POST, or both, depending on what the action is supposed to do.

## Example

Imagine a simple PHP page that greets a visitor by name, where the name comes from the URL: `http://example.com/greet.php?name=Sara`. When this page runs, PHP automatically places `"Sara"` into `$_GET['name']`, and the script can use it immediately.

## Code

```php
<?php
// greet.php
// Visit this as: greet.php?name=Sara&language=en

if (isset($_GET['name']) && $_GET['name'] !== '') {
    $name = $_GET['name'];
} else {
    $name = 'Guest';
}

$language = $_GET['language'] ?? 'en';

if ($language === 'fr') {
    echo "Bonjour, {$name} !";
} else {
    echo "Hello, {$name}!";
}
```

## Code Explanation

- `isset($_GET['name'])` checks whether the key `name` actually exists in the `$_GET` array. This matters because if someone visits `greet.php` with no query string at all, `$_GET['name']` would not exist, and trying to read a missing array key produces a PHP warning.
- The condition also checks `$_GET['name'] !== ''` to guard against a visitor submitting an empty value, like `greet.php?name=`.
- `$_GET['language'] ?? 'en'` uses PHP's null coalescing operator (`??`), which returns the value on the left if it exists and is not `null`, and otherwise returns the fallback on the right. This is a compact way to provide a default value for data that might be missing from `$_GET`.
- Because all of this data came from the URL's query string, everything after the `?` in the address the visitor typed or clicked is what fills `$_GET`.

## Common Mistakes

- **Reading `$_GET` or `$_POST` without checking the key exists first.** Accessing `$_GET['name']` directly, when no `name` key was sent, triggers an "undefined array key" warning. Always use `isset()`, `empty()`, or the `??` operator.
- **Using GET for sensitive or state-changing actions.** Never put a password, a payment amount, or a "delete this record" action behind a GET request — the data ends up visible in the URL, browser history, and server logs.
- **Assuming a form's method automatically matches how you read it in PHP.** If an HTML form's `method` attribute is `"get"`, its data lands in `$_GET`, not `$_POST`, no matter what you expect. The PHP code must match the form's actual method.
- **Forgetting that `$_GET` and `$_POST` values are always strings** (or arrays of strings), even if they look like numbers. `$_GET['age']` is the string `"25"`, not the integer `25`, until you convert it.

## Best Practices

- Use GET for anything that reads or searches data and does not change server state; use POST for anything that creates, updates, deletes, or otherwise changes something.
- Always provide a sensible default with `??` or check with `isset()` before using a value from `$_GET` or `$_POST`, since you can never fully control what a visitor sends.
- Never trust the contents of `$_GET` or `$_POST` blindly — treat them as visitor-supplied data that needs checking before use (you will learn how to properly validate and clean this data in the next lesson).

## Exercise

Write a PHP script called `price-calculator.php` that reads two values from the query string: `price` and `quantity`. If both are present, calculate and display the total cost (price multiplied by quantity) formatted as `"Total: $X"`. If either value is missing, display `"Please provide both price and quantity."` instead.

## Solution

```php
<?php
// price-calculator.php
// Visit as: price-calculator.php?price=19.99&quantity=3

if (isset($_GET['price']) && isset($_GET['quantity'])) {
    $price = (float) $_GET['price'];
    $quantity = (int) $_GET['quantity'];
    $total = $price * $quantity;

    echo "Total: $" . number_format($total, 2);
} else {
    echo "Please provide both price and quantity.";
}
```

This works because `isset()` confirms both `price` and `quantity` were actually included in the query string before any calculation happens. The values are cast with `(float)` and `(int)` because everything coming out of `$_GET` is a string, and PHP needs real numbers to multiply correctly. `number_format($total, 2)` then formats the result with exactly two decimal places, so `59.97` displays cleanly rather than something like `59.9699999999`.

## Mini Quiz

```yaml
- question: "Where does data sent with a GET request appear?"
  options:
    - "In the request body, hidden from the URL"
    - "In the URL itself, as a query string"
    - "In a special HTTP header only the server can read"
    - "It is not sent anywhere unless PHP asks for it"
  answer: 1
  explanation: "GET request data is attached to the URL as a query string, after a question mark, in key=value pairs."
- question: "Which PHP superglobal array holds data submitted through a POST request?"
  options:
    - "$_GET"
    - "$_REQUEST_BODY"
    - "$_POST"
    - "$_SERVER"
  answer: 2
  explanation: "$_POST holds the key/value pairs sent in the body of a POST request."
- question: "Which of these actions is most appropriate to send as a POST request rather than GET?"
  options:
    - "Searching a product catalog"
    - "Viewing page 2 of search results"
    - "Submitting a login form with a password"
    - "Sorting a list alphabetically"
  answer: 2
  explanation: "Submitting a password changes nothing about retrieving data and involves sensitive information, so it should never appear in a URL — POST keeps it out of the address bar and browser history."
- question: "What type are values inside $_GET and $_POST, even if they look numeric?"
  options:
    - "Integers"
    - "Floats"
    - "Strings"
    - "Booleans"
  answer: 2
  explanation: "All values coming from $_GET and $_POST are strings by default; you must cast them, e.g. with (int) or (float), to work with them as numbers."
```
