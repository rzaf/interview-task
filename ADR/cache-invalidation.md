# ADR: Cache Invalidation for /api/orders

- Candidate: CAND-NT1
- Date: 2026-07-23

## Context
Briefly describe the API, data freshness needs, and traffic patterns.


When users look at their order history, they use different filters like pages (page=2), page sizes (per_page=50), or date ranges (start=2026-01-01). This creates dozens of different cache keys for just one user.If a user buys something new, they expect to see it in their history right away. If the cache stays active, they will think their order failed. We need a way to clear all variations of a user's cache immediately when they make a purchase.

## Options Considered
1) TTL-based cache (per key)
2) Tag-based cache invalidation
3) Event-driven invalidation on write (pub/sub)
4) No cache (DB + indexes only)

## Decision
We choose: ...

We will save a simple version counter for each user (e.g., user_123_version = 1). Every time we save an order history cache, we put that number inside the filename.When the user places a new order, we change their counter to 2. The system will automatically look for filenames containing version 2. Because the old files contain version 1, they are instantly ignored and bypassed.



## Rationale (Criteria)
- Simplicity
- Staleness risk
- DB offload
- Deployment/ops complexity
- Failure modes

- Simplicity: High. It avoids complex file system crawling or external infrastructure additions
- Staleness risk: Zero. Invalidation happens instantly on mutate operations, giving users real-time accurate lists.
- DB offload: High. Repetitive pagination requests during session browsing hitting identical filters avoid the DB entirely.
- Deployment/ops complexity: Low. It operates within the current single-server file architecture.
- Failure modes: If the generation identifier fails to read, we safely drop back to an un-cached database query.

## When NOT to use this choice (Anti-case)

Do not use this approach if the order system allows massive bulk updates affecting thousands of users simultaneously (e.g., global admin status changes). Such operations would trigger massive parallel cache thundering herds.

## Rollback Plan

If tracking generational versions introduces unexpected race conditions, we can instantly fall back to a dumb short-term TTL (Option 1) by stripping out the $version variable from the cache key builder and setting a 5–10 second expiration wrapper.


## Implementation Notes
Keys, TTLs, tags/topics, invalidation triggers.


Key Schema: orders:u{userId}:v{userVersion}:p{page}:per{per}:s{start}:e{end}Version Key: user_version:u{userId}TTL: 1 hour (3600 seconds), since data is guaranteed fresh until a write clears the generation pointer.Triggers: Any file executing a INSERT, UPDATE, or DELETE on the orders or payments table for a specific user must call cache_invalidate_user($userId).