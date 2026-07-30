# ADR-002 — Boundary enforcement & dependency inversion

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Two composable checks enforce BC/DM isolation. deptrac maps which BC each DM may reach
(`deptrac_dm.yaml` imports `deptrac_bc.yaml`; `--fail-on-uncovered` forces every new module to
be declared). phpat (a PHPStan extension under `tools/PHPat/`) enforces what a BC exposes inside
the BCs deptrac grants.

A BC exposes exactly two things. Behaviour: an Application interface carrying `#[AsDrivingPort]`
(`Shared\Application\Port`). Data and shapes: `PublishedLanguageInterface`
(`Shared\Application\Language`), extended by `CommandInterface`, `QueryInterface`,
`ResultInterface` and `ApplicationExceptionInterface`, carried explicitly by a validation compound
(`Application/Validation/Valid<Name>`) and a published vocabulary
(`Application/Language/Published<X>`).

A published vocabulary derives its values from the Domain enum
(`case PENDING = ShipmentStatus::PENDING->value;`), so a new Domain case leaks nothing.

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
