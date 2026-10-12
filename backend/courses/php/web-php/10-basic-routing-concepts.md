---
slug: basic-routing-concepts
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Basic Routing Concepts"
difficulty: beginner
order: 10
estimatedMinutes: 11
tags: [routing, url, http]
---

## Learning Objective

By the end of this lesson, you will understand the basic idea of "routing" — deciding what content to show based on the URL a visitor requested — and you will be able to write a simple PHP script that manually routes between a few different pages.

## Explanation

Every request a browser sends includes the URL the visitor asked for. So far in this chapter, you have mostly written scripts that each live in their own file and simply do one job, like handling a single form submission. But many websites want to show different content — a home page, an "about" page, a "contact" page — all through a single entry point, or at least want a script to make a decision about what to display based on the exact URL or a query parameter it received.

This decision-making process — looking at the incoming request and deciding which piece of code or content should handle it — is called **routing**. It is called that because it is similar to how a letter gets "routed" to the correct department based on the address written on it.

There are two very common beginner-level ways to read what was requested:

1. Using a **query string parameter**, like `?page=about`, read through `$_GET['page']` (you already learned about `$_GET` in the GET and POST requests lesson).
2. Using the actual URL path itself, available in PHP through `$_SERVER['REQUEST_URI']`, which contains the path portion of the requested URL, such as `/about` or `/contact?ref=footer`.

Once you have that value, a simple `switch` statement (or a series of `if`/`elseif` checks) can decide what to display for each possible value, with a sensible default (often called a "404" or "not found" case) for anything unrecognized.

```php
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        echo "Welcome to the homepage!";
        break;
    case 'about':
        echo "This is the about page.";
        break;
    default:
        echo "Page not found.";
}
```

This works for a small number of pages, but notice how it can quickly get unwieldy: every new page needs a new `case`, all the display logic is crammed into one file, and there is no clean way to handle more advanced needs, such as URLs with dynamic parts (like `/products/42` showing product number 42), matching multiple similar patterns, or organizing each page's code into its own separate file while still going through one central place.

## Why It Matters

Understanding this manual approach matters because it builds the intuition for a problem that essentially every real-world web application has to solve: matching an incoming URL to the right piece of code. Small scripts can get away with a manual `switch` statement like the one above. But as an application grows to dozens or hundreds of pages, doing this by hand becomes messy and error-prone. This is exactly the kind of problem that a framework like Symfony, which you will study later in this course, solves with a dedicated, much more powerful **routing system** — one that can match complex URL patterns, extract dynamic parts of a URL automatically, and cleanly connect each route to its own piece of code, without a giant hand-written `switch` statement. Learning the manual version first makes the framework's routing system feel like a natural, well-motivated upgrade rather than a mysterious black box.

## Example

A tiny website has three sections — home, about, and contact — all handled through one PHP file, chosen by a `page` query string parameter such as `index.php?page=contact`.

## Code

```php
<?php
// Read the requested page from the query string, defaulting to 'home'
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        echo "<h1>Home</h1><p>Welcome to our simple website!</p>";
        break;

    case 'about':
        echo "<h1>About Us</h1><p>We build small PHP projects for learning.</p>";
        break;

    case 'contact':
        echo "<h1>Contact</h1><p>Email us at hello@example.com.</p>";
        break;

    default:
        http_response_code(404);
        echo "<h1>404</h1><p>Sorry, that page does not exist.</p>";
}
```

## Code Explanation

- `$_GET['page'] ?? 'home'` reads the `page` query string parameter if it was provided, and falls back to the string `'home'` if it was not present at all, using the null coalescing operator so no warning is triggered when the parameter is missing.
- The `switch` statement compares `$page` against each known page name, running the matching block of code and then stopping with `break` so the following cases are not also executed.
- Each recognized case simply echoes the HTML content for that "page" directly — in a larger site, each case would more likely `include` a separate file rather than echoing HTML inline, as you learned in the previous lesson.
- The `default` case handles any value that does not match a known page, which is essential — without it, an unrecognized `page` value would silently produce no output at all.
- `http_response_code(404)` sets the HTTP status code of the response to `404 Not Found`, correctly informing the browser (and search engines, and any automated tools) that the requested page genuinely does not exist, rather than returning the default success code with error text in the body.

