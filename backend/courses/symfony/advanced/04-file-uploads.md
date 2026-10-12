---
slug: symfony-file-uploads
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Handling File Uploads"
difficulty: intermediate
order: 4
estimatedMinutes: 12
tags: [symfony, uploads, files, forms]
---

## Learning Objective

Learn how Symfony represents uploaded files with `UploadedFile`, how to validate, rename, and move them safely, and why uploads must never be trusted blindly.

## Explanation

When a browser submits a form containing `<input type="file">` (with the required `enctype="multipart/form-data"` attribute that switches the form to binary-capable encoding), PHP receives the file separately from text fields, and Symfony wraps it in an `UploadedFile` object. This object exposes safe helpers: `getClientOriginalName()` (the name on the user's computer — useful for display only, never for storage), `guessExtension()` (the extension inferred from the file's actual content), and `move($directory, $name)` (which relocates the temporary upload to its permanent home). Your controller or service fetches it from the request via `$request->files->get('avatar')` — note `files`, not `request`, which holds only text fields.

Uploads are inherently hostile input: the "image" might be a PHP script, the filename might contain path tricks like `../../evil.php`, and the size might exhaust your disk. Three defenses are mandatory. First, **validate**: check MIME type and size with Symfony's `File` or `Image` constraints (a **MIME type** is the content label like `image/png` describing what the bytes actually are) before touching the file. Second, **rename**: generate a random, extension-safe filename (`bin2hex(random_bytes(16)) . '.' . $file->guessExtension()`) so attacker-controlled names never reach your filesystem. Third, **store outside executable reach**: keep uploads in a dedicated directory like `public/uploads/` configured via a parameter, and never in a location where the web server would execute scripts.

The entity stores only the *filename string* (e.g., `avatarFilename`), never the file itself — the database remembers the name while the filesystem holds the bytes. Recording the original client name in a separate column is a nice touch for "download as..." headers.

## Why It Matters

Avatars, attachments, CSV imports, invoice PDFs — file upload features appear in most business apps, and insecure handling is a classic breach vector (uploaded shells are a textbook takeover). Reviewers scrutinize upload code closely: validation, renaming, and storage location are the three questions every upload implementation must answer convincingly.

## Example

User profiles need an avatar image (JPEG or PNG, max 2 MB). The controller validates the upload, stores it under a random name in the configured upload directory, and records the filename on the user entity.

## Code

```php
<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AvatarController extends AbstractController
{
    #[Route('/account/avatar', name: 'app_avatar', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('avatar');

        if (null === $file || !$file->isValid()) {
            $this->addFlash('error', 'Please choose a valid image file.');

            return $this->redirectToRoute('app_account');
        }

        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $file->move($this->getParameter('avatars_dir'), $filename);

        $this->getUser()->setAvatarFilename($filename);
        $em->flush();

        $this->addFlash('success', 'Avatar updated!');

        return $this->redirectToRoute('app_account');
    }
}
```

## Code Explanation

- `$request->files->get('avatar')` retrieves the upload from the files bag (text fields live in `$request->request`) — mixing these up yields `null` and is a common beginner bug.
- `!$file->isValid()` rejects failed uploads (too large for PHP limits, partial transfers) before any further processing.
- `bin2hex(random_bytes(16))` creates an unpredictable 32-character name, and `guessExtension()` derives the extension from content, not the user-supplied filename.
- `$file->move($this->getParameter('avatars_dir'), $filename)` relocates the temp file to the directory configured as the `avatars_dir` parameter — one config value shared by code and deploy setup.
- Only the generated filename string is persisted on the user; the bytes live on disk, keeping the database small and the file servable directly.

## Common Mistakes

- Trusting `getClientOriginalName()` for storage. It is attacker-controlled and may contain path traversal or double extensions (`shell.php.jpg`) — use it for display at most.
- Forgetting `enctype="multipart/form-data"` on the HTML form. Without it, browsers send only the filename text and `$request->files` stays empty with no error.
- Skipping MIME/size validation and moving whatever arrives. A 2 GB "avatar" or an executable script lands on your server unchecked.

## Best Practices

- Validate first (MIME type via `Image`/`File` constraints, size limits aligned with PHP's `upload_max_filesize`), rename with random bytes plus content-guessed extension, and store in a dedicated non-executable directory.
- Persist only the filename string on the entity and serve uploads as static files — never `include` or execute anything from the upload directory.
- Show upload failures with flash messages and keep the original-name display separate from the storage name.

## Exercise

An expense form accepts PDF receipts up to 5 MB under field name `receipt`. List the four handling steps (retrieval, validity, naming, storage) with the exact methods/values you would use.

## Solution

Retrieve with `$request->files->get('receipt')`; reject when null or `!$file->isValid()`; validate MIME `application/pdf` and size ≤ 5 MB via a `File` constraint; name with `bin2hex(random_bytes(16)).'.'.$file->guessExtension()`; then `$file->move($this->getParameter('receipts_dir'), $filename)` and persist only `$filename`. Each step neutralizes one attack: missing-file crashes, corrupt transfers, malicious types, filename tricks.

## Mini Quiz

```yaml
- question: "How does Symfony expose an uploaded file, and where do you read it from?"
  options:
    - "As a plain string in $request->request"
    - "As an UploadedFile object from $request->files, separate from text fields"
    - "As a Twig variable available in all templates"
    - "As a row in the session storage"
  answer: 1
  explanation: "File inputs arrive in the files bag as UploadedFile objects with helpers for validation, naming, and moving; text fields live in the request bag."
- question: "Why must uploads be stored under a randomly generated name instead of the client-provided filename?"
  options:
    - "Random names make files download faster"
    - "Client filenames are attacker-controlled and may contain path traversal or executable extensions"
    - "PHP cannot handle filenames with more than eight characters"
    - "Databases can only store hexadecimal strings"
  answer: 1
  explanation: "The original name is untrusted input; generating your own name from random bytes plus a content-derived extension neutralizes traversal and script-upload attacks."
- question: "Why does an upload form need enctype=multipart/form-data?"
  options:
    - "It encrypts the file with the user's password"
    - "It switches the form to binary-capable encoding; without it only the filename text is sent and no file arrives"
    - "It compresses images to save bandwidth"
    - "It is optional and has no effect on uploads"
  answer: 1
  explanation: "Default form encoding cannot carry file bytes, so the multipart enctype is required for $request->files to contain anything at all."
```
