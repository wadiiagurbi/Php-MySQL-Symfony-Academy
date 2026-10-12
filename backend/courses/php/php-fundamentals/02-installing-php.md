---
slug: installing-php
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Installing PHP"
difficulty: beginner
order: 2
estimatedMinutes: 9
tags: [php, setup, installation, command-line]
---

## Learning Objective

By the end of this lesson, you will understand what "installing PHP" actually means,
what your main options are as a beginner, and how to verify that PHP is correctly
installed and working on your computer using the command line.

## Explanation

In the previous lesson, you learned that PHP code needs a **PHP interpreter** to run
it — a program that reads your `.php` file and carries out its instructions. Before
you can run any PHP code yourself, that interpreter needs to exist on your computer.
Getting it there is what people mean by "installing PHP."

There are a few common ways beginners get PHP running on their machine:

1. **Install PHP directly.** Each operating system (Windows, macOS, Linux) has its own
   way to install the PHP interpreter as a standalone program. Once installed, you can
   type `php` into a terminal (also called a **command line** — a text-based way to
   give your computer instructions by typing commands instead of clicking) and it will
   run.
2. **Use a bundled local server stack.** Tools like **XAMPP**, **Laragon** (Windows),
   or **MAMP** (Mac) package PHP together with a web server (Apache or nginx) and a
   database (MySQL) into one easy installer. These are popular with beginners because
   one installer sets up almost everything a small PHP website needs at once.
3. **Use an online sandbox.** Websites exist where you can type PHP code into a browser
   text box and see the output immediately, with nothing installed on your computer at
   all. This is a fast way to experiment, though it's less realistic than working on
   your own machine.

For this course, any of these options works. What actually matters is that, at the
end of setup, you have a working `php` command you can type into a terminal. The rest
of this lesson focuses on that verification step, since the exact installer clicks
differ by operating system and change over time — but checking that it worked is
always the same.

A **terminal** (or command line) is simply a window where you type text commands and
press Enter to run them, instead of clicking icons. Every operating system has one:
Windows has "Command Prompt" or "PowerShell," macOS and Linux have "Terminal." You will
use it constantly throughout this course, so it's worth getting comfortable with it
now.

## Why It Matters

In real-world PHP development, almost nobody writes code by only clicking around a
browser sandbox. Professional developers run PHP from the command line constantly: to
start a local web server, to run automated tests, to execute one-off scripts (like
"import 10,000 rows into the database"), and to run the tools that frameworks like
Symfony depend on. Symfony itself, which this course builds toward, is managed through
a command-line tool.

Knowing how to check that PHP is installed and run a file from the terminal is a
foundational skill you will use in every single lesson from here on. If this step is
shaky, everything else becomes harder, so it's worth getting solid now, even though it
feels like "just setup."

## Example

Imagine you just finished installing PHP through one of the methods above. Before
writing any real code, you want a quick way to confirm "did that actually work?" The
standard way to check is to ask PHP to report its own version number — if it answers,
the interpreter is correctly installed and reachable from your terminal.

## Code

```text
php -v
```

## Code Explanation

- You type this directly into your terminal (not into a `.php` file — this is a
  command you give to your operating system, not PHP code).
- `php` is the name of the command that runs the PHP interpreter. If your terminal
  recognizes this word, it means PHP is installed and the operating system knows where
  to find it.
- `-v` is a **flag** — a short extra instruction added to a command to change its
  behavior. Here, `-v` stands for "version," so `php -v` means "run PHP, and just tell
  me your version number, don't run any file."
- If everything is set up correctly, you'll see output similar to this (the exact
  numbers will vary):

  ```text
  PHP 8.3.6 (cli) (built: ...)
  Copyright (c) The PHP Group
  Zend Engine v4.3.6, Copyright (c) Zend Technologies
  ```

- The important part is simply that a version number appeared instead of an error. If
  your terminal instead says something like `'php' is not recognized as an internal or
  external command` (Windows) or `command not found: php` (macOS/Linux), PHP is not yet
  correctly installed or your terminal doesn't know where to find it.
