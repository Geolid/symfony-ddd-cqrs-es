# Architecture Decision Records

Architecture decisions: the what, the why, and the trade-offs — whatever isn't obvious from
reading the code alone.

## Index

| # | Title | Status | Date |
|---|-------|--------|------|
| [ADR-001](ADR-001-domain-events-sealed-integration-event-single-contract.md) | Domain Events sealed per BC; Integration Events are the sole cross-BC contract | Accepted | 2026-07-28 |
| [ADR-002](ADR-002-deptrac-dm-phpat-composable-boundaries.md) | deptrac_dm maps DM→BC reachability; phpat enforces BC boundary shape | Accepted | 2026-07-28 |
| [ADR-003](ADR-003-aggregate-event-naming-three-segments.md) | Aggregate/Event naming: three segments mirroring the source path | Accepted | 2026-07-28 |
| [ADR-004](ADR-004-exhaustive-openapi-test-external-dm-only.md) | Exhaustive OpenAPI contract test required only for externally-consumed DMs | Accepted | 2026-07-28 |
| [ADR-005](ADR-005-reaction-model-side-effects-enrichment-erasure.md) | Reaction model: side effects, enrichment, personal-data erasure | Accepted | 2026-07-28 |
| [ADR-006](ADR-006-sentry-context-provider-dependency-inversion.md) | Sentry context enrichment via a Domain-declared port | Accepted | 2026-07-28 |