## Common Mistakes

- **Forgetting a default/fallback case.** Without one, an unexpected or mistyped `page` value produces a blank page with no explanation, which is confusing for visitors and hard to debug.
- **Not setting an appropriate HTTP status code for a "not found" page.** Returning a normal `200 OK` status with "page not found" text in the body is technically incorrect and can confuse browsers, search engines, and monitoring tools.
- **Mixing large amounts of HTML directly inside the routing script.** As the number of pages grows, this becomes very hard to maintain — it is usually better to keep each page's content in its own file and use `include` (from the previous lesson) inside each case.
- **Trusting the query parameter value without validation in more complex cases**, for example if the value were later used to directly build a file path (`include $_GET['page'] . '.php';`), which would allow a visitor to potentially load arbitrary files. Always match against a known, fixed list of allowed values, as the `switch` statement does here.

## Best Practices

- Always include a default/"not found" branch in any manual routing logic, and give it an appropriate HTTP status code.
- Keep each "page's" actual content in its own file, and use the routing script only to decide which file to `include`, keeping logic and content cleanly separated.
- Never use a raw, unvalidated request value directly as a file path or file name; always match it against a fixed, known set of allowed options first.
- Recognize the limits of this manual approach early — once you need dynamic URL segments (like an ID in `/products/42`) or many more pages, it's a strong signal that a dedicated routing system (such as the one Symfony provides) is the better tool.

## Exercise

Extend the example so it also supports a `"faq"` page, showing the heading `"FAQ"` and the text `"Frequently asked questions will go here."`. Make sure visiting an unknown page still correctly returns a 404 status.

## Solution

```php
<?php
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        echo "<h1>Home</h1><p>Welcome to our simple website!</p>";
        break;

    case 'about':
        echo "<h1>About Us</h1><p>We build small PHP projects for learning.</p>";
        break;

    case 'contact':
        echo "<h1>Contact</h1><p>Email us at hello@example.com.</p>";
        break;

    case 'faq':
        echo "<h1>FAQ</h1><p>Frequently asked questions will go here.</p>";
        break;

    default:
        http_response_code(404);
        echo "<h1>404</h1><p>Sorry, that page does not exist.</p>";
}
```

The only change needed is adding one more `case 'faq':` block, placed alongside the existing cases and ending with its own `break`, so it does not fall through into the `default` block. Because the `default` case was already written to catch anything unmatched, any page name other than `home`, `about`, `contact`, or `faq` still correctly triggers the 404 response.

## Mini Quiz

```yaml
- question: "What does 'routing' mean in the context of a web application?"
  options:
    - "Encrypting data sent between browser and server"
    - "Deciding what content or code should handle an incoming request based on its URL"
    - "Storing data across multiple requests"
    - "Validating a form's input fields"
  answer: 1
  explanation: "Routing is the process of matching an incoming request's URL to the right piece of code or content to run."
- question: "Which superglobal can be used to read a query string parameter for manual routing, like ?page=about?"
  options:
    - "$_SERVER"
    - "$_GET"
    - "$_FILES"
    - "$_COOKIE"
  answer: 1
  explanation: "$_GET holds query string parameters sent with the request, such as page=about."
- question: "Why is it important to include a default case in a routing switch statement?"
  options:
    - "PHP requires every switch to have a default case"
    - "Without it, unrecognized page values would silently produce no output, and no proper 'not found' response would be given"
    - "It makes the code run faster"
    - "It is only needed for security, not usability"
  answer: 1
  explanation: "A default case handles any unmatched value, letting you show a proper 'not found' message and status code instead of a blank page."
- question: "According to the lesson, what does a framework like Symfony offer compared to this manual switch-based routing?"
  options:
    - "Nothing different, it works the same way"
    - "A dedicated, more powerful routing system that can handle complex patterns and dynamic URL parts cleanly"
    - "It removes the need for URLs entirely"
    - "It only works for command-line scripts, not websites"
  answer: 1
  explanation: "Frameworks like Symfony provide a dedicated routing system that handles complex matching and dynamic URL segments far more cleanly than a hand-written switch statement."
```
