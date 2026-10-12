---
slug: roles-and-authorization
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: security
chapterTitle: "Chapter 10 — Security & Authentication"
chapterOrder: 10
title: "Roles and Authorization"
difficulty: intermediate
order: 4
estimatedMinutes: 11
tags: [symfony, security, roles, authorization]
---

## Learning Objective

Learn how **roles** express what a user may do, how to check them with `isGranted()` and `#[IsGranted]`, and how **voters** answer object-level questions like "may this user edit *this* task?"

## Explanation

A **role** is a simple uppercase string such as `ROLE_USER` or `ROLE_ADMIN` stored on the user object that describes which broad category the account belongs to. **Authorization** is the decision, made *after* login, of whether the current user may perform a requested action — and roles are its simplest input. Every logged-in user implicitly holds `ROLE_USER`; administrators additionally hold `ROLE_ADMIN`. Symfony's `User::getRoles()` method returns the list, and it should guarantee at least `ROLE_USER` for every account so access rules have a reliable baseline to check against.

There are two levels of checks. **Role checks** answer coarse questions ("is this visitor an admin?") and appear either in PHP via `$this->isGranted('ROLE_ADMIN')` (a controller helper returning true or false) or declaratively via the `#[IsGranted('ROLE_ADMIN')]` attribute, which denies access before the method body runs. **Object-level checks** answer fine-grained questions ("may Sophie edit task #42, which belongs to Marc?") that no static role can express. Those are handled by **voters**: small classes implementing `VoterInterface` (usually by extending Symfony's `Voter` base class) that receive an attribute name like `TASK_EDIT`, the specific object, and the current user, then vote to grant or deny. A controller calls `$this->denyAccessUnlessGranted('TASK_EDIT', $task)` and Symfony consults every voter that supports that attribute.

Denying access produces distinct HTTP statuses worth knowing: unauthenticated visitors get `401 Unauthorized` (or a redirect to login for HTML pages), while logged-in users lacking permission get `403 Forbidden`. Templates can mirror these rules with `{% if is_granted('ROLE_ADMIN') %}` to hide admin links — but only as a convenience on top of server-side enforcement, never instead of it.

## Why It Matters

"I can see other users' tasks by changing the ID in the URL" is a classic authorization failure that role checks alone cannot prevent — it needs a voter comparing the task's owner to the current user. Professional code reviews therefore look for *both* layers: roles guarding sections, voters guarding individual objects. Getting this right is what keeps a multi-user app's data separated.

## Example

Task deletion must be limited to the task's owner: a voter compares the logged-in user with the task's owner, and the controller denies everyone else before touching the database.

## Code

```php
<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TaskDeleteController extends AbstractController
{
    #[Route('/tasks/{id}/delete', name: 'app_tasks_delete', methods: ['POST'])]
    public function delete(Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('TASK_DELETE', $task);

        $em->remove($task);
        $em->flush();

        return $this->redirectToRoute('app_tasks');
    }
}
```

## Code Explanation

- `Task $task` uses Symfony's **param converter** (the mechanism that automatically loads an entity from the `{id}` route placeholder) so the method receives the actual task object or a 404.
- `$this->denyAccessUnlessGranted('TASK_DELETE', $task)` asks all voters: "may the current user delete this specific task?" — throwing a 403/401 exception on denial before any state changes.
- A `TaskVoter` class (elsewhere in `src/Security/`) implements the actual rule, typically granting when `$task->getOwner() === $user` or the user has `ROLE_ADMIN`.
- `$em->remove($task)` plus `flush()` executes the SQL `DELETE` only after authorization has passed — order matters: check first, mutate second.
- Using POST (not GET) for deletion follows HTTP semantics so crawlers and cached links can never trigger destructive side effects.

## Common Mistakes

- Checking only `ROLE_USER` for object actions, so any logged-in user can edit or delete anyone else's tasks by guessing IDs.
- Hiding buttons in Twig without server-side checks. Hidden links are still callable directly — authorization must run in the controller or voter on every request.
- Mutating data *before* the authorization check (deleting, then checking). Always deny-or-proceed first; side effects must never precede permission verification.

## Best Practices

- Guard sections with roles (`ROLE_ADMIN` for admin areas) and individual objects with voters (`TASK_EDIT`, `TASK_DELETE`) — use both layers together.
- Name voter attributes after the domain action (`TASK_DELETE`) rather than reusing role names, so intent stays readable in controllers.
- Mirror server-side rules in Twig with `is_granted()` purely to hide irrelevant buttons, keeping the controller check as the real enforcement.

## Exercise

Only the owner of a task (or an admin) may view its private notes at `/tasks/{id}/notes`. Name the voter attribute you would define, state where the check goes, and describe the voter's grant rule in one sentence.

## Solution

Define a `TASK_VIEW_NOTES` attribute and call `$this->denyAccessUnlessGranted('TASK_VIEW_NOTES', $task)` at the top of the controller method, before loading or rendering anything sensitive. The voter's rule grants access when the current user is the task's owner or holds `ROLE_ADMIN`, denying everyone else with a 403.

## Mini Quiz

```yaml
- question: "What is a role in Symfony security?"
  options:
    - "A database migration that creates the users table"
    - "A string label like ROLE_ADMIN on the user object describing which category the account belongs to"
    - "A Twig template used for the login page"
    - "A type of HTTP response header"
  answer: 1
  explanation: "Roles are simple strings returned by getRoles() that coarse-grained checks (isGranted, IsGranted, access_control) compare against."
- question: "When do you need a voter instead of a plain role check?"
  options:
    - "When checking whether the user is logged in at all"
    - "When permission depends on the specific object, e.g. whether this user owns this particular task"
    - "When rendering any Twig template"
    - "When running database migrations"
  answer: 1
  explanation: "Roles answer who-you-are questions; voters answer may-you-touch-this-object questions by inspecting both the user and the domain object."
- question: "What does denyAccessUnlessGranted('TASK_DELETE', $task) do when access is refused?"
  options:
    - "It silently logs the user out"
    - "It throws an access-denied exception (401 for guests, 403 for logged-in users) before any further code runs"
    - "It deletes the task anyway but hides the result"
    - "It sends the user an email explaining the denial"
  answer: 1
  explanation: "The helper aborts the request with the proper HTTP status before reaching the mutation code, so denied users never trigger side effects."
```
