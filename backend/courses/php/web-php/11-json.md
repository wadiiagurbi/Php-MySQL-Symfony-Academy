---
slug: json-in-php
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Working with JSON in PHP"
difficulty: beginner
order: 11
estimatedMinutes: 10
tags: [php, json, api]
---

## Learning Objective

By the end of this lesson, you will be able to convert PHP arrays to JSON with `json_encode`, convert JSON back with `json_decode`, and send a JSON response from a PHP script.

## Explanation

**JSON** (JavaScript Object Notation) is a lightweight text format for exchanging structured data between systems. It looks like this: `{"name": "Sarah", "age": 30}`. A JSON **object** with curly braces corresponds to a PHP associative array, and a JSON **array** with square brackets corresponds to a PHP indexed array.

PHP provides two functions for this conversion. `json_encode()` takes a PHP value (usually an array) and returns a JSON string. `json_decode()` takes a JSON string and returns a PHP value; with the second argument `true`, it returns associative arrays instead of objects. Conversion from PHP to JSON is called **encoding**, and conversion from JSON back to PHP is called **decoding**.

When a PHP script acts as an **API endpoint** (a URL that returns data instead of a web page), it should send the header `Content-Type: application/json` before echoing the encoded string, so the receiver knows the body is JSON and not HTML.

## Why It Matters

Modern web development runs on JSON: JavaScript front ends fetch it, mobile apps consume it, and Symfony APIs return it on nearly every request. Understanding encoding and decoding now makes the later Symfony API lessons straightforward, since the same two functions sit underneath every JSON response you will build.

## Example

A product page needs to expose one product as data for a JavaScript widget. The PHP script builds an associative array, encodes it to JSON, labels the response correctly, and outputs it.

## Code

```php
<?php

$product = [
    'id' => 42,
    'name' => 'Coffee Mug',
    'price' => 12.99,
    'inStock' => true,
];

header('Content-Type: application/json');
echo json_encode($product);
```

## Code Explanation

- `$product` is an associative array, PHP's equivalent of a JSON object, holding the product's fields with their native types (integer, string, float, boolean).
- `header('Content-Type: application/json');` tells the browser or API client that the response body is JSON, and it must run before any output.
- `json_encode($product)` converts the array into the string `{"id":42,"name":"Coffee Mug","price":12.99,"inStock":true}`.
- `echo` sends that string as the response body, which the JavaScript widget can parse directly.

## Common Mistakes

- **Sending output before `header()`.** Even a blank line before `<?php` triggers a "headers already sent" warning and the content type is lost.
- **Forgetting the `true` flag in `json_decode`.** Without it, you get nested objects (`$data->name`) instead of arrays (`$data['name']`), which surprises beginners.
- **Encoding data with invalid UTF-8 characters.** `json_encode` returns `false` on bad input; stored text should use UTF-8 throughout.

## Best Practices

- Always send `Content-Type: application/json` from endpoints that return JSON.
- Validate decoded data before using it, since API input can be missing fields or hold wrong types.
- Use `JSON_PRETTY_PRINT` while debugging so encoded output is human-readable, and drop it in production.

## Exercise

Build an associative array for a user with name `"Tom"`, email `"tom@example.com"`, and age `28`, then output it as a JSON response with the correct content-type header.

## Solution

```php
<?php

$user = [
    'name' => 'Tom',
    'email' => 'tom@example.com',
    'age' => 28,
];

header('Content-Type: application/json');
echo json_encode($user);
```

The array mirrors the desired JSON object, the header labels the response, and `json_encode` performs the conversion.

## Mini Quiz

```yaml
- question: "What does json_encode() do?"
  options:
    - "It converts a PHP value such as an array into a JSON string"
    - "It connects PHP to a MySQL database"
    - "It encrypts passwords securely"
    - "It deletes a JSON file from disk"
  answer: 0
  explanation: "json_encode serializes PHP data into the JSON text format for transport to browsers, apps, or other services."
- question: "What does json_decode($json, true) return?"
  options:
    - "An XML document"
    - "A PHP associative array built from the JSON string"
    - "A database connection object"
    - "Nothing, the second argument is ignored"
  answer: 1
  explanation: "The true flag requests arrays instead of stdClass objects, so decoded objects become associative arrays."
- question: "Why send header(Content-Type: application/json) before echoing JSON?"
  options:
    - "It makes the JSON download faster"
    - "It tells the client the body is JSON rather than HTML so it can be parsed correctly"
    - "It is required for PHP to run at all"
    - "It encrypts the response automatically"
  answer: 1
  explanation: "The content-type header declares the body's format, letting clients parse the response as JSON with confidence."
```
