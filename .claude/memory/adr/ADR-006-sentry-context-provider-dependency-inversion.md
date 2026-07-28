# ADR-006 — Sentry context enrichment via a Domain-declared port

**Date:** 2026-07-28
**Status:** Accepted

## Decision

`SentryEventEnricher` (Shared) needs contextual data owned by specific BCs, but
`deptrac_bc.yaml` forbids Shared from depending on a BC. Dependency inversion: `Shared` declares
a generic, auto-tagged port (`name(): string`, `provide(): ?array`); each owning BC implements
it in its own Infrastructure; the enricher iterates every tagged provider and merges the
non-null results. The dependency runs BC → Shared, already allowed — no boundary rule changes.

## Trade-offs

Completeness depends on each BC remembering to add its provider — a convention, not an enforced
contract.

## Rules created

- ALWAYS implement the context-provider interface in the owning BC's Infrastructure.
- NEVER add an explicit BC→Shared dependency for this — discovery is by auto-tagging only.
