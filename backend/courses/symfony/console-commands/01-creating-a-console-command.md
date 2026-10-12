---
slug: creating-a-console-command
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: console-commands
chapterTitle: "Chapter 12 — Console Commands"
chapterOrder: 12
title: "Creating a Console Command"
difficulty: advanced
order: 1
estimatedMinutes: 10
tags: [symfony, console, cli]
---

## Learning Objective

Understand what a console command is, why an application needs code that runs outside of an HTTP request, and how to create and run a basic Symfony console command.

## Explanation

Every piece of Symfony code you have written so far has run in response to an HTTP request: a browser or API client sends a request, a controller handles it, a response goes back, and the PHP process ends. But plenty of real work an application needs to do has no browser or API client waiting on the other end at all: importing a CSV of products overnight, sending reminder emails to users with overdue tasks, cleaning up old expired records, or recalculating cached statistics. None of these have an HTTP request behind them — they need to be triggered directly, from the server itself, whenever you (or a scheduler) decide to run them.

A **console command** is exactly that: a PHP script you run from the terminal using `php bin/console <command-name>`, instead of triggering it through a URL. You already use console commands constantly without necessarily thinking of them that way — `php bin/console cache:clear`, `php bin/console doctrine:migrations:migrate`, and `php bin/console make:controller` are all commands built into Symfony or its bundles. This lesson is about writing your *own*.

Symfony's Console component (which powers `bin/console`) gives you a `Command` base class with a well-defined structure: a **name** (how you invoke it, e.g. `app:tasks:archive-completed`), a `configure()` method describing what the command accepts, and an `execute()` method containing the actual logic, which returns a status code (`Command::SUCCESS`, `Command::FAILURE`, or `Command::INVALID`) telling the shell whether the command succeeded — exactly the way an HTTP response uses a status code to tell a client whether a request succeeded.

Why does this matter beyond convenience? Because a console command gets you three things a controller action fundamentally cannot:

1. **No HTTP request/response cycle required.** The command can run for minutes or hours if needed (a controller action serving a web request should never do that — the client would time out).
2. **Direct terminal access, output, and exit codes**, which is exactly what tools like cron (a Unix scheduler that runs commands at fixed times) or a CI/CD pipeline expect to interact with.
3. **Full access to your application's services**, exactly like a controller does — a command can inject `TaskService`, `TaskRepository`, `EntityManagerInterface`, or any other service from the container, so it is not a separate, disconnected piece of code; it reuses the exact same business logic your API uses.

Commands are registered automatically in Symfony 6.4 as long as they live in `src/Command/` and use the `#[AsCommand]` attribute — no manual registration file needed, the same "convention over configuration" pattern you have already seen with controllers and event listeners.

## Why It Matters

Every non-trivial application eventually needs code that runs independently of a web request — imports, scheduled maintenance, batch processing, one-off data fixes performed by an engineer investigating a production issue. Without console commands, developers are tempted to hack this in by exposing a "secret" HTTP endpoint that performs the task when visited (a real anti-pattern still seen in production systems), which is slower, harder to secure, and impossible to run for longer than a request timeout allows. Console commands are the professional, safe, and Symfony-idiomatic way to do this work, and knowing how to write one is a basic expectation for any backend Symfony developer.

## Example

Imagine tasks that have been marked `DONE` for more than 90 days should be automatically archived (moved to an `archivedAt` state) so they stop cluttering the active task list. There is no user action that naturally triggers this — nobody clicks a button to say "check for old done tasks right now." A console command, `app:tasks:archive-completed`, run daily, is exactly the right tool: an engineer (or later, a cron job) runs `php bin/console app:tasks:archive-completed` and it does the job.

## Code

```php
<?php

namespace App\Command;

use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:archive-completed',
    description: 'Archives tasks that have been DONE for more than 90 days.',
)]
class ArchiveCompletedTasksCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tasks = $this->taskRepository->findDoneOlderThan(new \DateTimeImmutable('-90 days'));

        if ([] === $tasks) {
            $io->success('No tasks needed archiving.');

            return Command::SUCCESS;
        }

        foreach ($tasks as $task) {
            $task->archive();
        }

        $this->entityManager->flush();

        $io->success(sprintf('Archived %d task(s).', count($tasks)));

        return Command::SUCCESS;
    }
}
```

## Code Explanation

