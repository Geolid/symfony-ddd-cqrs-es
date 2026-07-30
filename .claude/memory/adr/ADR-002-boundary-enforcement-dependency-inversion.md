# ADR-002 — Boundary enforcement & dependency inversion

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Two composable checks enforce BC/DM isolation. deptrac maps which BC each DM may reach
(`deptrac_dm.yaml` imports `deptrac_bc.yaml`; `--fail-on-uncovered` forces every new module to
be declared). phpat (a PHPStan extension under `tools/PHPat/`) enforces what a BC exposes inside
the BCs deptrac grants.

A BC exposes itself through two concepts, each with its own marker: behaviour is an Application
interface carrying `#[AsDrivingPort]` (`Shared\Application\Port`); data and shapes are
`PublishedLanguageInterface` (`Shared\Application\Language`), extended by `CommandInterface`,
`QueryInterface`, `ResultInterface` and `ApplicationExceptionInterface`, and carried explicitly by
a validation compound (`Application/Validation/Valid<Name>`) and a published vocabulary
(`Application/Language/Published<X>`). `Tools\PHPat\DeliveryMechanismTest` allows exactly those, in
three selectors with no path or name regex. Two markers rather than one because PHP does not
inherit attributes and phpat's `appliesAttribute()` reads direct attributes only — an attribute
marks one named type, never a family, whereas `implements()` walks the chain.

Validation is an Application concern, so the compounds sit in `Application/Validation/` and
`Symfony\Component\Validator` is admitted to `Application.Vendors`, as `Webmozart\Assert` is to
`Domain.Vendors`. A closed vocabulary a DM has to name derives its values from the Domain enum
(`case PENDING = ShipmentStatus::PENDING->value;`): the Domain enum keeps its behaviour, and the
published subset is chosen case by case, so a new Domain case leaks nothing.

When a cross-cutting `Shared` component needs data owned by a specific BC, it cannot depend on
that BC directly (`deptrac_bc.yaml` forbids it). The fix is dependency inversion: `Shared`
declares a generic, auto-tagged port; each owning BC implements it in its own Infrastructure;
the `Shared` component iterates every tagged provider and merges the non-null results. The
dependency then runs BC → Shared, already allowed.

## Trade-offs

`deptrac-dm` re-analyzes the imported BC ruleset. Config closures under `apps/*/config/**.php`
are outside both tools' reach. Nothing enforces that a published vocabulary still covers what the
Domain offers, and status literals in Twig templates and translation catalogs escape both tools.
Provider completeness depends on each BC remembering to implement the port — a convention, not an
enforced contract.

## Rules created

- ALWAYS: expose behaviour with `#[AsDrivingPort]` and data with `PublishedLanguageInterface`; put
  a validation compound in `Application/Validation/`; implement a cross-cutting port in the owning
  BC's Infrastructure.
- NEVER: let a DM name a Domain enum — it names `Application/Language/Published<X>`; add an
  explicit BC→Shared dependency to feed a cross-cutting component — discovery is by auto-tagging
  only.
