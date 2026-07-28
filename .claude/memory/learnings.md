# Learnings

Patterns observed while working on this project — what worked, what surprised, what we'll
repeat.

## Index

| # | Pattern | Context | Date |
|---|---------|---------|------|
| [LRN-001](#lrn-001) | Webhook routing keys on a URL segment, not the event's own type | Webhook dispatch | 2026-07-28 |
| [LRN-002](#lrn-002) | `php_unit_strict` rewrites `assertEquals` to `assertSame` regardless of semantics | PHP-CS-Fixer risky rule | 2026-07-28 |

## LRN-001

Webhook dispatch resolves on the `webhook.routing` key (a URL segment), matched against the
`#[AsRemoteEventConsumer]` argument — never the remote event's own `eventType()`, which is
metadata only. An unmatched key is a hard `LogicException` (the transport is synchronous). A
routing alias is one more DI tag under the alias key, never a change to `eventType()`.

## LRN-002

`php_unit_strict` mechanically converts `assertEquals` to `assertSame` without checking value
vs. identity semantics — it breaks a test comparing Value Objects by value, since freshly
constructed instances are equal in value but never identical. Before accepting such a diff on a
VO/object comparison, check whether the test should instead assert through the VO's own
`equals()`.
