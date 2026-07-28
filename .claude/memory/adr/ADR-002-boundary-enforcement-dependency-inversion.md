# ADR-002 — Boundary enforcement & dependency inversion

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Two composable checks enforce BC/DM isolation. deptrac maps which BC each DM may reach
(`deptrac_dm.yaml` imports `deptrac_bc.yaml`; `--fail-on-uncovered` forces every new module to
be declared). phpat (a PHPStan extension under `tools/PHPat/`) enforces how a BC exposes its
interface: driving ports (`#[AsDrivingPort]`, a pure marker with zero DI effect, never applied
to a Command/Query), structural messages (Command/Query/Result/ApplicationException), and
validation compounds (`Infrastructure/Validation`).

When a cross-cutting `Shared` component needs data owned by a specific BC, it cannot depend on
that BC directly (`deptrac_bc.yaml` forbids it). The fix is dependency inversion: `Shared`
declares a generic, auto-tagged port; each owning BC implements it in its own Infrastructure;
the `Shared` component iterates every tagged provider and merges the non-null results. The
dependency then runs BC → Shared, already allowed — no boundary rule changes.

## Trade-offs

`deptrac-dm` re-analyzes the imported BC ruleset. Config closures under `apps/*/config/**.php`
are outside both tools' reach. Provider completeness depends on each BC remembering to
implement it — a convention, not an enforced contract.

## Rules created

- ALWAYS pass a Domain type needed on the DM side through a `Valid*` compound — never expose it
  directly. ALWAYS implement a cross-cutting port in the owning BC's Infrastructure.
- NEVER add an explicit BC→Shared dependency to feed a cross-cutting component — discovery is by
  auto-tagging only.
