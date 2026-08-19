# Project

A Symfony showcase of a DDD/CQRS/Event Sourcing architecture, modeling a small
e-commerce flow: customers, orders, shipping, catalog, identity.

## Stack & Architecture

- PHP, Symfony — versions: `composer.json`
- DDD, CQRS, Event Sourcing (`patchlevel`), Onion layering, Delivery Mechanism (DM)

## Commands (Castor = Docker proxy)

```bash
castor list                                        # tasks
castor sh ["<cmd>"]                                # shell in app container
castor cc                                          # cache clear + warmup
castor qa:test [options] [<target>]                # PHPUnit
castor qa:mutation [--coverage]                    # Infection, diff-scoped
castor qa:stan [--app=<dm>] [<target>]             # PHPStan (default: all)
castor qa:deptrac [--scope=bc|layers|dm]           # architecture checks (default: all)
castor qa:cs [--type=php|twig] [--fix] [<target>]  # lint (default: check)
castor qa:rector [--fix] [<target>]                # Rector (default: check)
castor qa:static                                   # lint + cs + deptrac + stan + rector
castor qa                                          # static + test + mutation
castor assets [<dm>]                               # install assets (default: all DMs)
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
