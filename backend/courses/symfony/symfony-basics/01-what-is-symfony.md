---
slug: what-is-symfony
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: symfony-basics
chapterTitle: "Chapter 1 — Symfony Basics"
chapterOrder: 1
title: "What is Symfony?"
difficulty: intermediate
order: 1
estimatedMinutes: 10
tags: [symfony, framework, basics]
---

## Learning Objective

Understand what a "framework" is, what problem it solves compared to writing everything from scratch in plain PHP, and what Symfony specifically is.

## Explanation

Up to now in this course, you have written plain PHP: you handled HTTP requests yourself (reading `$_GET`, `$_POST`), talked to MySQL directly, and organized your own classes by hand. That works, but imagine building a real web application — say, a task-tracking app with user accounts, a database, login/logout, forms, and JSON APIs. For every single one of those pieces, you would need to write (and keep secure and bug-free) code that:

- Parses incoming HTTP requests and figures out which piece of your code should handle a given URL.
- Builds HTTP responses correctly (status codes, headers, content type).
- Connects to the database, escapes queries safely, and maps rows to objects.
- Handles user login sessions, password hashing, and access control.
- Validates form input and reports errors back to the user.

Nearly every serious PHP application needs all of these things, and they are almost identical from one project to the next. A **framework** is a collection of pre-built, tested, reusable code that solves these recurring problems for you, so you only have to write the logic that is actually unique to *your* application (e.g., "what does a task look like, and what happens when someone marks it done"). Instead of starting from a blank file every time, you start from a solid, working skeleton and fill in your business logic.

**Symfony** is one of the most widely used PHP frameworks, especially for professional, long-lived applications. It provides ready-made, well-tested components for routing (mapping URLs to code), handling HTTP requests/responses, connecting to databases (via a library called Doctrine), validating data, handling security/authentication, sending emails, and much more. Symfony is also not "all or nothing" — many of its individual pieces (called *components*) are also used *inside other frameworks and tools*, including parts of Laravel and Drupal, because they are well-built and independently reusable.

Symfony encourages (though does not strictly force) an architecture called **MVC — Model-View-Controller**. This is a way of separating three concerns in your application:

- **Model**: your data and the rules around it (e.g., a `Task` class representing a row in your `tasks` table, and the logic for what a "valid" task looks like).
- **View**: what gets shown to the user (an HTML page, or in this course's case, usually a JSON response consumed by a Vue frontend).
- **Controller**: the glue — the code that receives an incoming request, asks the Model for data or tells it to do something, and decides what View/response to send back.

Separating these means your data logic does not get tangled up with your HTML/JSON formatting logic, which makes the codebase far easier to test, reuse, and reason about as it grows.

## Why It Matters

Professional PHP teams overwhelmingly build new backend applications on top of a framework rather than from scratch, and Symfony specifically powers a huge share of production PHP systems, from small internal tools to platforms handling millions of requests (Symfony itself is also the foundation that the popular Laravel framework partially builds on, via shared components). If you plan to work as a PHP developer, you will very likely either maintain an existing Symfony application or be asked to build a new one. Understanding *why* the framework exists — not just its syntax — means you will make better decisions about where your own code should live, instead of fighting the framework's conventions.

## Example

Without a framework, handling a request to `/tasks/5` means manually inspecting the URL string, extracting `5`, running your own SQL query, building a JSON string by hand, and setting headers yourself with `header()`. With Symfony, you instead write one small PHP class with a route attribute saying "this method handles `/tasks/{id}`," and Symfony handles finding that code, extracting `5` as a parameter, and turning your return value into a proper HTTP response.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    // This one small class replaces everything you'd otherwise write by hand:
    // URL parsing, method dispatch, and response formatting.
    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        // In a real app, this data would come from the database (Model layer).
        $task = ['id' => $id, 'title' => 'Write Symfony lesson', 'done' => false];

        return $this->json($task);
    }
}
```

## Code Explanation

- `namespace App\Controller;` places this class in the conventional location Symfony expects controllers to live in (`src/Controller/`).
- `class TaskController extends AbstractController` — extending Symfony's `AbstractController` gives this class helper methods like `$this->json(...)`, without you having to write JSON-encoding or header-setting logic yourself.
- `#[Route('/api/tasks/{id}', ...)]` is a PHP attribute (metadata attached to the method) that tells Symfony: "when a GET request comes in for a URL matching `/api/tasks/{id}`, run this method, and pass whatever value is in the URL's `{id}` slot as the `$id` argument." You will study attributes and routing in depth in the next chapter.
- `public function show(int $id): JsonResponse` is a normal PHP method — Symfony calls it for you; you never call it yourself.
- `$this->json($task)` is a helper from `AbstractController` that converts the PHP array into a proper JSON HTTP response, including the `Content-Type: application/json` header — something you would otherwise set manually with `header()`.

