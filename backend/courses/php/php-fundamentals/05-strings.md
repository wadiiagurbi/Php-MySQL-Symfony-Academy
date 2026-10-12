---
slug: strings
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Working with Strings"
difficulty: beginner
order: 5
estimatedMinutes: 13
tags: [php, strings, functions]
---

## Learning Objective

By the end of this lesson, you will understand the difference between single and
double quotes in PHP strings, how to join strings together, and how to use several
common built-in string functions to inspect and transform text.

## Explanation

A **string** is a piece of text — any sequence of characters, such as letters,
numbers, spaces, or punctuation, treated as text rather than as a number or other
data type. You've already used strings in earlier lessons, like `"Sarah"` or
`"Hello, World!"`. This lesson goes deeper into how PHP handles them.

**Single quotes vs. double quotes.** In PHP, you can write a string using either
single quotes (`'like this'`) or double quotes (`"like this"`). Both create a string,
but they behave differently in one important way: **double quotes support variable
interpolation**, meaning if you put a variable name directly inside a double-quoted
string, PHP replaces it with that variable's value automatically. Single quotes do
not do this — they treat everything inside them, including anything that looks like a
variable, as plain literal text.

For example:

```php
$name = "Sam";
echo "Hello, $name!"; // outputs: Hello, Sam!
echo 'Hello, $name!';  // outputs: Hello, $name!
```

Double quotes also understand a few special **escape sequences** — two-character
combinations starting with a backslash that represent characters that are otherwise
hard to type directly, like `\n` for a newline (a line break) or `\t` for a tab.
Single quotes only understand two escape sequences: `\\` (a literal backslash) and
`\'` (a literal single quote), and treat everything else literally.

Sometimes you need to include a quote character inside a string that uses the same
kind of quote. To do this, you **escape** it by putting a backslash directly before
it, like `"She said \"hello\""` or `'It\'s here'`. The backslash tells PHP "treat the
next character as part of the text, not as the end of the string."

**Concatenation.** You already saw the concatenation operator `.` in the first lesson,
which joins two strings (or a string and another value converted to a string) into
one. There is also a shorthand operator, `.=`, which appends text onto the end of an
existing variable and stores the result back into that same variable, saving you from
writing the variable name twice:

```php
$message = "Hello";
$message .= ", World!"; // $message is now "Hello, World!"
```

**Common string functions.** PHP includes many built-in functions — reusable,
ready-made pieces of code you can call by name to perform a task — for working with
strings. A few you will use constantly:

- `strlen($text)` returns the number of characters in a string.
- `strtoupper($text)` returns a new string with all letters converted to uppercase.
- `strtolower($text)` returns a new string with all letters converted to lowercase.
- `trim($text)` returns a new string with whitespace (spaces, tabs, newlines) removed
  from the very start and end of the string, leaving anything in the middle alone.
- `str_replace($search, $replace, $text)` returns a new string where every occurrence
  of `$search` inside `$text` has been replaced with `$replace`.
- `substr($text, $start, $length)` returns a smaller piece ("substring") of a string,
  starting at position `$start` (counting from 0) and continuing for `$length`
  characters.

An important detail: none of these functions change the original string. They each
**return a new string** as their result, which you typically store in a variable or
pass directly to `echo`.

## Why It Matters

Strings are everywhere in real applications: usernames, email addresses, product
descriptions, error messages, form input typed by a visitor — almost every website
handles enormous amounts of text. Knowing how to safely combine text (concatenation),
clean it up (`trim()` to remove stray spaces a user might have accidentally typed),
standardize it (`strtoupper()`/`strtolower()` for comparisons), and inspect or modify
it (`strlen()`, `substr()`, `str_replace()`) is something you will do constantly, not
just as an academic exercise.

The single-vs-double-quote distinction matters practically too: double-quoted
interpolation makes building dynamic messages (like a personalized greeting) much
shorter and more readable than concatenating many small pieces with `.`, which is why
experienced PHP developers often reach for it by default when a variable needs to
appear inside a message.

## Example

Imagine you're building a small part of a website that welcomes a returning customer
by name, and you've noticed that the name was typed with extra spaces and inconsistent
capitalization when it was originally saved (a very common real-world problem with
user-entered data). You want to clean it up and build a friendly, correctly formatted
welcome message.

## Code

```php
<?php

$rawName = "  amara okafor  ";

$cleanName = trim($rawName);
$displayName = strtoupper(substr($cleanName, 0, 1)) . substr($cleanName, 1);

$welcomeMessage = "Welcome back, " . $displayName . "!";
$welcomeMessage .= " We hope you enjoy your visit.";

echo $welcomeMessage;
echo "\n";
echo "Name length: " . strlen($displayName) . " characters";
echo "\n";
echo str_replace("Amara", "Guest", $displayName);
```

## Code Explanation

- `$rawName = "  amara okafor  ";` stores the messy original text, including leading
  and trailing spaces.
- `trim($rawName)` removes the spaces at the very start and end, producing
  `"amara okafor"`, which is stored in `$cleanName`.
- `substr($cleanName, 0, 1)` grabs just the first character of `$cleanName` (starting
  at position 0, taking 1 character), giving `"a"`. `strtoupper()` then converts that
  single character to `"A"`.
