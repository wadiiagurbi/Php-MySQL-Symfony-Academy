---
slug: your-first-php-script
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Your First PHP Script"
difficulty: beginner
order: 3
estimatedMinutes: 8
tags: [php, basics, echo, command-line]
---

## Learning Objective

By the end of this lesson, you will be able to create a `.php` file from scratch, add
working PHP code to it using `echo`, and run that file from the command line to see
its output.

## Explanation

A **script** is simply a file containing a sequence of instructions for a computer to
carry out, one after another. When we talk about "writing a PHP script," we mean
creating a text file that contains PHP code, saved with the `.php` file extension (the
letters after the dot in a filename, like `hello.php`, which tell your computer and
the PHP interpreter what kind of file it is).

Every file that contains PHP code needs to tell the interpreter "the following text is
PHP, please run it as instructions rather than just treating it as plain text." This
is done with an **opening tag**: `<?php`. As soon as the PHP interpreter sees `<?php`,
it switches into "execute this as code" mode.

You may also see files that end with a matching **closing tag**, `?>`. This tag tells
PHP "the code section ends here; treat anything after this as plain text again." For a
file that contains nothing but PHP code from start to finish (which is what you'll
write for most of this course), the modern convention is to simply leave the closing
`?>` tag off entirely. This avoids a subtle problem: if there is any stray blank line
or space after `?>`, PHP can accidentally send that as extra, invisible output, which
sometimes causes confusing bugs later on. Leaving it off entirely sidesteps that issue.

Once inside PHP code, the most basic instruction you can give is to display some text.
This is done with `echo`, a PHP keyword that outputs whatever follows it. There is also
a very similar instruction called `print`, which does almost the same job as `echo`.
For simple output, the two are essentially interchangeable, but `echo` is slightly more
common and is what we will use throughout this course.

Finally, to actually see your script run, you use the command line skill from the
previous lesson: typing `php` followed by the filename tells the interpreter "read
this specific file and execute it." For example, `php hello.php` runs the file named
`hello.php`.

## Why It Matters

Every single PHP program — from a simple script to a massive framework like Symfony —
is, underneath everything, built from files that start with `<?php` and produce output
through mechanisms rooted in the same idea as `echo`. Even when you later build web
pages, JSON APIs, or database-driven applications, the fundamental cycle stays the
same: write a `.php` file, have the interpreter run it, and produce some output.

Practicing this now — write file, run file, read output, repeat — is also how you will
work for the rest of this course (and your programming career). Getting comfortable
with that loop early removes a huge source of friction later, when the code itself
becomes more complex and you don't want to also be fighting the basic mechanics of
"how do I even run this."

## Example

Suppose you want to write a tiny program that greets the world — a traditional first
program for almost any programming language, often called "Hello, World!" You'll
create a file, put a single line of PHP in it, and run it from the terminal to see the
greeting appear.

## Code

```php
<?php

echo "Hello, World!";
echo "\n";
echo "This is my first PHP script.";
```

## Code Explanation

- `<?php` is the opening tag. It must be the very first thing in the file (no spaces or
  characters before it) for PHP to correctly recognize the whole file as code.
- `echo "Hello, World!";` outputs the text `Hello, World!` exactly as written between
  the quotes. The text between quotation marks is called a **string** — a sequence of
  characters (letters, numbers, symbols) treated as text rather than as a number or
  instruction.
- `echo "\n";` outputs a **newline character**. Inside double quotes, PHP treats the
  two characters `\n` specially: instead of printing a backslash followed by the letter
  n, it inserts an actual line break, moving anything printed afterward to a new line.
  We'll cover this kind of special handling in more depth in the strings lesson.
- `echo "This is my first PHP script.";` outputs a second line of text.
- Each instruction ends with a semicolon `;`, marking where one instruction finishes
  and the next can begin — exactly like the period at the end of a sentence.
- Notice there is no closing `?>` tag at the end of the file. Since this file contains
  only PHP code with nothing after it, the closing tag is intentionally left off, as
  explained above.

