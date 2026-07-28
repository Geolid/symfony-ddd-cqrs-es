# ADR-003 — Aggregate/Event naming: three segments mirroring the source path

**Date:** 2026-07-28
**Status:** Accepted

## Decision

`#[Aggregate('<subdomain>.<bc>.<aggregate>')]` mirrors the class path
`src/<Subdomain>/<BC>/Domain/<Aggregate>` exactly, even when the aggregate shares its BC's name.
`#[Event]` reuses the same two-segment prefix with the aggregate segment replaced by a
past-tense verb.

## Trade-offs

Longer names in the event store — a minor readability cost when debugging.

## Rules created

- ALWAYS derive the aggregate/event name from the class path — never a shorter ad hoc name.
