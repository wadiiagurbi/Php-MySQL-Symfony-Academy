---
slug: working-with-forms
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "Working with HTML Forms"
difficulty: beginner
order: 3
estimatedMinutes: 12
tags: [php, forms, html, get, post]
---

## Learning Objective

By the end of this lesson, you will understand what an HTML form is, how its `action` and `method` attributes control where and how data is sent, how each input's `name` attribute becomes a key in `$_GET` or `$_POST`, and how to write a PHP script that receives and displays submitted form data.

## Explanation

An HTML **form** is a section of a web page that contains input fields — text boxes, checkboxes, dropdown menus, buttons — that let a visitor type or select information and send it somewhere. A form is written with the `<form>` tag, and everything inside it (labels, inputs, a submit button) is part of that form.

A form tag has two attributes that matter most for what happens when it is submitted:

- `action` — the URL of the script that should receive the form's data when it is submitted. If you leave it out, the form submits to the same page it is on.
- `method` — which HTTP method to use when sending the data: `"get"` or `"post"`. You learned in the previous lesson that GET puts data in the URL's query string, while POST puts it in the request body.

Each input field inside a form also has a `name` attribute. This name is not just a label for the developer — it becomes the **array key** PHP uses to find that field's value in `$_GET` or `$_POST`. For example, an input written as `<input type="text" name="email">` will show up as `$_POST['email']` (if the form's method is POST) once the form is submitted.

When the visitor clicks the submit button, the browser packages up every named input's current value and sends it, as a new HTTP request, to whatever URL is in `action`, using whatever method is in `method`. The script at that URL — often the very same PHP file — can then read `$_GET` or `$_POST` exactly as you practiced in the previous lesson.

A very common and convenient pattern is to have a single PHP file that both displays the form and processes it. The script checks whether the form has actually been submitted (usually by checking `$_SERVER['REQUEST_METHOD']`, which tells you which HTTP method the current request used) and either shows the empty form or shows the submitted results.

## Why It Matters

Forms are how almost all user-generated content gets onto the web: registration pages, comment sections, checkout pages, search bars, and settings screens are all built from HTML forms submitting to server-side scripts. Understanding exactly how a `name` attribute connects to a PHP array key removes a huge source of beginner confusion — many bugs in early PHP projects come down to a typo between an input's `name` in the HTML and the array key used in the PHP (`$_POST['userName']` versus `$_POST['username']`, for example). Later, when you use Symfony's form component, it will still rely on this same underlying mechanism — named inputs submitted via GET or POST — just wrapped in a more powerful set of tools.

## Example

Consider a simple contact form: a visitor types their name and a message, then clicks "Send." The form uses POST, since submitting a message is an action that should not be repeated just by refreshing or bookmarking the URL. The same PHP file both shows the form and displays the submitted message.

## Code

```php
<?php
// contact.php

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($submitted) {
    $name = trim($_POST['name'] ?? '');
    $message = trim($_POST['message'] ?? '');
}
?>
<!DOCTYPE html>
<html>
<head><title>Contact Us</title></head>
<body>

<?php if ($submitted && $name !== '' && $message !== ''): ?>
    <h1>Thank you, <?php echo htmlspecialchars($name); ?>!</h1>
    <p>We received your message: "<?php echo htmlspecialchars($message); ?>"</p>
<?php else: ?>
    <h1>Contact Us</h1>
    <form action="contact.php" method="post">
        <label>Name: <input type="text" name="name"></label><br>
        <label>Message: <textarea name="message"></textarea></label><br>
        <button type="submit">Send</button>
    </form>
<?php endif; ?>

</body>
</html>
```

## Code Explanation

- `$_SERVER['REQUEST_METHOD']` is another PHP superglobal; it tells you which HTTP method the current request used, as a string such as `"GET"` or `"POST"`. Comparing it to `'POST'` lets the script tell apart "the visitor just arrived at this page" from "the visitor just submitted the form."
- `trim($_POST['name'] ?? '')` first falls back to an empty string if `name` was not sent at all, then removes any leading or trailing whitespace, so a message consisting only of spaces is correctly treated as empty.
- The `<form>` tag's `action="contact.php"` sends submissions back to this very same file, and `method="post"` means the data will arrive in `$_POST`, not `$_GET`.
- The input `name="name"` and the textarea `name="message"` are what create the keys `$_POST['name']` and `$_POST['message']` after submission — the browser reads these `name` attributes when it builds the outgoing request.
- `htmlspecialchars()` converts special HTML characters in the visitor's text (like `<` or `&`) into safe equivalents before displaying them back on the page. Skipping this step would let a visitor type HTML or script tags into the message box and have them rendered as real HTML — you will look at this in more depth in the next lesson.

