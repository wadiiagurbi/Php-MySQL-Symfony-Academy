# PHP & Symfony — From Scratch

A complete, beginner-friendly learning platform that takes a learner with **zero PHP
knowledge** through PHP fundamentals, MySQL, and Symfony, and finishes with a real
Symfony application (a Task Manager) built incrementally across the final chapter.

The platform itself is a working full-stack application: a Symfony JSON API backend
and a Vue 3 SPA frontend, with real authentication, progress tracking, quizzes, search,
and a working Task Manager — all serving as a live example of the concepts the course
teaches.

## Project overview

- **140 lessons** across 4 tracks (PHP, MySQL, Symfony, Final Project), each following a
  fixed structure: Learning Objective, Explanation, Why It Matters, Example, Code, Code
  Explanation, Common Mistakes, Best Practices, Exercise, Solution, Mini Quiz.
- Lesson content is **Markdown + YAML frontmatter**, stored as files under
  `backend/courses/` — never hardcoded into the frontend, never duplicated into the
  database.
- A Symfony backend exposes that content (and real user accounts, progress, quizzes,
  search, and a Task Manager) as a JSON API.
- A Vue 3 + Vuetify SPA consumes that API: course navigation, lesson pages with syntax
  highlighting and copy-to-clipboard, interactive quizzes, dark mode, search, a
  dashboard, and a working Task Manager UI.

## Architecture

```
php-symfony-academy/
├── backend/                     Symfony 6.4 API (PHP 8.2)
│   ├── src/
│   │   ├── Controller/Api/      Thin HTTP controllers (Request/Response only)
│   │   ├── DTO/                 Request/response shapes, decoupled from entities
│   │   ├── Entity/               Doctrine entities (User, Task, TaskCategory, ...)
│   │   ├── Repository/           Query logic (QueryBuilder-based filtering/paging)
│   │   ├── Service/               Business logic (CourseContentLoader, TaskService, ...)
│   │   ├── EventListener/         ApiExceptionListener (every /api/* error → clean JSON)
│   │   └── Exception/             Domain exceptions (LessonNotFoundException, ...)
│   ├── config/                   Symfony configuration (security, doctrine, cors, ...)
│   ├── courses/                  Lesson content — Markdown + YAML frontmatter
│   ├── migrations/                Doctrine migrations
│   └── tests/                     PHPUnit unit + functional tests
│
├── frontend/                     Vue 3 + Vite SPA
│   └── src/
│       ├── api/                   Axios client (JWT header injection)
│       ├── stores/                Pinia: auth, courses, theme
│       ├── router/                 Vue Router (auth guards)
│       ├── components/             AppSidebar, CodeBlock, QuizWidget, MarkdownContent, ...
│       ├── views/                  DashboardView, LessonView, TaskManagerView, ...
│       └── utils/                  Markdown rendering (markdown-it + highlight.js)
│
└── README.md
```

### Why this shape (architecture decisions)

- **Controller → DTO → Service → Repository → Entity.** Every write endpoint follows
  this exact chain (see `TaskController` → `TaskRequest` → `TaskService` →
  `TaskRepository` → `Task`). Controllers never contain business logic or raw queries;
  that's what the "Real-World Architecture" chapter of the course teaches, using this
  codebase as its own example.
- **Markdown is the single source of truth for lesson content.** `CourseContentLoader`
  scans `backend/courses/**/*.md` at request time (cached per process), parses YAML
  frontmatter and Markdown sections, and returns structured DTOs. Adding a lesson is
  just adding a file — no code change, no migration, no frontend change.
- **The database stores application data, not course content**: users, progress
  (`LessonCompletion`), quiz attempts (`QuizAttempt`), and the Task Manager's own data
  (`Task`, `TaskCategory`). Lesson text is never duplicated into a table.
- **Stateless JWT auth**, not sessions — the frontend is a separate SPA origin in
  development, and a stateless API scales horizontally without shared session storage.
