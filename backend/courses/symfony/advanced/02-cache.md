---
slug: cache
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: advanced
chapterTitle: "Chapter 16 — Advanced Concepts"
chapterOrder: 16
title: "Caching for Performance"
difficulty: intermediate
order: 2
estimatedMinutes: 11
tags: [symfony, cache, performance]
---

## Learning Objective

Understand what a **cache** is, how Symfony's Cache component stores and reuses expensive results, and how **cache pools**, keys, and expiration keep cached data correct.

## Explanation

A **cache** is a fast storage layer (memory or files) where your app keeps the results of expensive work — database queries, API responses, rendered fragments — so repeat requests reuse them instead of recomputing. The core trade-off is **freshness versus speed**: cached data answers instantly but may be slightly out of date, so every cached value carries a **TTL (Time To Live)** — a lifetime in seconds after which it expires and is rebuilt on next use.

Symfony's Cache component exposes named **pools** (independent storage areas, each with its own backend and settings, such as `cache.app` for your data and `cache.system` for framework internals). You fetch a pool's **adapter** behavior through the simple `CacheInterface` with its `get($key, $callback)` method: pass a string **key** (a unique name like `task_stats`) and a callback that computes the value on a **cache miss** (when the key is absent or expired). On a **cache hit** (key present and fresh) the callback never runs and the stored value returns immediately. Inside the callback, the `$item->expiresAfter(3600)` call sets the TTL — one hour here — bounding how stale the data can get.

Invalidation (deciding *when* to forget) matters as much as storage. Time-based expiry suits slowly changing data like dashboard counters; event-based invalidation (`$cache->delete('task_stats')` right after a task is created) suits data that must refresh the moment something changes. Choosing keys carefully — including relevant ids and versions — prevents collisions where two different things overwrite each other.

## Why It Matters

Caching is the cheapest performance win available: one `get()` call can turn a 400ms dashboard aggregating thousands of rows into a 5ms lookup. But stale caches also cause infamous bugs ("I updated my profile but the old name still shows"), so professional developers think about keys and invalidation with the same care as the queries themselves. Cache fluency shows up in every performance review and incident postmortem.

## Example

The dashboard shows task statistics (total, completed) computed with heavy aggregate queries. These numbers change only when tasks change, so caching them for ten minutes removes the query cost from nearly every page view.

## Code

```php
<?php

namespace App\Service;

use App\Repository\TaskRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TaskStatsProvider
{
    public function __construct(
        private CacheInterface $cache,
        private TaskRepository $tasks,
    ) {
    }

    public function getStats(): array
    {
        return $this->cache->get('task_stats', function (ItemInterface $item): array {
            $item->expiresAfter(600);

            return [
                'total' => $this->tasks->count([]),
                'done' => $this->tasks->count(['done' => true]),
            ];
        });
    }
}
```

## Code Explanation

- `CacheInterface $cache` injects the default `cache.app` pool — no setup needed for standard use.
- `$this->cache->get('task_stats', $callback)` returns the stored array on a hit, or runs the callback, stores its return, and then returns it on a miss.
- `$item->expiresAfter(600)` bounds staleness to ten minutes: worst case, the dashboard lags reality by that long.
- The callback holds the expensive queries (`count()` aggregates), so they execute at most once per ten minutes instead of on every request.
- Callers use `getStats()` like any plain method — the caching is invisible outside this class, keeping controllers clean.

## Common Mistakes

- Caching per-user data under a shared key (`user_tasks` for everyone). The first user's data then leaks to all others — always include the user id in the key.
- Setting no expiration "for maximum speed." Permanent entries go stale forever; every cached value needs either a TTL or an explicit invalidation path.
- Caching inside controllers with ad-hoc keys scattered everywhere. Centralize cached computations in services like this one so keys and TTLs live in one reviewable place.

## Best Practices

- Build keys from what they represent (`task_stats`, `user_7_tasks`) and keep TTLs proportional to change frequency — minutes for counters, longer for reference data.
- Invalidate on write: delete or refresh affected keys in the same code path that changes the underlying data, not "later."
- Measure before and after (profiler timings, Blackfire, or simple response times) so cache complexity pays for a proven bottleneck rather than a guessed one.

## Exercise

User profile pages show each user's task count, currently recomputed per visit. Name the cache key pattern you would use, state a sensible TTL, and explain where invalidation must happen when a task is added.

## Solution

Use a per-user key like `user_{id}_task_count` (never a shared key) with a TTL of a few minutes (e.g., 300 seconds). Invalidate by deleting that user's key in the task-creation path so the count refreshes immediately on change, with the TTL as a backstop guaranteeing bounded staleness even if a code path forgets.

## Mini Quiz

```yaml
- question: "What does $cache->get('task_stats', $callback) do on a cache hit versus a miss?"
  options:
    - "It always runs the callback and overwrites the stored value"
    - "On a hit it returns the stored value without running the callback; on a miss it runs the callback, stores, and returns the result"
    - "It deletes the key in both cases"
    - "It throws an exception on a miss"
  answer: 1
  explanation: "get() is a read-through helper: fresh stored values short-circuit the expensive computation, while misses rebuild and repopulate the entry."
- question: "What is a TTL and why does every cached value need one (or explicit invalidation)?"
  options:
    - "A TTL is a database index that speeds up cache writes"
    - "TTL (Time To Live) bounds how long a value may serve before expiring; without expiry or invalidation, data goes stale forever"
    - "A TTL encrypts cached values for security"
    - "A TTL compresses values to save disk space"
  answer: 1
  explanation: "Caching trades freshness for speed, so each entry needs a lifetime limit or a delete-on-change path to keep served data acceptably fresh."
- question: "Why must per-user data include the user id in its cache key?"
  options:
    - "Keys without numbers are rejected by Symfony"
    - "A shared key would serve one user's private data to everyone else after the first hit"
    - "Numeric keys render faster in Twig"
    - "The database requires numeric cache keys"
  answer: 1
  explanation: "Cache keys are global within a pool, so user-specific values need user-specific keys to prevent cross-user data leakage."
```
