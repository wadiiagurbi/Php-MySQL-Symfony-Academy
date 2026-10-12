---
slug: file-uploads
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: web-php
chapterTitle: "Chapter 3 — Web Development with PHP"
chapterOrder: 3
title: "File Uploads"
difficulty: beginner
order: 7
estimatedMinutes: 12
tags: [file-uploads, forms, http]
---

## Learning Objective

By the end of this lesson, you will be able to build an HTML form that lets a visitor upload a file, read the uploaded file's information in PHP through the `$_FILES` superglobal, and save the file safely with `move_uploaded_file()`.

## Explanation

So far, the forms you have built (as covered in the forms lesson) have sent simple text values through `$_GET` or `$_POST`. But web applications very often need something more: letting a visitor send an actual file, such as a profile picture, a PDF document, or a spreadsheet.

To allow this, an HTML form needs two things:

1. An input field of type file: `<input type="file" name="avatar">`.
2. The form tag itself must include `enctype="multipart/form-data"`. This attribute changes how the browser packages the request body so it can carry raw file data alongside any regular text fields. Without it, the file will not be sent correctly, even if everything else looks right.

When such a form is submitted, PHP does not put the uploaded file into `$_POST`. Instead, it places information about the file into a separate superglobal array called `$_FILES`. For an input named `avatar`, PHP gives you `$_FILES['avatar']`, which is itself an array containing several useful pieces of information:

- `name` — the original file name as it was on the visitor's own computer (for example `photo.jpg`).
- `tmp_name` — the temporary location on the server's disk where PHP has already stored the uploaded file's contents while your script runs.
- `size` — the file size in bytes.
- `type` — the file's MIME type (its file format) as reported by the browser, such as `image/jpeg`.
- `error` — an error code; `0` (also available as the constant `UPLOAD_ERR_OK`) means the upload succeeded.

The uploaded file only exists temporarily at `tmp_name` for the duration of the request. If you want to keep it, you must move it to a permanent location yourself using `move_uploaded_file()`, which both moves the file and checks that it was genuinely uploaded through PHP (protecting against certain attacks).

## Why It Matters

File uploads power features you have almost certainly used yourself: uploading a profile photo, attaching a document to a support ticket, submitting a CV on a job application form, or uploading a CSV file to import data into a system. Nearly every real-world PHP application that deals with user-generated content needs to handle file uploads correctly and safely at some point.

## Example

A simple form lets a visitor upload a profile picture. The PHP script checks that a file was actually sent, checks its type and size, and then moves it into an `uploads/` folder with a safe, unique file name.

