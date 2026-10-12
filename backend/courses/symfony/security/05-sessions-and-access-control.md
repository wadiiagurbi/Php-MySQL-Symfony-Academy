---
slug: sessions-and-access-control
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: security
chapterTitle: "Chapter 10 — Security & Authentication"
chapterOrder: 10
title: "Sessions and Access Control"
difficulty: intermediate
order: 5
estimatedMinutes: 11
tags: [symfony, security, sessions, access-control]
---

## Learning Objective

Understand how **sessions** persist data (including login state) across requests, how to use flash messages, and how `access_control` rules in `security.yaml` enforce URL-level protection.

## Explanation

HTTP is **stateless**: each request arrives independently, with the server remembering nothing about previous ones. A **session** is Symfony's answer — a per-visitor storage bag kept on the server (in files, cache, or a database by default) and linked to the browser by a cookie containing a random session id. On every request, Symfony loads the matching bag, your code reads or writes values like `$session->set('timezone', 'Europe/Paris')`, and the bag is saved again at the end. Login state itself lives here: after successful authentication, the firewall stores the user identity in the session, which is why `$this->getUser()` works on the *next* page without logging in again.

A beloved session feature is **flash messages**: one-time notifications (like "Task created!") that survive exactly one redirect. The pattern is `$this->addFlash('success', 'Task created!')` in the controller before redirecting, then `{% for message in app.flashes('success') %}` in the template to display and simultaneously consume them. They solve the redirect problem elegantly: the message travels with the user to the next page, then disappears so a refresh does not show it twice.

URL-level enforcement comes from the `access_control:` list in `security.yaml` — ordered "pattern → requirement" entries such as `- { path: ^/admin, roles: ROLE_ADMIN }`. Symfony evaluates them top to bottom and applies the first match: anonymous users hitting a protected path are redirected to login (or get `401 Unauthorized` for APIs), while logged-in users lacking the role get `403 Forbidden`. Because the first match wins, entries run from most specific (`^/admin/users`) to most general (`^/`), and `debug:firewall` plus `router:match` commands verify what actually applies.

## Why It Matters

Sessions power login persistence, multi-step forms, shopping carts, and every "saved!" banner your users see — misunderstanding their one-redirect flash lifecycle or their cookie linkage is behind classic bugs like "message shows twice" or "logged out on every refresh." Access-control fluency matters equally: it is the single choke point reviewers audit to confirm that protected sections are truly protected.

## Example

After creating a task, the app redirects to the task list and shows a one-time "Task created!" confirmation banner. The same app locks every `/account` URL to logged-in users via one access-control rule.

## Code

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FlashDemoController extends AbstractController
{
    #[Route('/tasks/create-demo', name: 'app_tasks_create_demo', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $title = trim((string) $request->request->get('title', ''));

        if ('' === $title) {
            $this->addFlash('error', 'The title cannot be empty.');

            return $this->redirectToRoute('app_tasks');
        }

        $this->addFlash('success', sprintf('Task "%s" created!', $title));

        return $this->redirectToRoute('app_tasks');
    }
}
```

## Code Explanation

- `$this->addFlash('success', ...)` stores a one-time message in the session under the `success` category; it survives the redirect and is consumed on first display.
- The `error` flash on the empty-title path shows the same mechanism for failures — the template renders each category with its own styling.
- `redirectToRoute('app_tasks')` sends the browser to the list page, where `{% for message in app.flashes('success') %}` prints and clears the message.
- Because flashes live in the session, they work across the redirect boundary where plain template variables would be lost.
- The matching `access_control` rule `- { path: ^/account, roles: ROLE_USER }` in `security.yaml` independently guarantees account pages require login.

## Common Mistakes

- Expecting flash messages to persist across multiple page views. They are consumed on first read — refreshing after display correctly shows nothing, which is by design, not a bug.
- Storing large objects or Doctrine entities in the session. Sessions serialize their contents; store small scalar values or ids and reload entities fresh on the next request.
- Adding access-control rules in the wrong order (general `^/` before specific `^/admin`), so the specific rule never matches and the admin section inherits the weaker requirement.

## Best Practices

- Use flashes for exactly one thing: short post-redirect confirmations ("Saved!", "Deleted!") categorized as `success` or `error`.
- Keep session payloads tiny (ids, strings, flags) and reload full objects from the database per request to avoid stale or unserializable data.
- List `access_control` from most specific to most general, and verify with `php bin/console debug:firewall` and `router:match /your/path` after every change.

## Exercise

After deleting a task, redirect to the task list showing "Task deleted." exactly once. Write the two controller lines (flash + redirect) and the Twig loop that displays and consumes the message.

## Solution

In the controller call `$this->addFlash('success', 'Task deleted.');` then `return $this->redirectToRoute('app_tasks');`. In the list template write `{% for message in app.flashes('success') %}<p>{{ message }}</p>{% endfor %}` — the loop prints each pending message once and clears it, so a refresh shows a clean page.

## Mini Quiz

```yaml
- question: "What is a session in Symfony?"
  options:
    - "A database migration that runs once per deploy"
    - "A per-visitor server-side storage bag linked to the browser by a cookie, letting data (including login state) survive across stateless requests"
    - "A Twig block that renders the page footer"
    - "A log file recording every SQL query"
  answer: 1
  explanation: "Because HTTP is stateless, the session bag plus its cookie give each visitor continuity: values set on one request are readable on the next."
- question: "What makes flash messages special compared to normal session values?"
  options:
    - "They are stored in the database permanently"
    - "They survive exactly one redirect and are consumed on first display, ideal for post-action confirmations"
    - "They can only contain error messages"
    - "They are visible only to administrators"
  answer: 1
  explanation: "Flashes travel across the redirect boundary to the next page and then disappear, so confirmations show once and never repeat on refresh."
- question: "How are access_control rules in security.yaml evaluated?"
  options:
    - "All matching rules must pass simultaneously"
    - "Top to bottom with the first match winning, so specific patterns must come before general ones"
    - "In random order on every request"
    - "Only the last rule in the file is ever applied"
  answer: 1
  explanation: "Evaluation stops at the first matching pattern, which is why ordering from most specific to most general is essential for correct enforcement."
```
