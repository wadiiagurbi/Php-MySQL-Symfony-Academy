---
slug: templates-and-variables
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: twig
chapterTitle: "Chapter 4 — Twig & Templates"
chapterOrder: 4
title: "Templates and Variables"
difficulty: beginner
order: 2
estimatedMinutes: 10
tags: [symfony, twig, templates, variables]
---

## Learning Objective

Learn how to pass variables from a controller into a Twig template, print them with `{{ }}`, and use simple loops and conditions with `{% %}` to display lists of data.

## Explanation

A **variable** in Twig is a named value your controller hands to the template — for example, a task title string, a boolean flag, or an array of tasks. The controller decides *what* data exists; the template decides *how* it looks on screen. This separation is the core idea behind the **MVC (Model-View-Controller)** pattern you met in Chapter 1: the controller prepares data, and the view (here, the Twig template) presents it.

Printing a variable uses the double-curly-brace syntax: `{{ task.title }}`. The **dot notation** (`task.title`) works for both array keys and object properties or getter methods, so `{{ task.title }}` prints the `title` key of an array *or* calls `getTitle()` on a `Task` object — Twig figures out which one automatically. If a variable might not exist, the `|default('...')` **filter** (a small function applied with the pipe `|` character that transforms a value) provides a fallback: `{{ nickname|default('Guest') }}`.

Logic uses `{% %}` statement tags. An `{% if %}` block shows content only when a condition is true, and an `{% for %}` block repeats content once per item in an array. Both must be closed with matching `{% endif %}` and `{% endfor %}` tags — forgetting a closing tag is the most common Twig syntax error beginners hit.

Twig automatically **escapes** (converts dangerous characters like `<` into harmless text) everything printed with `{{ }}`, which protects your pages from XSS attacks where a user smuggles JavaScript into stored data. You never need to call escaping functions yourself in normal templates.

## Why It Matters

Listing pages are everywhere in real applications: task lists, user tables, search results, order histories. Every one of them follows the same pattern — the controller fetches an array of items and the template loops over it with `{% for %}`. Mastering variables, dot notation, and `for`/`if` covers the large majority of day-to-day Twig work.

## Example

The task tracker needs a page at `/tasks` showing all tasks in a list. Completed tasks should show a check mark, and when there are no tasks at all, a friendly "No tasks yet" message should appear instead of an empty page.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskPageController extends AbstractController
{
    #[Route('/tasks', name: 'app_tasks', methods: ['GET'])]
    public function list(): Response
    {
        $tasks = [
            ['title' => 'Write Twig lesson', 'done' => false],
            ['title' => 'Review pull request', 'done' => true],
        ];

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks,
        ]);
    }
}
```

## Code Explanation

- The `$tasks` array stands in for database results — in a real app this would come from Doctrine (the database library), but the template receives it in exactly the same shape.
- `'tasks' => $tasks` makes a `tasks` variable available inside the `task/list.html.twig` template.
- Inside that template you would write `{% for task in tasks %}` to repeat HTML once per task, printing each with `{{ task.title }}` via dot notation.
- An `{% if task.done %}` check inside the loop lets the template show a different label for completed tasks.
- Wrapping the loop with `{% if tasks is empty %}` (or using `{% else %}` inside the `for` block) handles the "no tasks yet" case gracefully.

## Common Mistakes

- Using PHP syntax inside templates, like `<?php echo $task; ?>` or `$task['title']`. Twig has its own syntax: `{{ task.title }}` with no `$` and no `echo`.
- Forgetting the closing `{% endfor %}` or `{% endif %}` tag, which produces a confusing "Unexpected end of template" error pointing at the last line.
- Trying to run database queries or complex calculations in the template. Prepare the data in the controller so the template only loops and prints.

## Best Practices

- Pass ready-to-display data to templates: format dates and compute labels in PHP, where you have proper tooling and tests, not in Twig.
- Use the `|default()` filter for optional values instead of letting templates crash on missing variables.
- Prefer `{% for task in tasks %}` with an `{% else %}` branch for empty lists — it keeps the "no results" message right next to the loop it belongs to.

## Exercise

The controller passes a `user` array with keys `name` and `isAdmin` to `profile/show.html.twig`. Write the Twig snippet that prints the user's name in an `<h1>` and, only when `isAdmin` is true, also shows a paragraph saying "Site administrator".

## Solution

Print the name with `{{ user.name }}` inside the heading, then wrap the extra paragraph in an `{% if user.isAdmin %} ... {% endif %}` block. Dot notation reads the array keys, `{{ }}` safely escapes the name, and the `{% if %}` block conditionally renders the admin note only for administrators.

## Mini Quiz

```yaml
- question: "How does a Twig template receive data from a controller?"
  options:
    - "It queries the database directly with SQL"
    - "The controller passes an array to $this->render(), whose keys become variable names in the template"
    - "Templates read $_GET variables automatically"
    - "Data is hard-coded into the template file"
  answer: 1
  explanation: "The second argument of render() is an array; each key becomes a variable of the same name that the template can print or loop over."
- question: "What does {{ task.title }} do when task is an array with a title key?"
  options:
    - "It deletes the title key from the array"
    - "It prints the value of the title key, with automatic escaping"
    - "It creates a new task in the database"
    - "It redirects the browser to a task page"
  answer: 1
  explanation: "Dot notation reads array keys (or object properties/getters), and {{ }} prints the result while escaping dangerous characters."
- question: "Which Twig snippet correctly lists tasks with an empty-state message?"
  options:
    - "{% for task in tasks %}{{ task.title }}{% endfor %}"
    - "{% for task in tasks %}{{ task.title }}{% else %}No tasks yet{% endfor %}"
    - "{{ for(task in tasks) { print(task.title) } }}"
    - "{% loop tasks %}{{ title }}{% endloop %}"
  answer: 1
  explanation: "A for block supports an {% else %} branch that renders only when the looped-over array is empty, keeping the fallback message next to the loop."
```
