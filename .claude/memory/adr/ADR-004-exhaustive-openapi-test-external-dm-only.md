# ADR-004 — Exhaustive OpenAPI contract test required only for externally-consumed DMs

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Every DM documents its exposed API (`description` + `example` on each property), external or
internal. Only a DM consumed by an external party additionally requires an exhaustive contract
test (`assertSame` against the full property map) — that is the only place a silent drift is
invisible to the consumer.

## Trade-offs

Maintenance cost of the exhaustive test on every external schema change.

## Rules created

- ALWAYS document every exposed property.
- ALWAYS add an exhaustive property-map test on a DM consumed externally.
