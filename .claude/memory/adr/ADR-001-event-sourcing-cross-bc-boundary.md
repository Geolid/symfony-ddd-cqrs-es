# ADR-001 — Event sourcing & cross-BC boundary

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

`#[Aggregate]`/`#[Event]` names mirror the class path exactly (`<subdomain>.<bc>.<aggregate>`,
three segments even when the aggregate shares its BC's name) — the name is the event store's
serialization key, so renaming it without an upcaster in the same change makes every existing
stream undeserializable on replay.

A reaction inside a BC listens to its own Domain Event; a reaction crossing a BC listens to the
Integration Event. Enrichment reads the Repository only, never a Finder — a projection is
eventually consistent, the Repository replays the true state. A side effect dispatches a Command
only when it mutates the write model; an outbound effect (notification, external call) calls its
port directly.

Erasure of personal data is a Domain Event implementing an erasure marker, carrying the subject
id. Dropping the subject's encryption key covers every past event for that subject implicitly. A
projection holding personal data in clear text redacts on erasure through its own fan-out, or is
rebuilt by replay. An effect reading personal data and the erasure of that data never react to
the same event — the reader records the erasure event itself once done, so causality guarantees
erasure follows the read.

## Trade-offs

A foreign attribute that changes over time costs a nullable column plus one fan-out handler. The
fold is a bounded snapshot — later changes arrive only through the fan-out. Longer event-store
names, minor readability cost when debugging. Each projection holding personal data owns its own
erasure obligation.

## Rules created

- ALWAYS: translate and persist Domain→Integration through the Translator; enrich cross-BC by
  folding the foreign stream; intra-BC reaction on Domain Event, cross-BC on Integration Event;
  mutating effect → Command, outbound effect → port; erasure marker on the Domain Event.
- NEVER: publish an Integration Event from Application; enrich from a Finder; a Command for a
  non-mutating effect; erasure via Integration Event; orchestrate replay from the eraser.
