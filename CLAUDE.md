# DDD / CQRS / Event Sourcing showcase

A Symfony showcase of a DDD/CQRS/Event Sourcing architecture — an order/shipment domain
illustrating the pattern, not a product.

## Stack & Architecture

- PHP, Symfony — versions: `composer.json`
- DDD, CQRS, Event Sourcing (`patchlevel`), Onion layering, Delivery Mechanism (DM)

## Commands (Make = Docker proxy)

```bash
make help                    # list all targets
make start                   # onboarding: up + install + setup + seed demo data
make up / stop                # start/stop the stack
make sh                       # shell into the app container
make seed                     # re-seed demo data (demo/SeedCommand.php)
make test [filter=<x>]        # PHPUnit
make stan                     # PHPStan (includes the phpat architecture suite)
make deptrac-bc / -layers / -dm  # architecture isolation checks
make cs-fix [file=<x>]        # code style auto-fix (whole repo if file omitted)
make static                   # CS + PHPStan/phpat + Deptrac
make qa                       # static + tests
```

## Structure (Monorepo)

- `apps/<dm>/` — a DM booted through the single `bootstrap/Kernel.php` + `appId` (`web`, `api`, `cli`, `webhook`).
- `src/<Subdomain>/<BC>/` — a BC's `Domain/` `Application/` `Infrastructure/`.
- `bootstrap/` — cross-BC DI wiring.
- `config/` — global config + per-subdomain services.
- `tests/` — mirrors `src/`.
- `tools/` — custom QA rules (PHPat architecture tests, a PHPStan rule).

## Memory

@.claude/memory/README.md