## Code

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "Upload failed with error code: " . $file['error'];
    } elseif ($file['size'] > 2 * 1024 * 1024) { // limit: 2 MB
        echo "File is too large. Maximum size is 2MB.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowedTypes, true)) {
            echo "Only JPEG and PNG images are allowed.";
        } else {
            $extension = $file['type'] === 'image/png' ? 'png' : 'jpg';
            $safeName = uniqid('avatar_', true) . '.' . $extension;
            $destination = __DIR__ . '/uploads/' . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                echo "File uploaded successfully as $safeName.";
            } else {
                echo "Could not save the uploaded file.";
            }
        }
    }
}
```

## Code Explanation

- `isset($_FILES['avatar'])` checks whether a file was actually submitted under the input name `avatar` before trying to read anything about it.
- `$file['error'] !== UPLOAD_ERR_OK` checks PHP's own upload error code first. If something went wrong during the upload itself (for example the file exceeded a server limit), this catches it before your code tries to use a broken or missing file.
- The size check compares `$file['size']` (in bytes) against 2 megabytes, converted here as `2 * 1024 * 1024` bytes, rejecting files that are too large.
- The type check compares `$file['type']` against a fixed list of allowed MIME types, using strict comparison (`true` as the third argument to `in_array`) to avoid unexpected type-juggling matches.
- A brand-new file name is generated with `uniqid()` rather than trusting `$file['name']`, and the extension is chosen based on the verified type rather than copying whatever extension the original file name had.
- `move_uploaded_file()` moves the file from its temporary location to the final `uploads/` folder, and returns `true` only if the move succeeded and the source file was a genuine PHP upload.

## Common Mistakes

- **Forgetting `enctype="multipart/form-data"` on the `<form>` tag.** Without it, `$_FILES` will simply be empty, even though the form otherwise looks correct.
- **Trusting the original file name (`$file['name']`) or the reported `type`.** Both values are supplied by the visitor's browser and can be faked; never use the original name directly as your save path, and never trust `type` alone as a security check for what the file actually is.
- **Using `move()` or `copy()` instead of `move_uploaded_file()`.** Only `move_uploaded_file()` verifies that the source file really was uploaded through PHP's upload mechanism, which helps prevent certain file-tampering attacks.
- **Not checking `size` before processing.** A visitor could otherwise submit an enormous file and exhaust server disk space or memory.
- **Saving uploaded files inside a publicly executable folder without restricting how they can run.** An uploaded file with a `.php` extension, if placed somewhere PHP will execute it, can become a serious security hole.

## Best Practices

- Always validate both file size and file type on the server, even if you also do it in the browser with HTML attributes — browser-side checks can always be bypassed.
- Generate a new, unpredictable file name for every saved upload instead of reusing the visitor's original file name.
- Store uploaded files outside of, or with execution disabled in, any folder the web server would otherwise run PHP scripts from.
- Always check PHP's `error` code from `$_FILES` before doing anything else with the uploaded file.

## Exercise

Build a small script that accepts a file upload from an input named `document`. Only allow PDF files (MIME type `application/pdf`), with a maximum size of 5 MB. If accepted, move it into an `uploads/` folder using a random file name that keeps the `.pdf` extension. Print a clear success or failure message in each case.

## Solution

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "Upload error, code: " . $file['error'];
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        echo "File is too large. Maximum size is 5MB.";
    } elseif ($file['type'] !== 'application/pdf') {
        echo "Only PDF files are allowed.";
    } else {
        $safeName = uniqid('document_', true) . '.pdf';
        $destination = __DIR__ . '/uploads/' . $safeName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "PDF uploaded successfully as $safeName.";
        } else {
            echo "Could not save the uploaded file.";
        }
    }
}
```

This mirrors the lesson's example: it checks the upload error code first, then the size limit, then the MIME type, and only after all three checks pass does it generate a safe random file name and move the file into place with `move_uploaded_file()`.

## Mini Quiz

```yaml
- question: "What form attribute is required for file uploads to work?"
  options:
    - "method=\"file\""
    - "enctype=\"multipart/form-data\""
    - "type=\"upload\""
    - "action=\"upload.php\""
  answer: 1
  explanation: "enctype=\"multipart/form-data\" tells the browser to encode the request so it can carry raw file data."
- question: "Where does PHP temporarily store an uploaded file's contents during the request?"
  options:
    - "In $_POST"
    - "In the path given by $_FILES['field']['tmp_name']"
    - "Directly in $_FILES['field']['name']"
    - "It is not stored anywhere until you call setcookie()"
  answer: 1
  explanation: "PHP stores the uploaded file's data at a temporary path, available as tmp_name, until you move or discard it."
- question: "Why should you use move_uploaded_file() instead of a generic copy() function?"
  options:
    - "It is faster"
    - "It also verifies the file was genuinely uploaded through PHP, adding a security check"
    - "It automatically resizes images"
    - "It is the only function that can read $_FILES"
  answer: 1
  explanation: "move_uploaded_file() checks that the given path is a valid PHP upload before moving it, which copy() does not do."
- question: "Why shouldn't you trust the original file name from $_FILES['field']['name']?"
  options:
    - "It is always empty"
    - "It is supplied by the visitor's browser and can be manipulated or unsafe to use directly"
    - "PHP does not actually provide this value"
    - "It can only contain numbers"
  answer: 1
  explanation: "The original name comes from user-controlled input, so it should never be trusted or used directly as a server file path."
```
