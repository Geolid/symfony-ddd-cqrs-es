# ADR-001 — Domain Events sealed per BC; Integration Events are the sole cross-BC contract

**Date:** 2026-07-28
**Status:** Accepted

## Decision

A Domain Event is consumed only within its own Bounded Context (its own Projectors, its own
Translator) — enforced by a generated phpat rule per BC. Any other consumer, in another BC or a
side effect, reacts to the Integration Event instead.

Domain and Integration Events share a single event store. A Translator subscribes to the
producer's Domain Events and appends the matching Integration Event under a correlation id. A
composite Projector subscribes to its own Domain Events plus foreign Integration Events, folding
the foreign stream at fold time (a snapshot bounded by the current message index) to materialize
a column, with one fan-out handler per foreign attribute that can change over time.

## Trade-offs

A foreign attribute that changes over time costs a nullable column plus one fan-out handler.
The fold is a bounded snapshot — later changes arrive only through the fan-out.

## Rules created

- ALWAYS: translate and persist Domain→Integration through the Translator; enrich cross-BC by
  folding the foreign stream, never by reading a foreign aggregate or projection.
- NEVER: publish an Integration Event from Application; let a failed subscriber skip silently.
