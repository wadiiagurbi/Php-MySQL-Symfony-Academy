---
slug: what-is-php
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "What is PHP?"
difficulty: beginner
order: 1
estimatedMinutes: 8
tags: [php, basics, introduction]
---

## Learning Objective

By the end of this lesson, you will understand what PHP is, what it is used for, and
why websites like Facebook, Wikipedia, and WordPress sites run on it.

## Explanation

PHP stands for **"PHP: Hypertext Preprocessor"** (yes, the name is a little strange —
it used to stand for "Personal Home Page" and the acronym stuck even after the meaning
changed).

PHP is a **programming language**. A programming language is a precise set of
instructions a computer can follow. You write PHP code in a text file, and a program
called the **PHP interpreter** reads that file and carries out the instructions.

PHP is specifically a **server-side scripting language**. That phrase has two parts
worth unpacking:

- **Scripting language**: PHP code is not compiled into a separate program ahead of
  time (like C++ is). Instead, it is read and executed directly, line by line, each
  time it runs.
- **Server-side**: PHP code runs on a **server** — a computer that receives requests
  from visitors' browsers, does some work, and sends back a result. This is the
  opposite of **client-side** code (like JavaScript running in a browser), which runs
  on the visitor's own computer.

Here is the typical flow when someone visits a PHP-powered website:

1. A visitor types a URL into their browser and presses Enter.
2. The browser sends a **request** over the internet to a web server.
3. The web server hands that request to the PHP interpreter.
4. PHP runs the relevant `.php` file, often fetching data from a database along the
   way.
5. PHP produces plain HTML (and possibly CSS/JavaScript) as output.
6. The web server sends that HTML back to the browser as a **response**.
7. The browser displays the page.

The visitor's browser never sees your PHP code — only the HTML it generated. This is
why "server-side" matters: the logic and any secret information (like database
passwords) stay safely on the server.

## Why It Matters

PHP matters because it is one of the most widely used languages for building dynamic
websites — pages whose content changes based on data, user input, or time (as opposed
to a "static" page that always shows the exact same content).

A few reasons PHP is worth learning:

- It powers a huge share of the web, including WordPress, which alone runs on a large
  percentage of all websites.
- It is designed specifically for web development, so common tasks (reading form data,
  talking to databases, managing user sessions) are built in or just one function call
  away.
- It has a mature ecosystem, including **Symfony**, the framework this entire course
  builds toward, which is used by companies to build large, maintainable applications.
- It is beginner-friendly: you can see a result (a web page) very quickly, which makes
  learning motivating.

## Example

Imagine a simple online store page that shows "Welcome back, Sarah!" if Sarah is
logged in, or "Welcome, Guest!" if nobody is logged in. That single word that changes
("Sarah" vs. "Guest") is decided by code running on the server — that decision-making
is exactly the kind of job PHP does.

## Code

```php
<?php

$visitorName = "Sarah";

echo "Welcome back, " . $visitorName . "!";
```

## Code Explanation

- `<?php` tells the PHP interpreter "everything after this is PHP code, please run
  it." Every PHP file's code must start with this tag.
- `$visitorName = "Sarah";` creates a **variable** named `visitorName` (variables
  always start with `$` in PHP) and stores the text `"Sarah"` inside it. We will cover
  variables in detail in the next lesson.
- `echo` is a PHP instruction that outputs text. Whatever comes after `echo` becomes
  part of the HTML sent back to the browser.
- The `.` (dot) joins two pieces of text together. This is called **string
  concatenation**. So `"Welcome back, " . $visitorName . "!"` produces the single
  piece of text `"Welcome back, Sarah!"`.
- The line ends with a semicolon `;`. In PHP, every statement (instruction) ends with
  a semicolon, similar to a period at the end of an English sentence.

If you ran this file on a PHP-enabled server, the browser would receive and display:

```
Welcome back, Sarah!
```

## Common Mistakes

- **Forgetting the opening `<?php` tag.** Without it, the web server treats the whole
  file as plain text/HTML and none of your code runs — you would just see your PHP
  code printed literally on the page.
- **Confusing PHP with client-side languages like JavaScript.** PHP code never
  reaches the visitor's browser; only its *output* does. If you "View Source" on a
  PHP-generated page, you will see HTML, never the PHP code that created it.
- **Thinking PHP only works with HTML.** PHP can output HTML, but it can also produce
  JSON (for APIs), plain text, images, PDFs, and more. We will build JSON APIs later
  in this course.

## Best Practices

- Always start PHP files that contain only PHP code with `<?php` and, in modern PHP,
  it is common to leave off the closing `?>` tag at the very end of pure-PHP files to
  avoid accidental extra whitespace in the output.
- Save PHP files with the `.php` extension so the web server knows to run them through
  the PHP interpreter instead of serving them as plain text.
- Keep this mental model firmly in mind as you continue this course: **PHP runs on the
  server, before the page ever reaches the visitor.** Almost every confusing bug a PHP
  beginner hits traces back to forgetting this.

## Exercise

Without running any code, answer this: if a PHP file contains
`echo "2 + 2 = " . (2 + 2);`, what exact text will the visitor's browser display?

## Solution

The browser will display:

```
2 + 2 = 4
```

PHP evaluates the expression `(2 + 2)` first, getting `4`, then concatenates it onto
the string `"2 + 2 = "`, producing `"2 + 2 = 4"`, which `echo` sends to the browser.

## Mini Quiz

```yaml
- question: "Where does PHP code actually run?"
  options:
    - "In the visitor's web browser"
    - "On the web server, before the response is sent"
    - "Inside the visitor's operating system"
    - "It runs equally on both the browser and the server"
  answer: 1
  explanation: "PHP is a server-side language: it runs on the server, and only its output (usually HTML) is sent to the browser."
- question: "What does a visitor see if they View Source on a page generated by PHP?"
  options:
    - "The original PHP code, including variables and functions"
    - "The HTML that the PHP code produced"
    - "An error message"
    - "A blank page"
  answer: 1
  explanation: "Browsers only ever receive the output PHP generates (typically HTML) — never the PHP source code itself."
- question: "Which of these is true about PHP?"
  options:
    - "It must be compiled into a separate program before it can run"
    - "It only works with MySQL databases"
    - "It is a scripting language commonly used for server-side web development"
    - "It can only output plain text, never HTML"
  answer: 2
  explanation: "PHP is a scripting language designed for server-side web development, and it is commonly used to generate HTML, JSON, and other output formats."
```
