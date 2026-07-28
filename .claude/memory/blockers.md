# Blockers

Friction encountered while working on this project — root cause, solution found.

## Index

| # | Friction | Status | Date |
|---|----------|--------|------|
| [BLK-001](#blk-001) | PHPStan result cache hides an edited phpat rule | Resolved | 2026-07-28 |
| [BLK-002](#blk-002) | Renaming `#[Event]` breaks store deserialization | Resolved | 2026-07-28 |
| [BLK-003](#blk-003) | Vendor autoloader stale after a new psr-4 prefix | Resolved | 2026-07-28 |
| [BLK-004](#blk-004) | `;`-chained Make foreach hides a failing app | Resolved | 2026-07-28 |
| [BLK-005](#blk-005) | Symfony cache hides an ApiPlatform metadata change | Resolved | 2026-07-28 |
| [BLK-006](#blk-006) | Env-scoped bundle config loaded without a guard | Resolved | 2026-07-28 |
| [BLK-007](#blk-007) | `readable: false` alone still leaves a property writable | Resolved | 2026-07-28 |

## BLK-001

PHPStan caches phpat results by file, not by rule dependency — editing a rule under
`tools/PHPat/` can leave stale violations reported. Clear the cache before investigating:
`vendor/bin/phpstan clear-result-cache -c phpstan.dist.neon`.

## BLK-002

`#[Event]`/`#[Aggregate]` names are the event store's serialization key. Renaming one without an
upcaster in the same change makes every existing stream undeserializable on replay.

## BLK-003

Adding a psr-4 prefix to `composer.json` doesn't take effect until `composer dump-autoload` is
run — a class that resolves correctly by namespace still fails to load.

## BLK-004

A `$(foreach ...)` loop chained with `;` only reports the last app's exit code — earlier
failures are silently swallowed. Chain with `&&` (and a trailing `true` if the loop must not
fail overall).

## BLK-005

Symfony's compiled container/resource cache doesn't invalidate on an ApiPlatform metadata change
(Groups, `normalizationContext`, operations) — clear the cache for the affected app before
judging the result.

## BLK-006

A `config/packages/*.php` file for a bundle that isn't loaded in every environment needs its own
env guard — importing it unconditionally breaks container compilation in environments where the
bundle is absent.

## BLK-007

ApiPlatform treats `readable: false` as "not read", not "absent" — without `writable: false` in
the same declaration, the property still appears in the schema, marked write-only.
