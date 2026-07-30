# ADR-004 — A BC's Open Host Service surface

**Date:** 2026-07-30
**Status:** Accepted

## Decision

A BC exposes itself to a Delivery Mechanism through two concepts, each with its own marker:
behaviour is an Application interface carrying `#[AsDrivingPort]` (`Shared\Application\Port`);
data and shapes are `PublishedLanguageInterface` (`Shared\Application\Language`), extended by
`CommandInterface`, `QueryInterface`, `ResultInterface` and `ApplicationExceptionInterface`, and
carried explicitly by a validation compound (`Application/Validation/Valid<Name>`) and a published
vocabulary (`Application/Language/Published<X>`). `Tools\PHPat\DeliveryMechanismTest` allows
exactly those, in three selectors with no path or name regex.

Two markers rather than one because PHP does not inherit attributes and phpat's
`appliesAttribute()` reads direct attributes only — an attribute marks one named type, never a
family, whereas `implements()` walks the chain.

Validation is an Application concern, so the compounds sit in `Application/Validation/` and
`Symfony\Component\Validator` is admitted to `Application.Vendors`, as `Webmozart\Assert` is to
`Domain.Vendors`.

A closed vocabulary a DM has to name derives its values from the Domain enum
(`case PENDING = ShipmentStatus::PENDING->value;`): the Domain enum keeps its behaviour, and the
published subset is chosen case by case, so a new Domain case leaks nothing.

## Trade-offs

Nothing enforces that the published vocabulary still covers what the Domain offers. Status
literals in Twig templates and translation catalogs escape both deptrac and phpat.

## Rules created

- ALWAYS expose behaviour with `#[AsDrivingPort]` and data with `PublishedLanguageInterface`.
- ALWAYS put a validation compound in `Application/Validation/`.
- NEVER let a DM name a Domain enum — it names `Application/Language/Published<X>`.