## Common Mistakes

- Thinking Symfony is "just for big companies" and unnecessarily complex for small projects. In reality, Symfony's skeleton starts very small, and you only pull in the pieces (bundles) your project actually needs.
- Assuming a framework removes the need to understand plain PHP and OOP. Symfony is written in, and used through, plain PHP classes — everything you learned about classes, interfaces, and objects earlier in this course applies directly.
- Believing MVC means literally three folders named Model, View, Controller. In Symfony, "Model" is usually represented by Doctrine entities in `src/Entity/`, and in an API-only app like the one this course builds, the "View" is often just a JSON array returned from the controller, not an HTML template.

## Best Practices

- Resist the urge to bypass the framework's conventions "just this once" (e.g., reading `$_GET` directly inside a controller) — use the abstractions Symfony gives you (like the `Request` object, covered in Chapter 3), so your code stays consistent and testable.
- Learn to distinguish framework code (Symfony's classes, which you should not need to modify) from your application code (classes you write in `src/`), which is where nearly all of your work will happen.
- When in doubt about "the Symfony way" to do something, prefer Symfony's own documented conventions over improvising — this consistency is a major reason large teams choose frameworks in the first place.

## Exercise

In your own words, write two or three sentences explaining to a junior developer who has never heard of frameworks: (1) what problem a framework solves, and (2) what the "C" in MVC stands for and what its job is.

## Solution

A framework is a pre-built, tested set of code that handles the repetitive infrastructure every web application needs — like matching URLs to code, building HTTP responses, and talking to a database — so developers only have to write the logic unique to their own application instead of reinventing routing, request handling, and database access from scratch every time.

The "C" stands for **Controller**. Its job is to receive an incoming request, ask the Model (your data/business logic) for whatever it needs, and decide what to send back as the response (the View) — it is the coordinating glue between "what the user asked for" and "what your application's data says," without containing the core business rules itself.

## Mini Quiz

```yaml
- question: "What core problem does a framework like Symfony solve?"
  options:
    - "It replaces the need to learn PHP"
    - "It provides pre-built, tested solutions to recurring problems like routing, HTTP handling, and database access, so you only write your app's unique logic"
    - "It makes PHP code run faster than plain PHP"
    - "It is only useful for very large companies"
  answer: 1
  explanation: "Frameworks exist to eliminate repetitive, error-prone infrastructure code (routing, request/response handling, DB access) that nearly every application needs, letting developers focus on unique business logic."

- question: "In the MVC pattern, what is the responsibility of the Controller?"
  options:
    - "Storing all the application's data permanently"
    - "Defining what HTML/CSS the user sees"
    - "Receiving the request, coordinating with the Model, and deciding what response (View) to return"
    - "Running database migrations"
  answer: 2
  explanation: "The Controller is the coordinating layer: it receives incoming requests, talks to the Model for data or actions, and determines what gets sent back as a response."

- question: "Which statement about Symfony is accurate?"
  options:
    - "Symfony is unrelated to plain PHP OOP concepts"
    - "Symfony forces you to always render full HTML pages, never JSON"
    - "Symfony provides reusable, independently testable components, some of which are also used by other PHP projects"
    - "Symfony eliminates the need to write any classes yourself"
  answer: 2
  explanation: "Symfony is built as a set of well-tested, reusable components; several of them are used beyond Symfony itself in other PHP projects, while your application code still consists of classes you write following Symfony's conventions."
```
