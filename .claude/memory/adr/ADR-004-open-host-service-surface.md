# ADR-004 — A BC's Open Host Service surface

**Date:** 2026-07-30
**Status:** Accepted

## Decision

A Bounded Context exposes itself to a Delivery Mechanism through an Open Host Service made of
exactly two concepts, each with its own marker:

- **Port** — a behaviour the DM invokes: an Application interface carrying `#[AsDrivingPort]`
  (`Shared\Application\Port`).
- **Published Language** — the data and shapes crossing the boundary: anything implementing
  `Shared\Application\Language\PublishedLanguageInterface`. `CommandInterface`, `QueryInterface`,
  `ResultInterface` and `ApplicationExceptionInterface` all extend it, so every concrete message
  is published by inheritance; a validation compound (`Application/Validation/Valid<Name>`) and a
  published vocabulary (`Application/Language/Published<X>`) carry it explicitly.

`Tools\PHPat\DeliveryMechanismTest` allows a DM exactly those two things, with three selectors and
no path or name regex.

Two mechanisms rather than one because PHP does not inherit attributes and phpat's
`Selector::appliesAttribute()` reads direct attributes only: an attribute can mark one named type,
never a family. `Selector::implements()` walks the whole chain, so an interface can. A port is
referenced by the DM under its own name — the attribute is enough and keeps a behaviour grant from
being mistaken for a data grant.

Validating input is an Application concern, so the compounds live in `Application/Validation/` and
`Symfony\Component\Validator` is admitted to `Application.Vendors` — mirroring `Webmozart\Assert`
in `Domain.Vendors`. Without this a DM would have to depend on Infrastructure to validate.

A closed vocabulary a DM has to name (a status, a type) is a `Published<X>` enum in
`Application/Language/` whose cases derive their values from the Domain enum
(`case PENDING = ShipmentStatus::PENDING->value;`). The Domain enum keeps its behaviour and stays
in Domain; the published subset is chosen explicitly, so adding a Domain case leaks nothing.

## Trade-offs

A published case is added by hand — nothing statically enforces that the published enum still
covers what the Domain offers. Status literals in Twig templates and translation catalogs name the
vocabulary outside the reach of both deptrac and phpat. A DM that only needs one Result field still
receives the whole published shape.

## Rules created

- ALWAYS expose behaviour with `#[AsDrivingPort]` and data with `PublishedLanguageInterface` —
  never both on the same concept.
- ALWAYS put a validation compound in `Application/Validation/`, never in Infrastructure.
- NEVER let a DM name a Domain enum: it names the `Published<X>` enum in `Application/Language/`.