- `substr($cleanName, 1)` (with no third argument) grabs everything from position 1
  onward, giving `"mara okafor"`.
- The `.` operator joins the capitalized first letter and the rest of the name into
  `"Amara okafor"`, stored in `$displayName`.
- `$welcomeMessage = "Welcome back, " . $displayName . "!";` builds the first part of
  the message using concatenation.
- `$welcomeMessage .= " We hope you enjoy your visit.";` appends more text onto the
  end of `$welcomeMessage` using the shorthand append operator.
- `echo $welcomeMessage;` prints the full combined message.
- `strlen($displayName)` counts the characters in `"Amara okafor"`.
- `str_replace("Amara", "Guest", $displayName)` returns a new string with `"Amara"`
  swapped out for `"Guest"`, without changing `$displayName` itself.

Running this script prints:

```text
Welcome back, Amara okafor! We hope you enjoy your visit.
Name length: 12 characters
Guest okafor
```

## Common Mistakes

- **Expecting variable interpolation inside single quotes.** Writing
  `echo 'Hello, $name!';` will literally print `Hello, $name!`, including the dollar
  sign and the word "name," because single quotes never interpolate variables.
- **Forgetting that string functions return a new value instead of modifying the
  original.** Writing `trim($rawName);` alone and expecting `$rawName` itself to
  change afterward is a mistake — you must capture the result, like
  `$rawName = trim($rawName);`, if you want to keep the cleaned-up version.
- **Miscounting substr() positions.** Beginners often forget that string positions in
  PHP start counting at 0, not 1. So the first character of a string is at position
  `0`, the second at position `1`, and so on.
- **Using `.` when `.=` was intended, or vice versa.** Writing `$message . " more";`
  instead of `$message .= " more";` calculates a joined string but throws it away
  instead of saving it back into `$message`, since `.` alone doesn't store anything.

## Best Practices

- Reach for double quotes when you need to include a variable's value inside a
  message, since interpolation is usually more readable than several `.`
  concatenations chained together.
- Reach for single quotes for plain, fixed text with no variables, since PHP does
  marginally less work checking for interpolation, and it makes it visually obvious
  to readers that nothing dynamic is happening inside that string.
- Always trim user-provided text (like form input) before storing, comparing, or
  displaying it, since accidental leading/trailing spaces are an extremely common
  source of bugs (like two names that "look" identical but fail an equality check).
- When combining several string functions, as in the example above, consider whether
  breaking the logic into a few named variables (like `$cleanName`, `$displayName`)
  makes the code easier to read than nesting many function calls directly inside each
  other.

## Exercise

Write a script that starts with the string `"   The Great Gatsby   "` (representing a
book title typed with extra surrounding spaces). Clean it up by trimming the
whitespace, then print: the cleaned title, its length in characters, and an uppercase
version of it.

## Solution

```php
<?php

$rawTitle = "   The Great Gatsby   ";
$cleanTitle = trim($rawTitle);

echo $cleanTitle;
echo "\n";
echo strlen($cleanTitle);
echo "\n";
echo strtoupper($cleanTitle);
```

Running this script prints:

```text
The Great Gatsby
17
THE GREAT GATSBY
```

`trim()` removes the extra spaces from both ends, leaving `"The Great Gatsby"`.
`strlen()` then counts its 17 characters (including the two spaces between words, but
not the trimmed outer spaces). Finally, `strtoupper()` converts every letter to
uppercase without affecting the spaces between words.

## Mini Quiz

```yaml
- question: "What is the key difference between single-quoted and double-quoted strings in PHP?"
  options:
    - "Single-quoted strings can hold numbers, double-quoted strings cannot"
    - "Double-quoted strings support variable interpolation and more escape sequences; single-quoted strings do not"
    - "There is no difference; they are always interchangeable"
    - "Single-quoted strings are always faster to display on screen"
  answer: 1
  explanation: "Double quotes allow variables placed inside the string to be automatically replaced with their values (interpolation) and support escape sequences like \\n, while single quotes treat their contents as literal text."
- question: "What does the .= operator do?"
  options:
    - "It compares two strings for equality"
    - "It divides one string by another"
    - "It appends text onto the end of a variable and stores the result back into that variable"
    - "It deletes the contents of a variable"
  answer: 2
  explanation: ".= is shorthand for concatenating new text onto an existing variable's value and saving the result back into that same variable, e.g. $msg .= '!' appends '!' to $msg."
- question: "After calling $upper = strtoupper($name);, what happens to the original variable $name?"
  options:
    - "$name is converted to uppercase permanently"
    - "$name is deleted"
    - "$name stays exactly as it was; strtoupper() returns a new string instead of modifying it"
    - "$name becomes null"
  answer: 2
  explanation: "PHP string functions like strtoupper() do not modify the original string; they return a new string as their result, which must be captured in a variable (like $upper here) to be kept."
- question: "In substr($text, 1), what does the number 1 represent?"
  options:
    - "The total number of characters to keep"
    - "The starting position, where counting begins at 0"
    - "The number of times to repeat the string"
    - "The character to search for"
  answer: 1
  explanation: "The second argument to substr() is the starting position within the string, and PHP counts string positions starting from 0, so 1 means 'start from the second character.'"
```
