---
slug: arguments-options-and-services-in-commands
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: console-commands
chapterTitle: "Chapter 12 — Console Commands"
chapterOrder: 12
title: "Arguments, Options, and Services in Commands"
difficulty: advanced
order: 2
estimatedMinutes: 12
tags: [symfony, console, cli, cron]
---

## Learning Objective

Learn how to accept input into a console command through arguments and options, how to reuse application services inside a command, and how commands fit into scheduled/background execution.

## Explanation

The previous lesson's command always did the same thing every time it ran: archive every `DONE` task older than 90 days, with no way to change that number or target a specific user without editing the code. Real commands almost always need to accept input from whoever is running them, the same way a controller reads data from the `Request`. The Console component gives you two mechanisms for this: **arguments** and **options**.

An **argument** is a required (or optionally required) positional value, given right after the command name: `php bin/console app:tasks:archive-completed 30` — here `30` is an argument overriding the default "90 days" threshold. Arguments are typically used for the one or two pieces of input a command cannot reasonably run without.

An **option** is a named, optional flag, given with two dashes: `php bin/console app:tasks:archive-completed --dry-run` or `php bin/console app:tasks:notify-user --user=42`. Options are for anything optional — toggles, filters, or values that have a sensible default. `--dry-run` is an especially common and important option pattern: it lets the command report *what it would do* without actually doing it, which is invaluable for safely testing a command against production data before letting it make real changes.

Both are declared in the command's `configure()` method, using `addArgument()` and `addOption()`, and read back inside `execute()` via `$input->getArgument('name')` and `$input->getOption('name')`.

The second half of this lesson is about **reusing services inside a command**. A command class is registered in Symfony's service container exactly like a controller or another service, which means its constructor can type-hint any service you need — `TaskRepository`, `TaskService`, a custom `MailerInterface` wrapper, anything. This is important: a command should generally **not** reimplement business logic that already exists in a Service class used by your controllers. Instead, it should parse its input, then delegate to the same Service methods your API already calls, exactly the way `TaskController::create()` delegates to `TaskService::create()`. This keeps your business rules in one place regardless of whether they were triggered by an HTTP request or a terminal command.

Finally, a concept worth understanding even though implementing it is outside this lesson's scope: **scheduling**. A console command by itself does not run automatically — you (or another process) must invoke it. On Linux servers, the traditional tool for this is **cron**, a system scheduler that runs a configured command at fixed times (e.g., "every day at 2 AM, run `php bin/console app:tasks:archive-completed`"). A cron entry is just an operating-system-level line saying "at this schedule, run this shell command," and that shell command is almost always `php /path/to/project/bin/console <your command>`. This is precisely why console commands matter for background/scheduled work: cron (or a similar scheduler, or Symfony's own Scheduler component in newer versions) needs *something* invokable from the shell with a clear exit code, and a console command is exactly that — cron does not know how to "call a controller," but it knows how to run a shell command on a timer.

## Why It Matters

Hardcoded values in a command force you to edit and redeploy code every time the business wants a different threshold, target, or mode — a real cost in a production system where deployments should be deliberate, reviewed events, not the only way to change "how many days" a report covers. Accepting arguments and options turns a one-off script into a reusable, safely testable tool. Meanwhile, understanding how commands connect to scheduling (cron or similar) explains *why* Symfony invests so heavily in the console component at all: virtually every production Symfony application has a crontab (or equivalent) file listing several `bin/console` invocations that keep the system healthy without any human clicking a button.

## Example

Imagine you run a nightly cleanup with an argument and an option: the days threshold comes from the argument and the safety toggle from the option.

## Code

```php
<?php

namespace App\Command;

use App\Service\TaskService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:archive-completed',
    description: 'Archives tasks that have been DONE for more than N days.',
)]
class ArchiveCompletedTasksCommand extends Command
{
    public function __construct(private readonly TaskService $taskService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Archive tasks done for at least this many days', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be archived, without saving changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');
        $dryRun = (bool) $input->getOption('dry-run');

        $count = $this->taskService->archiveCompletedOlderThan($days, dryRun: $dryRun);

        $io->success(sprintf(
            '%s %d task(s) older than %d days.',
            $dryRun ? 'Would archive' : 'Archived',
            $count,
            $days,
        ));

        return Command::SUCCESS;
    }
}
```

## Code Explanation

