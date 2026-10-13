# Project

A Symfony showcase of a DDD/CQRS/Event Sourcing architecture, modeling an
e-commerce flow: identity, catalog, sales (buyer/order), finance (payment/refund),
fulfilment (shipping), after-sales (return).

## Stack & Architecture

- PHP, Symfony — versions: `composer.json`
- DDD, CQRS, Event Sourcing (`patchlevel`), Onion layering, Ports & Adapters (driving/driven), Delivery Mechanism (DM)

## Commands

Castor = Docker proxy. Its contexts (`dev`, `test`, `debug`, `demo`) set the env forwarded to the container.
A task's own options are declared; what follows `--` goes to the wrapped tool as is.

```bash
castor list                                        # see every task (this is a curated subset)
castor setup                                       # bootstrap: docker + vendor + db + assets
castor setup:vendor                                # composer install (any other composer command: castor sh -- composer …)
castor sh [-- <cmd>]                               # shell, or run a command, in the app container
castor cc [--app-id=<dm>]                          # cache clear + warmup (default: all DMs)
castor docker:logs [<service>] [-- <logs args>]    # tail a service's logs
castor db:reset                                    # wipe + fresh DB
castor qa:cs[:php|:twig] [--fix] [<path>]          # coding standards (targeted)
castor qa:stan [--app-id=<dm>] [<path>]            # PHPStan (targeted)
castor qa:deptrac [--scope=<scope>]                # architecture boundaries (targeted)
castor qa:rector [--fix] [<path>]                  # Rector refactoring rules (targeted)
castor qa:test [--testsuite=<s>] [--filter=<n>] [<path>]  # PHPUnit (targeted)
castor qa:static                                   # all static checks (gate)
castor qa                                          # static + test + mutation (gate, before PR)
```

## Structure (Monorepo)

- `apps/<dm>/` — a DM booted through the single `bootstrap/Kernel.php` + `appId`.
- `src/<Subdomain>/<BC>/` — a BC's `Domain/` `Application/` `Infrastructure/`.
- `bootstrap/` — cross-BC DI wiring.
- `config/` — global config + per-subdomain services.
- `demo/` — Demo Stories
- `tests/` — mirrors `src/`.
- `tools/` — QA rules and test tooling
- `ui/` — Assets, shared Twig, i18n

## Memory

@.claude/memory/README.md
