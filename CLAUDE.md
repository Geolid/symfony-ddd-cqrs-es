# Project

A Symfony showcase of a DDD/CQRS/Event Sourcing architecture.

## Stack & Architecture

- PHP, Symfony — versions: `composer.json`
- DDD, CQRS, Event Sourcing (`patchlevel`), Onion layering, Delivery Mechanism (DM)

## Commands (Make = Docker proxy)

```bash
make help                               # list all targets
make sh cmd="<cmd>"                     # shell into the app container
make cc                                 # Cache clear + warmup
make test [filter=<x>] [suite=<x>]      # PHPUnit
make make stan.src / -<dm>              # PHPStan (Core ou DM targeted)
make deptrac-bc / -layers / -dm         # architecture isolation checks
make cs-php-fix / -twig-fix [file=<x>]  # Linter auto-fix (whole repo if file omitted)
make static                             # Lint + CS + Deptrac + Stan
make qa                                 # static + tests
make assets                             # Install DM assets
```

## Structure (Monorepo)

- `apps/<dm>/` — a DM booted through the single `bootstrap/Kernel.php` + `appId`.
- `src/<Subdomain>/<BC>/` — a BC's `Domain/` `Application/` `Infrastructure/`.
- `bootstrap/` — cross-BC DI wiring.
- `config/` — global config + per-subdomain services.
- `demo/` — Seeders
- `tests/` — mirrors `src/`.
- `tools/` — custom QA rules
- `ui/` — Assets, Twig partagés, i18n

## Memory

@.claude/memory/README.md