- **One exception listener, not per-controller try/catch.** `ApiExceptionListener`
  guarantees every `/api/*` response is JSON (never Symfony's default HTML error page),
  with a consistent `{"error": {"message": ..., "details": ...}}` shape.
- **404, not 403, for "not your resource."** Task ownership checks deliberately return
  404 for another user's task, to avoid confirming a resource's existence to a caller
  who has no business asking about it. This is explained and taught in the course
  itself (`final-project/task-manager/08-validation-and-authorization.md`).

## Requirements

- PHP 8.2+ with `pdo_sqlite`, `openssl` extensions enabled
- Composer 2
- Node.js 20+ / npm
- (Optional) MySQL 8+ if you want to run against MySQL instead of SQLite
- (Optional) OpenSSL CLI, only if you ever need to regenerate the JWT keypair

## Installation

### Backend

```bash
cd backend
composer install
```

Environment variables live in `backend/.env`. The defaults work out of the box for
local development:

```dotenv
# Database — SQLite by default, zero setup required
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
# To use MySQL instead, comment the line above and uncomment/edit this one:
# DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"

# CORS — allows the local Vite dev server to call the API
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# JWT signing keys — already generated under config/jwt/ for this project
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=<already set>
```

Run database migrations (creates the SQLite file automatically on first write):

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

If you ever need to regenerate the JWT keypair (the project already ships with one):

```bash
mkdir -p config/jwt
openssl genpkey -aes256 -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -pass pass:<your-passphrase> -out config/jwt/private.pem
openssl pkey -in config/jwt/private.pem -passin pass:<your-passphrase> -out config/jwt/public.pem -pubout
```

(Update `JWT_PASSPHRASE` in `.env` to match.) This project intentionally uses the
`openssl` CLI rather than `php bin/console lexik:jwt:generate-keypair`, because some
Windows PHP builds have a broken `openssl` extension configuration; the CLI approach
works everywhere.

### Frontend

```bash
cd frontend
npm install
```

`frontend/.env` points the frontend at the backend:

```dotenv
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

## Running the app

```bash
# Terminal 1 — backend
cd backend
php -S 127.0.0.1:8000 -t public

# Terminal 2 — frontend
cd frontend
npm run dev
```

Open the URL Vite prints (typically `http://localhost:5173/`). Register an account to
unlock progress tracking, quizzes, and the Task Manager.

## Running tests

```bash
cd backend
php bin/phpunit
```

36 tests (unit + functional) cover the content loader, course/lesson API, 404/malformed
handling, auth, progress, quizzes, and the full Task Manager CRUD + filtering +
pagination + ownership rules.

```bash
cd frontend
npm run build
```

Production build — also the quickest way to catch a frontend compile error.

## Course content: format and how to add a lesson

Every lesson is one Markdown file under `backend/courses/<track>/<chapter>/NN-slug.md`:

````markdown
---
slug: what-is-php # unique, kebab-case, becomes the URL /lessons/<slug>
track: php # php | mysql | symfony | final-project
trackTitle: "PHP"
trackOrder: 1 # controls track order on the dashboard
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1 # controls chapter order within the track
title: "What is PHP?"
difficulty: beginner # beginner | intermediate | advanced
order: 1 # lesson order within the chapter
estimatedMinutes: 8 # optional, defaults to 10
tags: [php, basics] # optional, used by search
---

## Learning Objective

...

## Explanation

...

## Why It Matters

...

## Example

...

## Code

​```php

<?php
echo "hello";
​```
## Code Explanation
...
## Common Mistakes
...
## Best Practices
...
## Exercise
...
## Solution
...
## Mini Quiz
​```yaml
- question: "..."
  options: ["A", "B", "C"]
  answer: 1                    # 0-based index of the correct option
  explanation: "..."
​```
````

To add a lesson: drop a new `.md` file following this format anywhere under
`backend/courses/`. `CourseContentLoader` discovers it automatically on the next
request — no code change, no registration step. All 11 sections are required, or the
file is treated as malformed (logged and skipped, never crashes the API — see
`CourseContentLoaderTest` and `CourseApiTest` for the exact contract).

Global lesson ordering (used for Previous/Next navigation) is `[trackOrder, chapterOrder,
order]` — keep those numbers consistent within a chapter/track when adding lessons.

## How authentication works

- `POST /api/auth/register` — creates a `User` (email, hashed password, display name).
- `POST /api/auth/login` — a declarative `json_login` firewall (see
  `config/packages/security.yaml`) checks credentials and issues a JWT via
  LexikJWTAuthenticationBundle. There is no controller action for login itself.
- The frontend stores the JWT in `localStorage` (Pinia `auth` store) and attaches it as
  `Authorization: Bearer <token>` to every subsequent API call.
- `GET /api/me` and every other non-public `/api/*` route require a valid JWT (enforced
  by `access_control` rules in `security.yaml`); public routes (`/api/health`,
  `/api/courses`, `/api/lessons/*`, `/api/search`) work without one.

## How progress works

- `POST /api/lessons/{slug}/complete` / `DELETE /api/lessons/{slug}/complete` toggle a
  `LessonCompletion` row for the current user and update `User.lastLessonSlug`.
- `GET /api/progress` returns completed lesson slugs, total lesson count, completion
  percentage, last lesson, and quiz statistics — all computed live, not cached.
- The frontend's `courses` Pinia store keeps completed slugs in memory so the sidebar
  and dashboard update instantly without refetching the whole tree.

## How quiz attempts work

- Each lesson's `Mini Quiz` YAML block becomes a list of `QuizQuestion` DTOs. The
  correct answer index is **never sent to the client** before grading
  (`QuizQuestion::jsonSerialize()` strips it).
- `POST /api/quizzes/{slug}/attempt` grades the submitted answers server-side, stores a
  `QuizAttempt` (score, per-question breakdown, timestamp), and returns the graded
  result with explanations.
- Taking a quiz requires being logged in (attempts are tied to a user); an anonymous
  visitor sees a "Log in to take this quiz" prompt instead.

## Final project: Task Manager

The final course chapter (`backend/courses/final-project/task-manager/`, 12 lessons)
teaches the learner to build a Task Manager — and that Task Manager is **not
hypothetical**: it's the real, working feature in this app (`Task`, `TaskCategory`
entities; `TaskService`; `TaskController`; full CRUD, ownership checks, filtering,
searching, sorting, and pagination via `GET /api/tasks`). The lessons quote and explain
the actual source files, and `/tasks` in the frontend is a fully working UI for it —
try it after logging in.

