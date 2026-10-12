---
slug: twig-basics
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: twig
chapterTitle: "Chapter 4 — Twig & Templates"
chapterOrder: 4
title: "Twig Basics"
difficulty: beginner
order: 1
estimatedMinutes: 10
tags: [symfony, twig, templates]
---

## Learning Objective

Understand what Twig is, why Symfony uses a templating engine instead of mixing raw PHP into HTML, and how to render your first Twig template from a controller.

## Explanation

**Twig** is Symfony's default templating engine. A **templating engine** is a tool that takes an HTML file containing special placeholders and replaces those placeholders with real data to produce a final HTML page. A **template** is simply that HTML file with placeholders inside it — it lives in the `templates/` directory of your Symfony project.

You might wonder why you cannot just write `<?php echo $name; ?>` directly inside your HTML. You technically could, but mixing database logic, loops, and HTML in one file quickly becomes messy and unsafe: it is easy to forget to escape user input, which opens the door to **XSS (Cross-Site Scripting)**, an attack where malicious JavaScript stored in your database runs in other users' browsers. Twig solves this by auto-escaping every variable it prints, so `<script>` typed by a user shows up as harmless text instead of running as code.

Twig templates use two main delimiter styles. Double curly braces `{{ ... }}` print a value into the page (called an **expression** or "print" tag). Curly brace plus percent `{% ... %}` runs logic such as loops and conditions without printing anything directly (called a **statement** tag). You will practice both in the next lesson; for now, just recognize that `{{ }}` outputs and `{% %}` controls.

To connect a controller to a template, Symfony's base controller class (`AbstractController`, the class your controllers extend to gain helper methods) provides a `$this->render()` helper. You pass it the template file name plus an array of data, and it returns a finished HTML **Response** object — the object Symfony converts into what the browser receives.

## Why It Matters

Almost every Symfony application that shows HTML pages — admin panels, marketing pages, confirmation screens, error pages — renders them through Twig, even when the main product is a JSON API consumed by a frontend. Understanding `render()` and the `templates/` directory is therefore essential onboarding knowledge: when a teammate says "the welcome email preview page looks wrong," the first place you look is the Twig template behind it, not the controller.

## Example

A task-tracking app needs a simple "hello" page at `/hello/Sophie` that greets the visitor by name. The name comes from the URL, the controller passes it to Twig, and Twig safely prints it inside an `<h1>` heading.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HelloController extends AbstractController
{
    #[Route('/hello/{name}', name: 'app_hello', methods: ['GET'])]
    public function hello(string $name): Response
    {
        return $this->render('hello/index.html.twig', [
            'name' => $name,
        ]);
    }
}
```

## Code Explanation

- `namespace App\Controller;` places the class where Symfony expects controllers (`src/Controller/`), so its routes are discovered automatically.
- `#[Route('/hello/{name}', ...)]` maps GET requests like `/hello/Sophie` to this method, passing `Sophie` as the `$name` argument.
- `$this->render('hello/index.html.twig', [...])` tells Twig to load the `templates/hello/index.html.twig` file and make a variable called `name` available inside it.
- The array key `'name'` becomes the variable name used in the template (`{{ name }}`), while its value comes from the URL in this example.
- The method returns a `Response` object containing finished HTML, which Symfony sends back to the browser with the correct headers.

## Common Mistakes

- Putting templates in the wrong folder, such as `src/` or `public/`. Twig only looks inside `templates/` by default, so `templates/hello/index.html.twig` is the correct path here.
- Forgetting to pass a variable the template uses. If the template prints `{{ name }}` but the controller never passes `'name'`, Twig throws an error instead of silently printing nothing.
- Disabling auto-escaping with the `|raw` filter "to fix" HTML showing as text. That filter turns off XSS protection — the real fix is usually to pass cleaner data, not to bypass escaping.

## Best Practices

- Keep logic out of templates: compute and prepare data in the controller (or a service), and let Twig focus purely on displaying it.
- Name templates after what they show (`hello/index.html.twig`, `task/list.html.twig`) so teammates can find them without opening every controller.
- Use `$this->render()` from `AbstractController` rather than building `Response` objects with hand-concatenated HTML strings.

## Exercise

Create a route `/goodbye/{name}` (GET only, named `app_goodbye`) whose controller method renders the template `goodbye/index.html.twig` and passes the URL `{name}` value to it as a `name` variable.

## Solution

Create the controller method with a `#[Route]` attribute and call `$this->render()` with the template path and variables array. Then create the `templates/goodbye/index.html.twig` file that prints `{{ name }}`. The controller passes data, the template displays it — neither does the other's job.

## Mini Quiz

```yaml
- question: "What is Twig in a Symfony application?"
  options:
    - "A database server bundled with Symfony"
    - "Symfony's default templating engine, which turns templates with placeholders into final HTML"
    - "A JavaScript framework for building frontends"
    - "A tool that replaces the need for controllers"
  answer: 1
  explanation: "Twig takes HTML templates containing placeholders and combines them with data from the controller to produce the final page, with automatic escaping for safety."
- question: "What is the difference between {{ ... }} and {% ... %} in Twig?"
  options:
    - "There is no difference; they are interchangeable"
    - "{{ ... }} prints a value into the page, while {% ... %} runs logic like loops and conditions"
    - "{% ... %} prints values and {{ ... }} runs logic"
    - "Both only work inside controllers, not templates"
  answer: 1
  explanation: "{{ }} is the print tag that outputs a value, while {% %} is the statement tag that executes control logic without printing directly."
- question: "What does $this->render('hello/index.html.twig', ['name' => $name]) do?"
  options:
    - "It deletes the template file from disk"
    - "It loads the template from templates/, makes a name variable available inside it, and returns a finished HTML Response"
    - "It sends an email containing the template"
    - "It creates a new database table called hello"
  answer: 1
  explanation: "render() loads the given template, injects the array values as template variables, and returns a Response object with the resulting HTML."
```
