---
slug: manytomany-and-cascade-options
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: entity-relationships
chapterTitle: "Chapter 7 — Entity Relationships"
chapterOrder: 7
title: "ManyToMany, Cascade, and Orphan Removal"
difficulty: intermediate
order: 2
estimatedMinutes: 13
tags: [symfony, doctrine, relationships, cascade]
---

## Learning Objective

By the end of this lesson you will be able to model a many-to-many relationship (like `Task` having many `Tag` entities and vice versa) using a join table, understand Doctrine's `cascade` and `orphanRemoval` options, and know what lazy loading means when accessing related collections.

## Explanation

A **many-to-many** relationship is one where many rows on each side can relate to many rows on the other — for example, a task can have several tags ("urgent", "bug"), and each tag can be applied to many tasks. In plain SQL, you'd model this with a **join table** (sometimes called a pivot table) — a `task_tag` table with just two foreign key columns, `task_id` and `tag_id`. Doctrine's `#[ORM\ManyToMany]` attribute models exactly this, and by default creates and manages that join table for you automatically — you never define a `TaskTag` entity for it.

Like `OneToMany`/`ManyToOne`, a `ManyToMany` relationship has an owning side and an inverse side, using the same `mappedBy`/`inversedBy` attributes, but with one extra attribute on the owning side: `#[ORM\JoinTable(name: 'task_tag')]`, which names the join table.

Two more options control what happens automatically when you change a relationship:

- **`cascade`**: tells Doctrine to automatically apply certain operations (most commonly `persist` and `remove`) to related entities. For example, `cascade: ['persist']` on a `Task`'s tags means that if you `persist()` a task with brand-new, not-yet-saved `Tag` objects attached, Doctrine will automatically `persist()` those tags too, instead of you needing to persist each one manually.
- **`orphanRemoval`**: specifically for `OneToMany`/`OneToOne` relationships (not typically `ManyToMany`, since a tag can belong to many tasks and removing it from one task doesn't mean it should be deleted). When `orphanRemoval: true`, removing a related entity from its owning collection (e.g., removing a `Comment` from a `Task`'s comments) causes Doctrine to delete that entity entirely on the next `flush()`, because it's now an "orphan" with no owner.

Finally, **lazy loading**: by default, when Doctrine hydrates an entity, it does *not* immediately query for its related collections (like `$task->getTags()`) or related single entities. Instead, it creates a lightweight placeholder ("proxy") and only runs the actual `SELECT` query the first time you access that property. This is efficient — you don't pay for data you never use — but it has a well-known downside covered in the mistakes section below: the N+1 query problem.

## Why It Matters

Tagging systems, permission/role assignments, and many other real features are naturally many-to-many. Getting `cascade` right avoids repetitive, error-prone manual `persist()` calls for every related object created alongside a parent. Getting `orphanRemoval` right avoids "ghost" rows lingering in the database after they're removed from their logical parent in code. And understanding lazy loading is essential for performance: it explains why looping over 50 tasks and calling `->getCategory()->getName()` on each can silently issue 51 queries instead of 1 or 2, if you're not careful — a very real, very common Doctrine performance trap.

## Example

Modeling tags on tasks: many tasks can share a tag, and a task can have many tags — a classic many-to-many, with a join table Doctrine manages for you.

```php
$task->addTag($urgentTag);
$task->addTag($bugTag);
$entityManager->persist($task);
$entityManager->flush(); // writes rows into the task_tag join table
```

## Code

```php
<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private string $label;

    /** @var Collection<int, Task> */
    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]
    private Collection $tasks;

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
```

```php
<?php
// Excerpt from App\Entity\Task — the owning side of the ManyToMany relationship

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks', cascade: ['persist'])]
#[ORM\JoinTable(name: 'task_tag')]
private Collection $tags;

// initialized in the constructor: $this->tags = new ArrayCollection();

public function getTags(): Collection
{
    return $this->tags;
}

public function addTag(Tag $tag): static
{
    if (!$this->tags->contains($tag)) {
        $this->tags->add($tag);
    }

    return $this;
}

public function removeTag(Tag $tag): static
{
    $this->tags->removeElement($tag);

    return $this;
}
```

## Code Explanation

- `#[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'tags')]` on `Tag::$tasks` is the inverse side — `mappedBy: 'tags'` points back to the `Task::$tags` property, which owns the relationship.
- `#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks', cascade: ['persist'])]` on `Task::$tags` is the owning side. `inversedBy: 'tasks'` keeps both in-memory collections in sync; `cascade: ['persist']` means that persisting a `Task` will automatically also persist any brand-new `Tag` objects attached to it that haven't been saved yet.
- `#[ORM\JoinTable(name: 'task_tag')]` explicitly names the join table Doctrine manages; without it, Doctrine derives a default name.
- `addTag()`/`removeTag()` are deliberately written as small helper methods rather than exposing the raw `Collection` for direct mutation — `contains()` avoids adding a duplicate, and `removeElement()` cleanly detaches a tag.
- Note there is no `orphanRemoval` here: removing a tag from one task's `$tags` collection should never delete the `Tag` entity itself, since other tasks may still reference it — `orphanRemoval` is reserved for genuinely "owned" child entities like comments, which don't make sense to exist detached from their parent.

