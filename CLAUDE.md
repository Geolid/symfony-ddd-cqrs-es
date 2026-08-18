# Project

A Symfony showcase of a DDD/CQRS/Event Sourcing architecture, modeling a small
e-commerce flow: customers, orders, shipping, catalog, identity.

## Stack & Architecture

- PHP, Symfony — versions: `composer.json`
- DDD, CQRS, Event Sourcing (`patchlevel`), Onion layering, Delivery Mechanism (DM)

## Commands (Make = Docker proxy)

```bash
make help                                       # list all targets
make sh cmd="<cmd>"                             # shell into the app container
make cc                                         # Cache clear + warmup
make test [coverage=1] [filter=<x>] [suite=<x>] # PHPUnit (ParaTest)
make mutation [coverage=1]                      # Infection mutation testing (scoped to diff)
make stan.src / stan.<dm>                       # PHPStan
make deptrac-bc / -layers / -dm                 # architecture isolation checks
make cs-php-fix / -twig-fix [file=<x>]          # Linter auto-fix (whole repo if file omitted)
make rector-fix [file=<x>]                      # Rector auto-fix (whole repo if file omitted)
make static                                     # Lint + CS + Deptrac + Stan + Rector
make qa                                         # static + tests + mutation testing
make assets                                     # Install DM assets
```

## Structure (Monorepo)

- `apps/<dm>/` — a DM booted through the single `bootstrap/Kernel.php` + `appId`.
- `src/<Subdomain>/<BC>/` — a BC's `Domain/` `Application/` `Infrastructure/`.
- `bootstrap/` — cross-BC DI wiring.
- `config/` — global config + per-subdomain services.
- `demo/` — Seeders
- `tests/` — mirrors `src/`.
- `tools/` — custom QA rules
- `ui/` — Assets, shared Twig, i18n

## Memory

@.claude/memory/README.md
