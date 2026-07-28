# ADR-005 — Reaction model: side effects, enrichment, personal-data erasure

**Date:** 2026-07-28
**Status:** Accepted

## Decision

- Integration Event is for cross-BC reactions only. A reaction inside a BC listens to its own
  Domain Event; a reaction crossing a BC listens to the producer's Integration Event.
- Enrichment reads the Repository only, never a Finder — a projection is eventually consistent,
  the Repository replays the true state. Data needed exact-at-the-moment travels on a fat
  Domain Event instead.
- A side effect dispatches a Command only when it mutates the write model; an outbound effect
  (notification, external call) calls its port directly.
- Erasure of personal data is a Domain Event implementing an erasure marker, carrying the
  subject id. Dropping the subject's encryption key covers every past event for that subject
  implicitly. A projection holding personal data in clear text redacts on erasure through its
  own fan-out, or is rebuilt by replay — the eraser never orchestrates that.
- An effect reading personal data and the erasure of that data never react to the same event —
  subscription order isn't contractual. The reader records the erasure event itself once done,
  so causality guarantees erasure follows the read.

## Trade-offs

Two reaction shapes (Domain-event enrichment vs. Integration Event) share one error model. Each
projection holding personal data owns its own erasure obligation.

## Rules created

- ALWAYS: intra-BC reaction on Domain Event, cross-BC on Integration Event; enrich from
  Repository; mutating effect → Command, outbound effect → port; erasure marker on the Domain
  Event; erasure recorded after the last read of that subject's data.
- NEVER: Integration Event intra-BC; enrich from a Finder; a Command for a non-mutating effect;
  erasure via Integration Event; orchestrate replay from the eraser.