## Common Mistakes

- **The N+1 query problem**: looping over a list of tasks and accessing `$task->getCategory()->getName()` (or any lazy-loaded relation) inside the loop triggers one extra query *per task*, because each relation is lazily loaded the first time it's touched — 50 tasks means 1 query for the tasks plus 50 more for their categories. The fix (covered practically as you build real features) is to eagerly fetch related data in the original query, e.g. with a `JOIN` in the QueryBuilder, when you know you'll need it.
- **Using `orphanRemoval: true` on a ManyToMany.** It's designed for exclusive parent/child ownership (`OneToMany`), not shared relationships — applying it to tags could delete a `Tag` still used by other tasks.
- **Forgetting `cascade: ['persist']` and then manually persisting every new related entity.** Not wrong, just repetitive and easy to forget in one code path while remembering it in another — inconsistent cascade usage across a codebase is a common source of confusion.
- **Not guarding against duplicates in `addTag()`.** Directly calling `$this->tags->add($tag)` without checking `contains()` first can add the same tag twice into the in-memory collection before a flush deduplicates via the join table's composite key — better to guard explicitly.

## Best Practices

- Use `cascade: ['persist']` for relationships where "child" objects are naturally created alongside their parent and would be tedious to persist separately; avoid blanket cascading `remove` unless you're certain child entities should never outlive the parent.
- Reserve `orphanRemoval: true` for true ownership relationships (e.g., a `Task`'s `Comment`s), never for shared many-to-many associations like tags.
- When you know you'll need a related collection for every item in a list (avoiding N+1), fetch it eagerly using a QueryBuilder `JOIN`, rather than relying on lazy loading inside a loop.
- Keep collection mutation behind small, intention-revealing methods (`addTag`, `removeTag`) instead of exposing the raw `Collection` for arbitrary external mutation.

## Exercise

A `Task` should be able to have multiple `Comment` entities, where each comment belongs to exactly one task, and deleting a comment from a task's comment list should delete that comment entirely (comments never exist independently of a task). Describe which relationship attribute you'd use, and whether `orphanRemoval` should be enabled — and why.

## Solution

This should be modeled as a `OneToMany`/`ManyToOne` relationship (not `ManyToMany`), because each `Comment` belongs to exactly one `Task` — the same shape as `Task`/`TaskCategory` from the previous lesson. On the `Task` side:

```php
/** @var Collection<int, Comment> */
#[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'task', orphanRemoval: true)]
private Collection $comments;
```

`orphanRemoval: true` is appropriate here because a `Comment` has no meaning detached from its `Task` — if it's removed from the task's `$comments` collection (e.g., `$task->getComments()->removeElement($comment)`), Doctrine will delete that `Comment` row entirely on the next `flush()`, rather than leaving an orphaned row with a dangling reference. This is different from the `Tag` example, where a tag removed from one task must still exist for other tasks.

## Mini Quiz

```yaml
- question: "How does Doctrine typically store a ManyToMany relationship in the database?"
  options:
    - "As a nullable foreign key column on one of the two entities' tables"
    - "Using a join table with two foreign key columns, managed automatically by Doctrine"
    - "As a JSON array column"
    - "It cannot be stored in a relational database"
  answer: 1
  explanation: "ManyToMany relationships use a join/pivot table with foreign keys to both sides; Doctrine creates and manages this table automatically based on the #[ORM\\JoinTable] mapping."
- question: "What does cascade: ['persist'] do on a relationship?"
  options:
    - "It automatically deletes related entities when the parent is deleted"
    - "It automatically persists newly created, related entities when the owning entity is persisted, without needing a separate persist() call for each"
    - "It prevents the relationship from ever being changed"
    - "It makes the relationship load eagerly instead of lazily"
  answer: 1
  explanation: "cascade: ['persist'] means calling persist() on the entity also persists any new, not-yet-saved related entities attached to it."
- question: "Why is orphanRemoval generally not appropriate for a ManyToMany tags relationship?"
  options:
    - "orphanRemoval only works with integers"
    - "A tag can be shared by multiple tasks, so removing it from one task's collection should not delete the Tag entity, which may still be referenced elsewhere"
    - "orphanRemoval is only valid for ManyToOne relationships"
    - "It would cause a database error"
  answer: 1
  explanation: "orphanRemoval assumes exclusive ownership — removing the child from its one parent's collection means it should be deleted. A shared Tag can belong to many tasks, so that assumption doesn't hold."
- question: "What is lazy loading, and what real problem can it cause if you're not careful?"
  options:
    - "Doctrine loads all data eagerly by default, which can slow down small requests; there's no downside to lazy loading"
    - "Related entities/collections are only queried when first accessed; looping over many parent entities and accessing a relation on each can trigger one extra query per item (the N+1 problem)"
    - "Lazy loading means data is cached forever after the first request"
    - "Lazy loading only applies to ManyToMany relationships, never to ManyToOne"
  answer: 1
  explanation: "Lazy loading defers fetching a related entity/collection until it's actually accessed. Accessing it inside a loop over many parents causes one query per parent — the classic N+1 problem."
```