- `#[AsCommand(name: 'app:tasks:archive-completed', description: '...')]` registers the command and its name automatically — no separate configuration file needed, similar to how `#[Route]` registers a controller action.
- `class ArchiveCompletedTasksCommand extends Command` — extending Symfony's `Command` base class gives this class the machinery `bin/console` needs to discover, describe, and run it.
- The constructor injects `TaskRepository` and `EntityManagerInterface` exactly like a controller or service would; `parent::__construct()` must be called so the base `Command` class finishes its own setup.
- `protected function execute(InputInterface $input, OutputInterface $output): int` is where the actual logic lives; `$input` gives access to arguments/options (covered in the next lesson), and `$output` (wrapped here in a friendlier `SymfonyStyle`) is how the command writes to the terminal.
- `$io->success('...')` prints a nicely formatted green success message — `SymfonyStyle` provides several such helpers (`error()`, `warning()`, `table()`) so you do not have to format terminal output by hand.
- `return Command::SUCCESS;` returns an exit code of `0` to the shell, telling anything that invoked this command (a human, a script, or later a cron job) that it completed without errors; `Command::FAILURE` (which equals `1`) would signal the opposite.

## Common Mistakes

- Forgetting to call `parent::__construct()` in the constructor when a command needs injected services — Symfony's `Command` base class relies on its own constructor running, and skipping it causes cryptic errors when the command is registered.
- Putting business logic (deciding what "archiving" actually means for a task) directly inside `execute()`, instead of delegating to a Service — this makes the same logic impossible to reuse from a controller or another command, and hard to unit test.
- Not returning a meaningful exit code, or always returning `Command::SUCCESS` even when something went wrong — this silently breaks any cron job or CI pipeline relying on the exit code to detect failure.
- Writing raw `echo` statements instead of using `$output` or `SymfonyStyle`, which loses features like verbosity levels (`-v`, `-vv`) and consistent formatting that other commands share.

## Best Practices

- Give commands a namespaced name (`app:tasks:archive-completed` rather than just `archive`) so `php bin/console list` stays organized as the number of commands grows.
- Keep `execute()` thin: parse input, call a Service method that does the real work, and report the result — the same Controller → Service separation you already use for HTTP endpoints applies here too.
- Return `Command::FAILURE` and print a clear error message via `$io->error()` whenever the command cannot complete successfully, so automated tooling (and humans watching logs) can detect the failure.
- Test commands the same way you test controllers — Symfony provides a `CommandTester` class specifically for running a command in a test and asserting on its output and exit code.

## Exercise

Write the skeleton (class declaration, attribute, constructor, and `execute()` signature only — no need to implement the full logic) of a console command named `app:tasks:send-due-reminders` that will eventually email users about tasks due within 24 hours. It should inject a hypothetical `TaskRepository` and a hypothetical `MailerInterface`.

## Solution

```php
<?php

namespace App\Command;

use App\Repository\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:tasks:send-due-reminders',
    description: 'Emails users about tasks due within the next 24 hours.',
)]
class SendDueRemindersCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Logic to find due tasks and send emails would go here.
        return Command::SUCCESS;
    }
}
```

The command follows the same shape as `ArchiveCompletedTasksCommand`: an `#[AsCommand]` attribute for registration, injected services via the constructor with `parent::__construct()` called, and an `execute()` method returning a `Command::*` exit code.

## Mini Quiz

```yaml
- question: "What is a console command in Symfony?"
  options:
    - "A special type of HTTP route that only accepts POST requests"
    - "A PHP script run via php bin/console, used for tasks that don't originate from an HTTP request, like imports or scheduled maintenance"
    - "A Twig template used only in the terminal"
    - "A Doctrine migration file"
  answer: 1
  explanation: "Console commands run outside the HTTP request/response cycle, invoked directly from the terminal, and are ideal for imports, scheduled jobs, and administrative tasks."

- question: "Why must parent::__construct() be called when a Command class has its own constructor?"
  options:
    - "It is optional and only affects performance"
    - "It sets the command's terminal color scheme"
    - "The base Command class relies on its own constructor logic running for the command to register and function correctly"
    - "It is required only for commands with arguments"
  answer: 2
  explanation: "Symfony's Command base class performs its own setup in its constructor; skipping parent::__construct() when overriding the constructor breaks that setup."

- question: "What does returning Command::FAILURE from execute() communicate?"
  options:
    - "Nothing; the return value is ignored by bin/console"
    - "A non-zero exit code to the shell, signaling to scripts, cron jobs, or CI pipelines that the command did not complete successfully"
    - "That the command should automatically retry"
    - "That the command's services failed to inject"
  answer: 1
  explanation: "The integer returned from execute() becomes the process exit code; non-zero values (like Command::FAILURE) tell anything invoking the command that something went wrong."
```