- Once `php -v` works, you can also run an actual file with `php filename.php` — for
  example, `php hello.php` tells the PHP interpreter "read the file named `hello.php`
  and execute its instructions." You'll do exactly this in the next lesson.

## Common Mistakes

- **Installing PHP but restarting the terminal too early, or not at all.** On some
  systems, a freshly opened terminal window needs to be reopened after installation
  before it recognizes the new `php` command. If `php -v` fails right after installing,
  try closing and reopening your terminal before assuming something is broken.
- **Confusing a local server stack's control panel with the terminal.** Tools like
  XAMPP have their own graphical "control panel" app for starting/stopping servers.
  That panel is not the same as your terminal, and clicking "Start" in it doesn't mean
  `php -v` will suddenly work in your terminal — you may still need to add PHP to your
  system's search path, or open the terminal that the tool provides specifically.
- **Assuming the online sandbox counts as "having PHP installed."** A browser-based
  sandbox is great for quick experiments, but it will not let you follow along with
  command-line-based lessons later in this course (like running Symfony's tools), so
  don't skip installing PHP locally if you plan to continue past the basics.

## Best Practices

- Prefer a bundled stack like XAMPP or Laragon if you're unsure which option to pick —
  they are designed to minimize setup friction and are widely documented online for
  every operating system.
- Always confirm success with `php -v` right after installing, before writing any
  code. It takes ten seconds and saves you from debugging "my code doesn't run" when
  the real problem is "PHP isn't installed correctly."
- Keep a terminal window open while you work through this course. You'll be switching
  between editing `.php` files and running them with the `php` command very frequently.

## Exercise

On your own computer, open a terminal and type `php -v`, then press Enter. Write down
(in your own notes) exactly what happened: did it print a version number, or an error
message? If it printed an error, note the exact wording of that error.

## Solution

There isn't a single universal output to compare against, since the exact version
number depends on what you installed — but here is how to judge your result:

- **Success looks like:** a block of text starting with something like
  `PHP 8.x.x (cli) ...`. If you saw this, PHP is installed correctly and you are ready
  to move on to writing your first script.
- **Failure looks like:** a message saying the `php` command was not recognized or not
  found. If you saw this, PHP is either not installed yet, or it is installed but your
  terminal doesn't know where to find it — in which case, revisit the installation
  steps for whichever option (direct install, XAMPP/Laragon/MAMP, etc.) you chose, and
  make sure to reopen your terminal afterward.

## Mini Quiz

```yaml
- question: "What does typing `php -v` in a terminal actually do?"
  options:
    - "It writes a new PHP file named v.php"
    - "It asks the PHP interpreter to report its version number, confirming it is installed"
    - "It starts a web server permanently"
    - "It deletes any existing PHP installation"
  answer: 1
  explanation: "The -v flag tells the php command to print its version information, which is a quick way to confirm PHP is correctly installed and reachable from the terminal."
- question: "Which of these is NOT one of the beginner-friendly ways to get PHP running mentioned in this lesson?"
  options:
    - "Installing PHP directly for your operating system"
    - "Using a bundled local stack like XAMPP, Laragon, or MAMP"
    - "Using an online browser-based sandbox"
    - "Compiling a custom web browser from source"
  answer: 3
  explanation: "Direct installation, bundled stacks (XAMPP/Laragon/MAMP), and online sandboxes are the three options discussed. Compiling a custom browser has nothing to do with running PHP."
- question: "What does it mean if typing `php -v` gives an error like 'command not found'?"
  options:
    - "PHP is installed but your code has a syntax error"
    - "The terminal is broken and needs to be reinstalled"
    - "PHP is not installed, or the terminal doesn't know where to find it"
    - "This is normal and PHP still works fine"
  answer: 2
  explanation: "A 'command not found' (or 'not recognized') error means the terminal cannot locate a program called php, which usually means PHP isn't installed yet, or the terminal needs to be reopened after installation."
```
