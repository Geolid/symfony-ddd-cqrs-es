# Blockers

Friction encountered while working on this project — root cause, solution found.

## Index

| # | Friction | Status | Date |
|---|----------|--------|------|
| [BLK-001](#blk-001) | A stale cache masks a real change | Resolved | 2026-07-28 |
| [BLK-002](#blk-002) | Build tooling silently produces a stale result | Resolved | 2026-07-28 |
| [BLK-003](#blk-003) | Env-scoped bundle config loaded without a guard | Resolved | 2026-07-28 |
| [BLK-004](#blk-004) | `readable: false` alone still leaves a property writable | Resolved | 2026-07-28 |

## BLK-001

Two independent caches can hide a real source change: PHPStan's result cache doesn't see the
file-to-rule dependency of a phpat rule edited under `tools/PHPat/` (clear with
`vendor/bin/phpstan clear-result-cache -c phpstan.dist.neon`); Symfony's compiled cache doesn't
invalidate on an ApiPlatform metadata change (Groups, `normalizationContext`, operations) —
clear the cache for the affected app before judging either result.

## BLK-002

Two build-tooling foot-guns: adding a psr-4 prefix to `composer.json` doesn't take effect until
`composer dump-autoload` runs; a `$(foreach ...)` Make loop chained with `;` only reports the
last app's exit code, silently swallowing earlier failures — chain with `&&` instead (plus a
trailing `true` if the loop must not fail overall).

## BLK-003

A `config/packages/*.php` file for a bundle that isn't loaded in every environment needs its own
env guard — importing it unconditionally breaks container compilation in environments where the
bundle is absent.

## BLK-004

ApiPlatform treats `readable: false` as "not read", not "absent" — without `writable: false` in
the same declaration, the property still appears in the schema, marked write-only.