If you saved this as `hello.php` and ran `php hello.php` in your terminal, you would
see:

```text
Hello, World!
This is my first PHP script.
```

## Common Mistakes

- **Saving the file with the wrong extension**, such as `hello.txt` or just `hello`
  with no extension. The PHP interpreter itself doesn't strictly require the `.php`
  extension to run a file when you call it directly by name with `php filename`, but
  web servers do require it to recognize the file as PHP, and it's a strong convention
  everyone follows — skipping it will confuse both you and any tool you use later.
- **Forgetting the semicolon at the end of a line.** PHP uses semicolons to know where
  one instruction ends and the next begins. Leaving one out (for example, writing
  `echo "Hi"` with no `;`) typically causes a "parse error," a message meaning PHP
  could not understand the structure of your code.
- **Typing the command in the wrong location.** If you run `php hello.php` from a
  terminal that is not currently "inside" the folder where `hello.php` is saved, PHP
  will report that it cannot find the file. You need to navigate your terminal to the
  correct folder first, or provide the full path to the file.

## Best Practices

- Give your script files short, descriptive, lowercase names, like `hello.php` or
  `greeting.php`, rather than vague names like `test1.php`. This matters more as you
  accumulate many files throughout this course.
- Run your script immediately after writing even a tiny amount of code, rather than
  writing a large amount of code first and testing at the end. Seeing quick feedback
  helps you catch mistakes (like a missing semicolon) while the cause is still obvious.
- Get used to reading error messages fully rather than skimming past them. PHP's error
  messages usually tell you the exact line number where something went wrong, which
  saves enormous time compared to guessing.

## Exercise

Create a new file named `about-me.php`. Inside it, write PHP code that, when run,
outputs three separate lines: your first name, your favorite food, and one hobby you
enjoy. Run the file from the command line and confirm the output looks correct.

## Solution

Here is one valid way to solve it (your actual text will differ):

```php
<?php

echo "Name: Alex";
echo "\n";
echo "Favorite food: Pasta";
echo "\n";
echo "Hobby: Playing the guitar";
```

Saving this as `about-me.php` and running `php about-me.php` produces:

```text
Name: Alex
Favorite food: Pasta
Hobby: Playing the guitar
```

Each `echo` prints one piece of text, and each `"\n"` moves the following output to a
new line, which is what keeps the three pieces of information visually separated
instead of running together on one line.

## Mini Quiz

```yaml
- question: "What must be the very first thing in a file for PHP to treat its contents as executable code?"
  options:
    - "The filename must be 'main.php'"
    - "The opening tag <?php"
    - "A comment describing the file"
    - "The word 'start'"
  answer: 1
  explanation: "PHP looks for the <?php opening tag to know where code begins. Without it at the start, the file's content is treated as plain text, not instructions."
- question: "What does echo do in a PHP script?"
  options:
    - "It deletes a variable"
    - "It outputs whatever follows it"
    - "It stops the script from running"
    - "It renames the current file"
  answer: 1
  explanation: "echo is a PHP instruction that outputs the text or value that follows it, sending it to wherever the script's output goes (like your terminal)."
- question: "Which command runs a file named greeting.php from the terminal?"
  options:
    - "run greeting.php"
    - "open greeting.php"
    - "php greeting.php"
    - "greeting.php --start"
  answer: 2
  explanation: "Typing php followed by the filename tells the PHP interpreter to read and execute that specific file, e.g. php greeting.php."
- question: "Why do many modern PHP files that contain only PHP code omit the closing ?> tag?"
  options:
    - "Because PHP files are not allowed to have a closing tag"
    - "To avoid accidental stray output from whitespace placed after the closing tag"
    - "Because it makes the file run faster"
    - "Because echo requires the closing tag to be removed"
  answer: 1
  explanation: "If characters (even a blank line) appear after ?> in a pure-PHP file, PHP can send them as unintended output. Omitting the closing tag entirely avoids this problem."
```
