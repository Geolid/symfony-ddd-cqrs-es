# Evals

Quality review of auto-generated output — anomalies caught, corrections applied.

## Index

| # | Output reviewed | Action | Date |
|---|------------------|--------|------|
| [EVAL-001](#eval-001) | Finder test named `itFinds*` for an exact, throwing lookup | Corrected | 2026-07-28 |
| [EVAL-002](#eval-002) | Success case for one endpoint split across two tests | Corrected | 2026-07-28 |

## EVAL-001

A Finder test named `itFinds*` for a lookup that gets-or-throws (never returns null) is
misleading — `Finds` implies a possibly-empty result. The repo's convention for an exact lookup
that throws is `itGets*`.

## EVAL-002

Two success tests replayed the same call to cover one endpoint's response contract in
fragments. A single success test should cover the full response contract in one pass; merge
fragments into one test instead of stacking near-duplicates.
