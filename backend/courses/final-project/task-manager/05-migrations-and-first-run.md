---
slug: migrations-and-first-run
track: final-project
trackTitle: "Final Project — Task Manager"
trackOrder: 4
chapter: task-manager
chapterTitle: "Building the Task Manager"
chapterOrder: 1
title: "Migrations and First Run"
difficulty: advanced
order: 5
estimatedMinutes: 10
tags: [final-project, task-manager, migrations, doctrine]
---

## Learning Objective

By the end of this lesson, you will be able to generate and run the migration that creates this project's schema, explain why it defaults to SQLite, and switch `DATABASE_URL` to MySQL for a production-like environment.

## Explanation

Once the entities from the last two lessons exist as PHP classes, Doctrine still needs to translate them into actual database DDL. That's the job of `doctrine:migrations:diff` (compare current entity mappings against the current schema and generate a migration file) followed by `doctrine:migrations:migrate` (execute the pending migration(s) against the configured database).

This project's real migration, `Version20260918110843`, was generated exactly that way. Its `up()` method creates `app_user`, `task_category`, and `task` (plus two tables — `lesson_completion` and `quiz_attempt` — that belong to the platform's course-tracking feature, not the Task Manager; a single Doctrine schema can serve more than one feature of an app). Its `down()` method is the mirror image: a clean `DROP TABLE` for everything `up()` created, so the migration can be rolled back.

By default, this project's `.env` points at SQLite:

```
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

SQLite is a deliberate choice for local development and running the test suite: it's a single file, needs no separate server process, and resets instantly — exactly what you want while iterating on entities and running `ApiTestCase`, which drops and recreates the whole schema before every single test method. Committing to SQLite forever would be a mistake, though: it doesn't support everything a production MySQL deployment needs (concurrent writes at scale, certain column types, replication). The `.env` file already shows the commented-out alternative:

```
# DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
```

Switching environments is a one-line change — uncomment the MySQL line (with real credentials), comment out the SQLite line, and re-run the migration against the new connection. Doctrine's DBAL abstracts away most of the SQL dialect differences, so the same migration file (mostly) works against both, though generated SQL for a fresh `diff` will differ per platform.

## Why It Matters

This SQLite-for-dev, MySQL-for-production split is extremely common in real Symfony teams — it trades a little bit of "am I 100% sure this behaves identically on both engines" risk for a much faster local feedback loop and a test suite that doesn't need a running database server in CI. Understanding *why* a migration file looks the way it does (matching every entity attribute you wrote in the previous two lessons) is what lets you trust generated migrations instead of treating them as opaque output.

## Example

The end-to-end first-run sequence for this project:

```bash
php bin/console doctrine:migrations:diff       # generate a migration from current entity mappings
php bin/console doctrine:migrations:migrate     # apply pending migrations to the configured database
php bin/console doctrine:migrations:status      # confirm everything is up to date
```

Running the functional test suite doesn't even require this step manually — `ApiTestCase::setUp()` calls Doctrine's `SchemaTool` directly to drop and recreate the schema from the current entity metadata before each test, bypassing migrations entirely for speed. Migrations exist for real deployments; tests build the schema straight from the mapping.

## Code

```php
<?php
// Adapted from migrations/Version20260918110843.php — the task-related
// portion of the real generated migration for this project.

final class Version20260918110843 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, display_name VARCHAR(120) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_lesson_slug CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON app_user (email)');

        $this->addSql('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(140) NOT NULL, description CLOB DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(10) NOT NULL, due_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INTEGER NOT NULL, category_id INTEGER DEFAULT NULL, CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_527EDB2512469DE2 FOREIGN KEY (category_id) REFERENCES task_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_527EDB257E3C61F9 ON task (owner_id)');
        $this->addSql('CREATE INDEX IDX_527EDB2512469DE2 ON task (category_id)');

        $this->addSql('CREATE TABLE task_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_468CF38D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_owner_name ON task_category (owner_id, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_category');
    }
}
```

## Code Explanation

Every line here traces directly back to an entity attribute from the previous two lessons: `VARCHAR(140) NOT NULL` for `title` matches `#[ORM\Column(length: 140)]` plus `#[Assert\NotBlank]`'s implied non-emptiness at the application layer (the column itself is `NOT NULL` because the PHP property type is `string`, not `?string`). `owner_id INTEGER NOT NULL ... ON DELETE CASCADE` is the SQL rendering of `JoinColumn(nullable: false, onDelete: 'CASCADE')`. `CREATE UNIQUE INDEX uniq_category_owner_name ON task_category (owner_id, name)` is exactly the composite constraint discussed in the database design lesson, now as real DDL. Note `INTEGER PRIMARY KEY AUTOINCREMENT` — this is SQLite-specific syntax; a MySQL-targeted `diff` would generate `INT AUTO_INCREMENT PRIMARY KEY` instead, which is why migrations are typically regenerated (or written per-platform) rather than blindly reused across database engines.

