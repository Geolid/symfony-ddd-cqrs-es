# ADR-003 — DM contract testing scope

**Date:** 2026-07-28
**Status:** Accepted

## Context

An exposed schema is a promise. Breaking it costs nothing at build time, and the cost lands on
whoever consumes it.

## Decision

Every Delivery Mechanism documents the properties it exposes, because that helps any consumer.
Only a Delivery Mechanism consumed from outside gets an exhaustive contract test asserting its full
shape — that is the one place a drift reaches production unseen. Anywhere else the same drift is
visible in the diff.

## Alternatives rejected

| Option | Why rejected |
|---|---|
| Exhaustive test on every DM | an internal change pays a test edit that review already caught |
| No contract test anywhere | the external consumer discovers the drift in production |
| Snapshot the whole schema | the diff is unreadable, so it gets approved without being read |

## Consequences

Every external schema change costs a test edit. Whether a Delivery Mechanism is consumed from
outside is a judgement made when it is created.
