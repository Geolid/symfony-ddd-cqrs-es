# ADR-002 — Boundary enforcement & dependency inversion

**Date:** 2026-07-28
**Status:** Accepted

## Context

Onion layering, isolation between Bounded Contexts, and how far a Delivery Mechanism may reach are
three different questions. Left to convention, they hold only as long as everyone remembers them.

## Decision

Two composable checks, each answering one question. The first maps which Bounded Context a Delivery
Mechanism may reach at all; an undeclared module fails the build, so a new one has to be granted
rather than silently allowed. The second restricts what may be touched inside a context it reached.

A Bounded Context offers a Delivery Mechanism exactly two things: behaviour it may invoke, and
language it may speak. Behaviour is marked as a driving port, language as published; anything
unmarked is internal, and that is the default. A vocabulary a Delivery Mechanism has to name is
published as its own type derived from the Domain one, so extending the Domain does not extend the
contract.

A cross-cutting component may not depend on the context owning the data it needs. It declares a
generic port instead, each owning context implements it, and the dependency turns around into a
direction isolation already allows.

## Alternatives rejected

| Option | Why rejected |
|---|---|
| One check for both questions | reach and exposition need different granularity; either one drowns the other |
| Convention plus review | silent by construction — nothing fails when it is forgotten |
| Expose the Domain type directly | the Domain can then no longer change without breaking a consumer |
| Relax isolation for the cross-cutting component | opens the same door to every other context |

## Consequences

The reach check re-analyzes the isolation ruleset it imports. Configuration written as PHP closures
escapes both checks, as does a vocabulary spelled out in a template or a translation catalog.
Nothing forces a context to implement a cross-cutting port it should.