## Common Mistakes

- Editing entity mappings and forgetting to run `doctrine:migrations:diff` afterward — the database schema silently drifts out of sync with what the PHP code expects, and Doctrine only discovers the mismatch at query time.
- Hand-editing a generated migration's SQL without testing it against a fresh database, risking a migration that "diffs clean" in development but fails on a production database with existing data.
- Assuming a migration generated against SQLite will run unmodified against MySQL — column type syntax and autoincrement syntax differ between platforms.

## Best Practices

- Always run `doctrine:migrations:diff` right after changing an entity, and read the generated SQL before running `migrate` — never assume it's exactly what you intended.
- Keep migrations in version control and treat them as append-only history: never edit a migration that has already been applied anywhere outside your own machine; write a new one instead.
- Use SQLite (or another lightweight engine) for fast local iteration and tests, but validate against the real production database engine (MySQL here) before shipping, since subtle SQL dialect differences can surface late.

## Exercise

You've just added the `TaskPriority::URGENT` case from the previous lesson's exercise, and confirmed no column-length change is needed. What command would you run to confirm Doctrine agrees no migration is necessary, and what command would you run if you *did* need a schema change (e.g. widening a column)?

## Solution

Run `php bin/console doctrine:migrations:diff` — since the enum's backing values are still `VARCHAR(10)`-compatible strings, Doctrine should generate an empty (or no) migration, confirming no schema change is needed for that particular edit. If a real schema change were needed (say, widening `task.title` from `VARCHAR(140)` to `VARCHAR(255)`), the same `doctrine:migrations:diff` command would detect the mapping change and generate a migration with an `ALTER TABLE task CHANGE title VARCHAR(255) NOT NULL` (MySQL) or equivalent statement, which you'd then review and run through `doctrine:migrations:migrate`.

## Mini Quiz

```yaml
- question: "Why does ApiTestCase use Doctrine's SchemaTool instead of running real migrations before each test?"
  options:
    - "Migrations don't work in the test environment"
    - "SchemaTool builds the schema directly from current entity metadata, which is faster and always in sync with the code being tested"
    - "SchemaTool is required by PHPUnit"
    - "Migrations can only be run once per database"
  answer: 1
  explanation: "SchemaTool::createSchema builds tables straight from the entity mapping metadata, skipping the overhead and drift risk of maintaining migration history just for test runs."

- question: "What must change in .env to switch this project from SQLite to MySQL?"
  options:
    - "Nothing — Doctrine auto-detects the database engine"
    - "The DATABASE_URL value, from a sqlite:// DSN to a mysql:// DSN with real credentials"
    - "The entity mappings must be rewritten for MySQL"
    - "All migrations must be deleted and regenerated"
  answer: 1
  explanation: "Doctrine DBAL picks its SQL dialect from the DATABASE_URL scheme, so swapping the sqlite:// line for the commented-out mysql:// line (with real credentials) is the only required change to target MySQL."

- question: "Why is ON DELETE CASCADE rendered directly in the migration's SQL for task.owner_id, rather than being enforced only in PHP?"
  options:
    - "PHP-level cascades are always sufficient and the SQL is redundant"
    - "So the database itself enforces cleanup even for deletes that bypass Doctrine entirely (e.g. a raw SQL client)"
    - "Because SQLite ignores foreign key constraints"
    - "Because Doctrine cannot express cascades in entity attributes"
  answer: 1
  explanation: "Putting the cascade in the actual foreign key definition means referential integrity holds no matter how a row is deleted, not just when Doctrine's own remove()/flush() is used."
```
