# ADR-001 — Event sourcing & cross-BC boundary

**Date:** 2026-07-28
**Status:** Accepted

## Context

Two Bounded Contexts need each other's data, and event sourcing offers no natural boundary between
them: any subscriber can read any stream.

## Decision

A Domain Event is the internal language of its own Bounded Context and is consumed only there. A
Translator republishes what the outside is allowed to know as an Integration Event, and that is the
only path across. Both kinds live in one store, so a cross-BC read is a projection folding the
foreign stream — replayable, eventually consistent, with no synchronous call between contexts.

An aggregate or event name is the store's serialization key, not a label: it changes only together
with an upcaster.

A reaction that mutates state dispatches a Command; one that leaves the system calls its port.
Enrichment reads the write model, never a projection, because a projection may not have caught up.

Erasure of personal data is itself a Domain Event: dropping the subject's key covers every past
event at once. A projection holding clear text redacts on that event, or is rebuilt by replay.

## Alternatives rejected

| Option | Why rejected |
|---|---|
| One store per Bounded Context | a cross-BC read then spans two stores, so either a distributed transaction or an ETL |
| Subscribe to a foreign Domain Event | the producer can no longer refactor its own language |
| Query the other context synchronously | couples availability, and leaves no history to replay |
| Delete personal data in place | an append-only store cannot forget; replay resurrects it |

## Consequences

A foreign attribute that keeps changing costs a nullable column and a handler to keep it current.
Renaming an aggregate or an event is a migration. Every projection holding personal data owns its
own erasure obligation.
