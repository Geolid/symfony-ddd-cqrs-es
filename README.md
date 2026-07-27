# DDD / CQRS / Event Sourcing — a Symfony showcase

[![CI](https://github.com/geolid/symfony-ddd-cqrs-es/actions/workflows/ci.yml/badge.svg)](https://github.com/geolid/symfony-ddd-cqrs-es/actions/workflows/ci.yml)

A showcase Symfony application demonstrating a Domain-Driven Design / CQRS / Event Sourcing
architecture: a Command bus and a Query bus, an event-sourced write model, DBAL-backed
projections for reads, Bounded Context isolation enforced by static analysis, and several
Delivery Mechanisms sharing one Kernel.

The domain (an order/shipment tracker) is a vehicle for the architecture, not a product —
pick it apart, rename it, replace it; the interesting part is the structure around it.

## Architecture

Two Bounded Contexts:

- **`Ordering.Order`** — places and cancels an `Order`.
- **`Shipping.Shipment`** — creates, dispatches and delivers a `Shipment`.

`Shipping` never depends on `Ordering`'s Domain or Application internals. Ordering's
`OrderPlaced` Domain Event never leaves its BC — an Infrastructure-layer Translator converts it
into a public `OrderPlacedIntegrationEvent`, which a Processor in `Shipping` reacts to by
opening a Shipment. That's the one sanctioned cross-BC edge (see `deptrac_bc.yaml` and
`.claude/memory/adr/`).

Four Delivery Mechanisms (`apps/`) call the same Command/Query bus, sharing one
`bootstrap/Kernel.php`:

| DM | Exposes |
|---|---|
| `apps/api` | JSON HTTP endpoints for orders and shipments |
| `apps/web` | A small Twig backoffice (list orders/shipments, place an order) |
| `apps/cli` | Console commands (`order:place`, `shipment:dispatch-pending`) |
| `apps/webhook` | An inbound carrier webhook (HMAC-verified) marking a shipment delivered |

A Delivery Mechanism only ever depends on `#[AsDrivingPort]` ports and Command/Query messages —
never on a Repository, a Finder implementation, or a persistence vendor directly. This and the
BC isolation above are enforced by `deptrac_*.yaml` and the `Tools\PHPat\*` rules run through
PHPStan, not just documented.

## Stack

- Symfony (Messenger for the Command/Query buses, Doctrine DBAL for read models)
- [`patchlevel/event-sourcing-bundle`](https://github.com/patchlevel/event-sourcing) for the event store, aggregates and subscriptions (projectors, translators, processors)
- [Deptrac](https://github.com/qossmic/deptrac) + [PHPat](https://github.com/carlosas/phpat) for architecture enforcement (Onion layering, BC isolation, DM reach)
- MariaDB, [Mailpit](https://github.com/axllent/mailpit) for local dev

## Open source over paid SaaS

Everything this stack needs to run locally or in CI is free and self-hostable:

- **CI/CD** — GitHub Actions, not a paid CI SaaS.
- **Message transport** — Messenger's Doctrine transport by default (zero extra infra);
  `docker compose --profile broker up` starts a RabbitMQ broker instead, no code change needed
  beyond the DSN.
- **Error tracking** — `sentry/sentry-symfony`'s wire protocol works against self-hosted Sentry
  or [GlitchTip](https://glitchtip.com/) (a free, open-source reimplementation) just as well as
  the hosted product — point `SENTRY_DSN` at either.
- **Logging** — structured stdout in dev (`docker compose logs`); swap in a self-hosted
  Grafana Loki/Promtail stack for aggregation if you need it, no paid log-shipping SaaS.
- **Local mail** — Mailpit catches outgoing mail in dev instead of a transactional-email SaaS.

## Getting started

```bash
git clone <this-repo> && cd symfony-ddd-cqrs-es
cp compose.override.yaml.dist compose.override.yaml
make start   # stack up, composer install, event store + read model set up, demo data seeded
```

Then visit `http://localhost/web/` (backoffice, already showing the seeded orders) or call
`http://localhost/api/orders`. Mailpit's UI is at `http://localhost:8025`.

`make start` is `up wait-db install setup seed` — see `Makefile` for each step, or run them
individually. Re-run `make seed` any time to add more demo orders (`demo/SeedCommand.php`,
a worked example of writing fixtures for an event-sourced app: through the Command bus, never
a direct insert into the read model). For anything beyond a laptop, copy `.env` to `.env.local`
and set a real `APP_SECRET`/`CARRIER_WEBHOOK_SECRET` first.

## Quality gates

```bash
make test              # PHPUnit
make stan               # PHPStan, includes the phpat architecture suite
make deptrac-layers      # Domain / Application / Infrastructure
make deptrac-bc          # Bounded Context isolation
make deptrac-dm          # Delivery Mechanism -> Bounded Context reach
make qa                 # everything above
```

## Directory structure

```
apps/<dm>/          a Delivery Mechanism: config/{bundles.php,routes.php,packages/}, src/, tests/
bootstrap/          the single Kernel + cross-BC DI wiring (compiler passes, subdomain loader)
config/             global Symfony config + per-subdomain services
docker/              Dockerfile, nginx vhost, MariaDB init
src/<Subdomain>/<BC>/ a Bounded Context: Domain/, Application/, Infrastructure/
tests/               mirrors src/
tools/PHPat/         architecture rules run through PHPStan
tools/PHPStan/       a custom PHPStan rule
```

`.claude/` documents the engineering conventions this codebase follows (`.claude/rules/`),
architecture decisions (`.claude/memory/adr/`), and a few task-specific subagents/skills for
working on it with Claude Code.

## License

MIT.