## Common Mistakes

- **Mismatched `name` attributes and array keys.** If the HTML input is `name="email"` but the PHP reads `$_POST['Email']` (different capitalization) or `$_POST['e_mail']`, the value will always be missing. Array keys in PHP are case-sensitive.
- **Forgetting the `method` attribute entirely.** If you omit `method`, browsers default to GET, which means your data will show up in `$_GET`, not `$_POST` — this surprises a lot of beginners who wrote their PHP code assuming POST.
- **Displaying submitted text without escaping it with `htmlspecialchars()`.** This is a security problem, not just a cosmetic one, because it allows a visitor to inject their own HTML or JavaScript into your page.
- **Not distinguishing "form not submitted yet" from "form submitted with empty fields."** Checking only `if (!empty($_POST))` can behave unexpectedly; checking `$_SERVER['REQUEST_METHOD']` is a clearer, more reliable way to know a submission actually happened.

## Best Practices

- Always give every input a clear, consistent, lowercase `name` attribute that matches exactly how you will read it in `$_GET` or `$_POST`.
- Choose `method="post"` for any form that creates, changes, or deletes something, and `method="get"` for simple search or filter forms.
- Escape any user-submitted value with `htmlspecialchars()` before echoing it back into HTML output.
- Use `$_SERVER['REQUEST_METHOD']` to clearly separate the "show the form" logic from the "process the submission" logic in the same script.

## Exercise

Create a PHP file called `subscribe.php` that shows a form with a single text input named `email` and a submit button, using the POST method. When the form is submitted, the script should display `"Subscribed: "` followed by the submitted email address (escaped with `htmlspecialchars`). If the email field was submitted empty, display `"Please enter an email address."` instead of the form.

## Solution

```php
<?php
// subscribe.php

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$email = $submitted ? trim($_POST['email'] ?? '') : '';
?>
<!DOCTYPE html>
<html>
<head><title>Subscribe</title></head>
<body>

<?php if ($submitted): ?>
    <?php if ($email !== ''): ?>
        <p>Subscribed: <?php echo htmlspecialchars($email); ?></p>
    <?php else: ?>
        <p>Please enter an email address.</p>
    <?php endif; ?>
<?php else: ?>
    <form action="subscribe.php" method="post">
        <label>Email: <input type="text" name="email"></label>
        <button type="submit">Subscribe</button>
    </form>
<?php endif; ?>

</body>
</html>
```

This works because `$_SERVER['REQUEST_METHOD']` reveals whether this request is the initial page load (GET) or a form submission (POST). When it is POST, the script reads `$_POST['email']`, which exists because the input's `name="email"` attribute matches. `trim()` clears out whitespace-only input, so an email box containing only spaces is still treated as empty, and `htmlspecialchars()` keeps the displayed value safe.

## Mini Quiz

```yaml
- question: "What determines the array key used to access a form field's value in $_POST?"
  options:
    - "The input's id attribute"
    - "The input's name attribute"
    - "The order the input appears in the HTML"
    - "The input's type attribute"
  answer: 1
  explanation: "The name attribute on an HTML input is what the browser uses as the key when building the submitted data, so it becomes the array key in $_GET or $_POST."
- question: "What does the form's action attribute specify?"
  options:
    - "The name of the field being submitted"
    - "The HTTP method used to submit the form"
    - "The URL of the script that will receive the submitted data"
    - "The default value shown in the form"
  answer: 2
  explanation: "The action attribute tells the browser which URL to send the form's data to when it is submitted."
- question: "Why does the contact.php example use $_SERVER['REQUEST_METHOD'] instead of just checking if $_POST is empty?"
  options:
    - "REQUEST_METHOD is faster to type"
    - "It reliably distinguishes a fresh page visit (GET) from an actual form submission (POST)"
    - "$_POST does not exist in PHP"
    - "It is required by every PHP form"
  answer: 1
  explanation: "Checking the request method gives a clear, reliable signal of whether the current request is the initial page load or the form submission, rather than guessing based on array contents."
```
