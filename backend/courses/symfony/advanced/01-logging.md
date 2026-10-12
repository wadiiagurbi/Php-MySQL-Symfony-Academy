---
slug: logging
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Logging with Monolog"
difficulty: intermediate
order: 1
estimatedMinutes: 10
tags: [symfony, logging, monolog, debugging]
---

## Learning Objective

Understand what application **logging** is, how Symfony's **Monolog** integration routes messages into channels and files, and how to log effectively from your own services.

## Explanation

**Logging** means writing timestamped messages about what your application is doing — "payment failed for order 42," "import finished: 900 rows" — into files (or external services) you can inspect later. Unlike `dump()` debugging, which helps one developer for five minutes, logs persist for weeks and answer production questions like "what happened at 3 AM?" Symfony integrates the **Monolog** library (the standard PHP logging package) so every part of your app, including the framework itself, writes through one consistent system.

Monolog organizes output around two ideas. A **channel** is a named category of logs — your app code writes to the `app` channel, Doctrine's queries go to `doctrine`, security events to `security` — so you can filter noise by viewing one channel at a time. A **handler** decides where a channel's messages go: the default setup streams `dev` output to the console and `var/log/dev.log` file, while `prod` writes structured lines to `var/log/prod.log` with higher severity thresholds. **Log levels** (`debug`, `info`, `notice`, `warning`, `error`, `critical`) rank each message's importance, and each handler ignores anything below its configured minimum — which is why chatty `debug` calls never pollute production files.

From your code, logging is one injected service away: type-hint `LoggerInterface` and call `$logger->info('Import finished', ['rows' => 900])`. The second **context** array (structured key-value data attached to the message) is what makes logs searchable later — always prefer context over concatenating values into the string.

## Why It Matters

When production breaks at midnight, logs are often the *only* witness: no debugger, no `dump()`, just `var/log/prod.log` and your error tracker. Teams that log meaningful events with context diagnose incidents in minutes; teams that log nothing (or log everything at `debug` level with no context) fly blind. Good logging is also an audit asset — "who approved this refund?" should be answerable from logs alone.

## Example

A nightly task-import command should record when it starts, how many rows it processed, and any rows it skipped — at appropriate levels with searchable context, so operators can verify each run from the log file.

## Code

```php
<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class TaskImporter
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function import(array $rows): int
    {
        $this->logger->info('Task import started', ['rows' => count($rows)]);

        $imported = 0;
        foreach ($rows as $index => $row) {
            if (!isset($row['title']) || '' === trim($row['title'])) {
                $this->logger->warning('Skipping row without title', ['row' => $index]);

                continue;
            }
            ++$imported;
        }

        $this->logger->info('Task import finished', ['imported' => $imported]);

        return $imported;
    }
}
```

## Code Explanation

- `LoggerInterface $logger` is injected by the container and preconfigured to the `app` channel — the standard **PSR-3** (the PHP community's shared logging interface, so this code works with any compliant logger) type-hint.
- `info()` marks normal lifecycle events (start/finish); `warning()` flags skipped rows that merit attention without failing the run.
- The context arrays (`['rows' => ...]`, `['row' => $index]`) attach searchable structured data instead of burying values inside the message string.
- No handler or file configuration appears here — where these lines land (console in dev, `var/log/prod.log` in prod) is decided by Monolog's environment config, not by application code.
- The method returns the imported count so callers (and tests) can assert on behavior, while the logs serve human operators.

## Common Mistakes

- Logging inside tight loops at `info` level or higher ("processing row 1... processing row 2..."). Thousands of lines drown the signal — log summaries instead, and keep per-item detail at `debug`.
- Interpolating values into the message (`"Row $i skipped"`) instead of using context (`['row' => $i]`). Concatenated strings cannot be filtered or aggregated by log tools.
- Logging sensitive data: passwords, tokens, full emails. Logs are read by many people and shipped to third parties — treat them as semi-public.

## Best Practices

- Log lifecycle events (started/finished) plus anomalies (skipped, retried, failed) with context arrays — the minimum needed to reconstruct what happened.
- Match the level to the meaning: `debug` for developer detail, `info` for normal milestones, `warning` for recoverable problems, `error`/`critical` for failures needing action.
- Tail logs with `tail -f var/log/dev.log` during development and check `var/log/prod.log` (or your log aggregator) as the first step of any production investigation.

## Exercise

Your payment webhook handler must record (1) each received webhook at low verbosity and (2) every failed charge prominently. State which level each gets, and write the two logger calls with context for `orderId` 42.

## Solution

Receipt is routine volume: `$this->logger->debug('Webhook received', ['orderId' => 42]);` — filtered out in production by handler thresholds. A failed charge needs action: `$this->logger->error('Charge failed', ['orderId' => 42]);`. Levels separate noise from signal, and the `orderId` context makes both lines searchable per order.

## Mini Quiz

```yaml
- question: "What are Monolog channels and handlers?"
  options:
    - "Channels are database tables and handlers are their columns"
    - "Channels are named log categories (app, doctrine, security) and handlers decide where each channel's messages are written"
    - "Channels are email inboxes and handlers are spam filters"
    - "Channels are Twig blocks and handlers render them"
  answer: 1
  explanation: "Channels categorize who is speaking; handlers route messages to destinations (files, console, external services) honoring per-environment level thresholds."
- question: "Why pass a context array like ['orderId' => 42] instead of writing 'Order 42 failed'?"
  options:
    - "Context arrays make the log file smaller"
    - "Structured context is filterable and searchable by log tools, while values buried in strings are not"
    - "Monolog refuses messages without context"
    - "Context arrays render as HTML in the browser"
  answer: 1
  explanation: "Key-value context lets operators and aggregators query all entries for one order, which free-text interpolation defeats."
- question: "Which level fits 'import skipped 3 malformed rows but otherwise succeeded'?"
  options:
    - "debug, so it never appears anywhere"
    - "critical, paging the on-call engineer immediately"
    - "warning, since something recoverable deserves attention without declaring failure"
    - "There is no appropriate level for partial success"
  answer: 2
  explanation: "warning marks recoverable anomalies worth noticing; the run succeeded overall, so error/critical would overstate it, and debug would hide it from operators."
```
