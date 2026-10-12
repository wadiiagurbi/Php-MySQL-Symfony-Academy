---
slug: mail
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Sending Email with Mailer"
difficulty: intermediate
order: 3
estimatedMinutes: 11
tags: [symfony, mailer, email]
---

## Learning Objective

Learn how Symfony's **Mailer** component sends emails, how to build a message with the `Email` object, and how **transports** and configuration differ between development and production.

## Explanation

Symfony's **Mailer** component is the framework's unified API for sending email: your code builds a message object describing *what* to send, and a configured **transport** (the delivery mechanism — SMTP server, API-based sender, or a test catcher) handles *how* it leaves the app. This separation means application code never changes between environments; only the `MAILER_DSN` **environment variable** (a connection string like `smtp://localhost:1025` naming the transport, stored in `.env` files per environment) changes. In `dev`, the DSN typically points at a local catcher like Mailpit so no real email escapes; in `prod`, it holds the real provider credentials as secrets.

Messages are built with the `Email` class using a **fluent interface** (a style where each setter returns the object itself, allowing chained calls like `->from(...)->to(...)->subject(...)`). You set the sender, recipient, subject, plain-text body, and optionally an HTML body rendered from a Twig template for attractive formatting. Sending is a single call — `$mailer->send($email)` — on the injected `MailerInterface`, and Symfony hands the message to the transport synchronously (immediately, in the same request) unless you route it through the Messenger queue for background delivery.

Deliverability details matter in production: the `from()` address should use your own verified domain (sending "from" gmail.com via your server fails anti-spoofing checks), and every send should be wrapped in error handling or queued, because a slow or down mail server must never break the user's request.

## Why It Matters

Welcome emails, password resets, order confirmations, failure alerts — email is how apps reach users when they are not looking at the screen, and password-reset delivery failures are business-critical outages. Knowing the build-then-send pattern plus environment DSNs lets you develop safely (catcher locally, real provider in prod) and debug "users never got the email" via the profiler and logs instead of guessing.

## Example

When a new user registers, the app sends a welcome email with both plain-text and HTML bodies. Locally the message lands in the Mailpit inbox; in production the same code sends through the real provider.

## Code

```php
<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class WelcomeMailer
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendWelcome(string $recipientEmail): void
    {
        $email = (new Email())
            ->from('hello@taskapp.example')
            ->to($recipientEmail)
            ->subject('Welcome to the Task App!')
            ->text('Thanks for registering. Time to organize your tasks!')
            ->html('<h1>Welcome!</h1><p>Thanks for registering.</p>');

        $this->mailer->send($email);
    }
}
```

## Code Explanation

- `MailerInterface $mailer` is the framework-provided sending service, injected through the constructor with no manual wiring.
- `(new Email())->from(...)->to(...)->subject(...)` chains fluent setters to describe the message; each returns the same object for the next call.
- `text()` sets the plain-text fallback every email client can display; `html()` adds the rich version shown by modern clients.
- `$this->mailer->send($email)` delivers via the environment's transport — Mailpit locally, the real provider in production — with identical application code.
- The `from()` address uses the app's own domain, which production mail servers require to pass spam and spoofing checks.

## Common Mistakes

- Developing against the production DSN and sending real emails to test addresses (or worse, real users) during development — always verify `MAILER_DSN` points at a catcher locally.
- Providing only an HTML body with no `text()` fallback. Some clients and spam filters penalize HTML-only mail; the plain-text part takes seconds to add.
- Sending synchronously inside the request for bulk or slow operations. A mail-server hiccup then delays or breaks the user's page — queue non-urgent mail with Messenger instead.

## Best Practices

- Keep `MAILER_DSN` per environment (catcher in dev/test, real provider credentials as secrets in prod) and never commit production credentials to git.
- Send from your own verified domain, include both `text()` and `html()` bodies, and render the HTML with a Twig template for maintainable styling.
- Queue bulk, newsletter, or retry-worthy mail via Messenger; reserve synchronous `send()` for single transactional emails where immediate failure feedback is useful.

## Exercise

A password-reset flow must email a reset link to the user without ever sending real mail during development. Describe where the reset link goes in the `Email` object, and how you guarantee dev safety.

## Solution

Put the link in both `text()` (full URL, since plain text has no anchors) and `html()` (a clickable link) bodies of the reset `Email`, sent to the account's address. Dev safety comes from `MAILER_DSN` pointing at a local catcher (Mailpit) in `.env`/`.env.dev`, so the identical code path delivers to the test inbox instead of the real internet.

## Mini Quiz

```yaml
- question: "What is the Mailer component's transport, and how is it chosen?"
  options:
    - "The email template file, chosen by the controller name"
    - "The delivery mechanism (SMTP, API sender, test catcher), selected by the MAILER_DSN environment variable per environment"
    - "The database table where emails are stored forever"
    - "The Twig filter that formats email subjects"
  answer: 1
  explanation: "Code builds the message identically everywhere; the DSN string decides how it is delivered, keeping dev safe (catcher) and prod real (provider)."
- question: "Why should emails include both text() and html() bodies?"
  options:
    - "Symfony throws an exception if either is missing"
    - "Plain text guarantees readability in every client and satisfies spam filters, while HTML provides rich formatting where supported"
    - "The two bodies are sent to two different recipients"
    - "HTML bodies cannot contain links"
  answer: 1
  explanation: "Dual bodies cover all clients gracefully: minimal readers show the text part, modern ones render HTML, and filters trust the combination more."
- question: "Why should bulk email go through the Messenger queue instead of synchronous send()?"
  options:
    - "Synchronous sending is forbidden by PHP"
    - "A slow or failing mail server would delay or break the user's request; background delivery isolates that risk"
    - "Queued emails skip spam filters automatically"
    - "Messenger writes emails faster to the database"
  answer: 1
  explanation: "Synchronous delivery couples request latency to mail-server health; queuing moves that work out of the request cycle with retries built in."
```