## API endpoints

| Method         | Path                           | Auth   | Description                                       |
| -------------- | ------------------------------ | ------ | ------------------------------------------------- |
| GET            | `/api/health`                  | public | Liveness check                                    |
| GET            | `/api/courses`                 | public | Full course tree (tracks → chapters → lessons)    |
| GET            | `/api/lessons/{slug}`          | public | One lesson's full structured content              |
| GET            | `/api/search?q=`               | public | Search lessons by title/chapter/content/tags      |
| POST           | `/api/auth/register`           | public | Create an account                                 |
| POST           | `/api/auth/login`              | public | Exchange credentials for a JWT                    |
| GET            | `/api/me`                      | JWT    | Current user                                      |
| GET            | `/api/progress`                | JWT    | Completion %, quiz stats, last lesson             |
| POST/DELETE    | `/api/lessons/{slug}/complete` | JWT    | Mark a lesson complete/incomplete                 |
| POST           | `/api/quizzes/{slug}/attempt`  | JWT    | Submit and grade a quiz attempt                   |
| GET/POST       | `/api/tasks`                   | JWT    | List (filter/search/sort/paginate) / create tasks |
| GET/PUT/DELETE | `/api/tasks/{id}`              | JWT    | Read/update/delete one task (owner-only)          |
| GET            | `/api/categories`              | JWT    | Task categories for the current user              |
| GET            | `/api/stats`                   | JWT    | Task counts by status, for the dashboard          |

## Known limitations

See the final course lesson,
`final-project/task-manager/12-production-readiness-review.md`, for a full breakdown —
in short: no JWT refresh flow, no rate limiting on auth endpoints, dev-permissive CORS,
and SQLite (fine for learning/dev, not for concurrent production writers). These are
deliberate, taught-as-such simplifications, not oversights.
