# ADR-003 — DM contract testing scope

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Documentation (`description` + `example` on every exposed property) helps any consumer, so it
applies to every DM. An exhaustive contract test (`assertSame` on the full property map) only
pays for itself on a DM consumed externally — that's the only place a schema drift is invisible
to the consumer; on an internal DM the same drift shows up in review.

## Trade-offs

Maintenance cost of the exhaustive test on every external schema change.
