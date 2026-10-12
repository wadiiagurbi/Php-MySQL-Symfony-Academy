---
slug: what-is-a-database
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: database-basics
chapterTitle: "Chapter 1 — Database Basics"
chapterOrder: 1
title: "What is a Database?"
difficulty: beginner
order: 1
estimatedMinutes: 8
tags: [mysql, databases, basics]
---

## Learning Objective

By the end of this lesson, you will understand what a database is, why applications need one, and what MySQL is. You will also meet the "online store" example project that we will build up, lesson by lesson, throughout this entire MySQL track.

## Explanation

A **database** is an organized collection of information (we call it "data") that a computer program can store, search through, and update reliably. Think about a school notebook where a teacher writes down every student's name, grade, and attendance. That notebook is a very simple "database" — except it is on paper, so it is slow to search, easy to damage, and only one person can use it at a time.

A computer database solves exactly those problems. It stores the same kind of information, but electronically, in a way that many programs (or many users at once) can read and change safely and quickly, even if there are millions of records.

Most databases organize data into **tables**. A table is a lot like a spreadsheet: it has columns (categories of information, like "Name" or "Email") and rows (one entry per person, product, or order). We will look at tables in detail in the next lesson — for now, just picture an Excel sheet.

**MySQL** is one specific piece of software that stores and manages databases. It belongs to a family called **RDBMS** (Relational Database Management System) — "relational" because data is organized into related tables, the way we just described. MySQL is free, extremely widely used (Facebook, YouTube, and thousands of other companies rely on it), and it understands a language called **SQL** (Structured Query Language), which is how you ask it to store, find, or change data. You will learn SQL starting in Chapter 2 of this track.

Other RDBMS products exist too — PostgreSQL, SQLite, Microsoft SQL Server — but they all speak a very similar dialect of SQL, so what you learn here transfers easily.

## Why It Matters

Almost every real web application — a blog, an online store, a banking app, a food delivery service — needs to remember information between visits: user accounts, articles, products, orders, comments. If that information only lived in your PHP script's variables, it would disappear the moment the script finished running or the server restarted. A database is the permanent, shared memory of your application.

Professionally, knowing how to design tables and write SQL is considered a core skill for any backend developer, including PHP and Symfony developers. Symfony's own database tool, called **Doctrine**, ultimately generates and runs the same kind of SQL you are about to learn — so understanding SQL directly makes you a far more effective Symfony developer, and helps you debug problems Doctrine cannot hide from you.

## Example

Imagine you are building an online store. Without a database, every time a customer refreshed the page, your list of products would vanish. With a database, you can:

- Store every product name, price, and stock quantity permanently.
- Let thousands of customers browse the same product list at the same time.
- Record every order so it can be looked up next week, next year, or during a dispute.

Throughout this MySQL track, we will design and gradually extend one running example: a small **online store** database. It will eventually contain a `users` table (customers), a `categories` table (like "Electronics" or "Books"), a `products` table, and tables to track `orders`. Every later lesson builds on this same story, so by the end of the track you will have designed and queried a realistic mini e-commerce database.

## Code

```sql
-- Ask the MySQL server what database software version it is running.
-- This is often the very first command people run to confirm MySQL is working.
SELECT VERSION();

-- Create the database that will hold our entire "online store" example.
CREATE DATABASE online_store;

-- Tell MySQL: "from now on, run my commands against online_store."
USE online_store;
```

## Code Explanation

- `SELECT VERSION();` is a tiny SQL command that asks the MySQL server to report its own version number. It doesn't touch any of your data — it's just a sanity check that the server is alive and reachable.
- `CREATE DATABASE online_store;` creates a brand-new, empty database named `online_store`. At this point it has no tables yet — it's like creating a new, empty folder on your computer before you put any files in it.
- `USE online_store;` switches your current session so that any command you type afterward (like creating a table) applies inside `online_store`, rather than some other database that might also exist on the same server.

## Common Mistakes

- **Confusing "database" with "table."** A database is the whole container (the folder); a table is one organized list inside it (one file/spreadsheet). A single database can — and usually does — hold many tables.
- **Forgetting to run `USE your_database;`.** If you skip this, your next commands may fail with an error like "No database selected," or worse, accidentally run against the wrong database if one was already selected.
- **Assuming SQL is only for "big" applications.** Even a tiny personal project benefits from a real database instead of storing data in plain text files, because a database prevents data corruption and lets you search efficiently.

## Best Practices

- Give databases clear, descriptive, lowercase names with underscores (e.g., `online_store`, not `DB1` or `Test`).
- Always double check which database you're connected to before running commands that change data — especially on a shared or production server.
- Keep a mental (or written) model of what your database is "for" — in our case, everything related to running a small online store — so tables stay organized and purposeful rather than becoming a random dumping ground.

## Exercise

Using the MySQL command line or a tool like phpMyAdmin/MySQL Workbench, write the SQL statements to:

1. Create a new database called `online_store` (if you haven't already).
2. Switch to using that database.
3. Run a command to confirm which database is currently selected.

(Hint for step 3: look up the `SELECT DATABASE();` function.)

## Solution

```sql
CREATE DATABASE online_store;
USE online_store;
SELECT DATABASE();
```

Running `SELECT DATABASE();` after `USE online_store;` should return `online_store`, confirming that any further commands will apply to the correct database.

## Mini Quiz

```yaml
- question: "What is a database, in plain terms?"
  options:
    - "A single spreadsheet cell"
    - "An organized collection of data that a program can store, search, and update reliably"
    - "A programming language"
    - "A type of website design"
  answer: 1
  explanation: "A database is an organized collection of data, designed for reliable, fast storage and retrieval — not a language or a design style."

- question: "What is MySQL?"
  options:
    - "A frontend JavaScript framework"
    - "A relational database management system (RDBMS) that stores and manages databases using SQL"
    - "A type of table inside a spreadsheet"
    - "A PHP function for formatting dates"
  answer: 1
  explanation: "MySQL is an RDBMS — software that stores relational databases and lets you query them with SQL."

- question: "In this track's running example, what does the online_store database represent?"
  options:
    - "A single table with no columns"
    - "A social media app"
    - "A small e-commerce system with users, categories, products, and orders"
    - "A file storage system unrelated to SQL"
  answer: 2
  explanation: "We will build up a small online store schema with users, categories, products, and orders across the lessons in this track."
```