- `->addArgument('days', InputArgument::OPTIONAL, 'description', 90)` declares an optional positional argument named `days` with a default of `90`, so `php bin/console app:tasks:archive-completed` (no argument) and `php bin/console app:tasks:archive-completed 30` both work.
- `->addOption('dry-run', null, InputOption::VALUE_NONE, 'description')` declares a boolean flag option; `InputOption::VALUE_NONE` means the option takes no value — it is either present (`--dry-run`) or absent, unlike an option such as `--user=42` which would use `InputOption::VALUE_REQUIRED`.
- `$input->getArgument('days')` and `$input->getOption('dry-run')` read back whatever the user passed (or the configured default) — both always return strings/booleans that you should cast to the type your logic needs, as done here with `(int)` and `(bool)`.
- `$this->taskService->archiveCompletedOlderThan($days, dryRun: $dryRun)` delegates the actual business logic to a Service method — the exact same method a controller or another command could call — rather than duplicating "what does archiving mean" logic inside the command itself.
- The named argument `dryRun: $dryRun` uses PHP 8's named arguments syntax, making the call self-documenting at the call site.

## Common Mistakes

- Implementing the "what does this command actually do" logic entirely inside `execute()`, instead of delegating to a Service — this means an API endpoint that needs the same behavior (e.g., an admin "archive now" button) cannot reuse it without copy-pasting.
- Forgetting to give an option like `--dry-run` when writing any command that deletes, archives, or otherwise mutates data in bulk — without a safe preview mode, the first real run against production data is also the first real test of the logic.
- Using `InputArgument::REQUIRED` for something that has a perfectly reasonable default, forcing every invocation to repeat a value that rarely changes.
- Assuming a command "runs itself" once written — a command is only ever executed when something (a human, a cron entry, a CI job) explicitly invokes it; writing the command does not schedule it.

## Best Practices

- Always add a `--dry-run` style option to any command that performs destructive or hard-to-reverse bulk operations, and test with it before ever running for real.
- Keep `configure()` focused purely on describing input (names, types, defaults, help text) — no logic belongs there; it runs once when the command is registered, not per-invocation.
- When a command will eventually be scheduled via cron, design it to be safely re-run (idempotent) — e.g., archiving tasks that are already archived should be a harmless no-op, since a cron job might occasionally run twice or overlap.
- Log meaningful output (via `$io` or a real logger) from scheduled commands, since nobody is watching the terminal in real time when cron runs them at 2 AM — logs are often the only record of what happened.

## Exercise

Extend the `ArchiveCompletedTasksCommand` concept: add a `--category=` option (using `InputOption::VALUE_REQUIRED`) that, when provided, restricts archiving to tasks in that category only. Write just the `configure()` method changes and the corresponding line in `execute()` that reads the new option.

## Solution

```php
protected function configure(): void
{
    $this
        ->addArgument('days', InputArgument::OPTIONAL, 'Archive tasks done for at least this many days', 90)
        ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be archived, without saving changes')
        ->addOption('category', null, InputOption::VALUE_REQUIRED, 'Only archive tasks in this category');
}
```

```php
// inside execute():
$category = $input->getOption('category'); // string|null
```

`InputOption::VALUE_REQUIRED` means that if `--category` is passed at all, a value must follow it (`--category=Backend`); if the option is omitted entirely, `getOption('category')` simply returns `null`, and the command's logic (in the Service it delegates to) should treat `null` as "no category filter."

## Mini Quiz

```yaml
- question: "What is the difference between a console command argument and an option?"
  options:
    - "Arguments are always optional; options are always required"
    - "An argument is a required-by-position value given right after the command name; an option is a named, typically optional flag given with two dashes"
    - "There is no functional difference, only stylistic"
    - "Options can only be booleans, arguments can only be strings"
  answer: 1
  explanation: "Arguments are positional values following the command name, while options are named flags (like --dry-run) that are typically optional and can carry their own values."

- question: "Why is a --dry-run option valuable on a command that archives or deletes data in bulk?"
  options:
    - "It makes the command run faster"
    - "It lets you preview what the command would do without making real changes, which is critical before running destructive logic against real data"
    - "It is required by Symfony for all commands"
    - "It disables logging"
  answer: 1
  explanation: "A dry-run mode lets you safely verify a command's behavior against real data before allowing it to make irreversible changes."

- question: "How does a scheduler like cron typically trigger a Symfony console command?"
  options:
    - "Cron calls the controller class directly via PHP's internal API"
    - "Cron has no way to interact with Symfony applications"
    - "Cron runs a configured shell command at a fixed schedule, which is usually 'php bin/console <command-name>'"
    - "Cron requires a dedicated Symfony bundle to run any command"
  answer: 2
  explanation: "Cron operates at the operating-system level, running shell commands on a schedule; invoking a console command via 'php bin/console ...' is exactly the kind of shell command cron is designed to run."

- question: "Why should a command intended for scheduled/background use delegate its real logic to a Service class rather than implementing it inline in execute()?"
  options:
    - "Because Service classes run faster than Command classes"
    - "So the same business logic can be reused from controllers, other commands, or tests, instead of being duplicated"
    - "Because Symfony does not allow business logic inside execute()"
    - "It is not actually recommended; inline logic in execute() is preferred"
  answer: 1
  explanation: "Delegating to a Service keeps business logic in one reusable, testable place, whether it's triggered by an HTTP request, a console command, or a test."
```
