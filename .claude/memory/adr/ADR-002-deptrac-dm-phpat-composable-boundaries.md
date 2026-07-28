# ADR-002 — deptrac_dm maps DM→BC reachability; phpat enforces BC boundary shape

**Date:** 2026-07-28
**Status:** Accepted

## Decision

Two composable checks. deptrac maps which BC each DM may reach (`deptrac_dm.yaml` imports
`deptrac_bc.yaml`; `--fail-on-uncovered` forces every new module to be declared). phpat (a
PHPStan extension under `tools/PHPat/`) enforces how a BC exposes its interface, across three
families: driving ports (`#[AsDrivingPort]`), structural messages (Command/Query/Result/
ApplicationException), and validation compounds (`Infrastructure/Validation`).

`#[AsDrivingPort]` is a pure marker (zero DI effect) flagging an Application port callable
directly by a DM; it never applies to a Command or Query.

## Trade-offs

`deptrac-dm` re-analyzes the imported BC ruleset. Config closures under `apps/*/config/**.php`
are outside both tools' reach.

## Rules created

- ALWAYS pass a Domain type needed on the DM side through a `Valid*` compound — never expose the
  type directly.
