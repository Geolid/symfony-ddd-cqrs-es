# DDD / CQRS / Event Sourcing — a Symfony showcase

[![CI](https://github.com/geolid/symfony-ddd-cqrs-es/actions/workflows/ci.yml/badge.svg)](https://github.com/geolid/symfony-ddd-cqrs-es/actions/workflows/ci.yml)

A showcase Symfony application demonstrating a Domain-Driven Design / CQRS / Event Sourcing
architecture: a Command bus and a Query bus, an event-sourced write model, DBAL-backed
projections for reads, Bounded Context isolation enforced by static analysis, and several
Delivery Mechanisms sharing one Kernel.

The domain (an order/shipment tracker) is a vehicle for the architecture, not a product —
pick it apart, rename it, replace it; the interesting part is the structure around it.

## Architecture

Six Bounded Contexts:

- **`Sales.Customer`** — registers and erases a `Customer`, the showcase's data subject.
- **`Sales.Order`** — places and cancels an `Order`, whose total the aggregate derives from
  the lines it was placed with; no surface may dictate an amount.
- **`Fulfilment.Shipment`** — creates, dispatches and delivers a `Shipment`.
- **`Catalog.Product`** — lists, reprices and delists a `Product`.
- **`Iam.Identity`** — registers an `Identity` and manages its credentials (password, API
  token), suspends and reactivates it.
- **`Iam.Access`** — grants and revokes a `Grant`'s permissions.

`Fulfilment` never depends on `Sales`'s Domain or Application internals — the only sanctioned
cross-BC edges (see `deptrac_bc.yaml`) are Sales' public Integration Event contracts. Neither
of Sales' Domain Events (`OrderPlaced`, `OrderCancelled`) ever leaves its BC; an
Infrastructure-layer Translator (`OrderIntegrationEventTranslator`) converts each into its
public counterpart (`OrderPlacedIntegrationEvent`, `OrderCancelledIntegrationEvent`) and appends
it to the event store. `Fulfilment` reacts to both, but in two different shapes, side by side:

- **Side effect (Processor)** — `CreateShipmentOnOrderPlaced` subscribes to
  `OrderPlacedIntegrationEvent` and dispatches a Command (`CreateShipment`) in response.
- **Read-side enrichment, two ways, in the same `DbalShipmentProjector`** —
  - *Backfill*: `OrderPlaced` always happened *before* the Shipment existed, so there's nothing
    to subscribe to yet — `OrderSummaryReducer` replays Sales' Integration Event stream for
    that order once, at `ShipmentCreated` time, to denormalize the customer/total onto the
    Shipment row.
  - *Fan-out*: `OrderCancelled` can happen *after* the Shipment already exists, so the
    projection instead subscribes to `OrderCancelledIntegrationEvent` directly and updates the
    existing row in place — no replay needed.
A third cross-BC shape sits one level up, inside `Sales`: placing an order needs the buyer
behind it, so `Sales.Order` declares `BuyerResolverInterface` and its Infrastructure folds
`Sales.Customer`'s Integration Event stream. The resolved address travels on `OrderPlaced`,
then on `OrderPlacedIntegrationEvent`, and `CreateShipmentOnOrderPlaced` freezes it onto
`ShipmentCreated`. `Fulfilment` therefore knows a single upstream contract — the order —
and the delivery notification is a pure function of Fulfilment's own stream, identical on
every replay. Every hop tags the address `#[PersonalData(fallback: null)]` next to a
`#[DataSubjectId]` customer id, so one key drop turns it to `null` the whole way down and
the notification is simply skipped; no projection ever materializes it.

That Processor is also replay-safe by construction: `ShipmentId::forOrder()` derives the
identity as a `uuid5` of the order id, so a replay resolves to the same aggregate and
`CreateShipmentHandler` returns early instead of opening a second shipment.

Four Delivery Mechanisms (`apps/`) call the same Command/Query bus, sharing one
`bootstrap/Kernel.php`:

| DM | Exposes |
|---|---|
| `apps/api` | JSON HTTP (API Platform) for orders and shipments |
| `apps/web` | A small Twig backoffice (register a customer, place and cancel an order, list shipments, erase a customer) |
| `apps/cli` | Console commands (bootstrap, `fulfilment:shipment:manifest-pending`) |
| `apps/webhook` | An inbound carrier webhook (HMAC-verified) marking a shipment delivered |

A Delivery Mechanism only ever depends on a BC's Open Host Service — its `#[AsDrivingPort]`
behaviours and its published language (`PublishedLanguageInterface`: Commands, Queries, Results,
Application exceptions, validation compounds, published vocabularies) — never on a Repository, a
Finder implementation, or a persistence vendor directly. This and the BC isolation above are
enforced by `deptrac_*.yaml` and the `Tools\PHPat\*` rules run through PHPStan, not just
documented.

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
- **Error tracking** — `sentry/sentry-symfony` (`config/packages/sentry.php`, prod only) is
  already wired; it never has to be a paid Sentry account. Its wire protocol is also implemented
  by [GlitchTip](https://glitchtip.com/), an open-source reimplementation with a free hosted
  tier ([sign-up](https://app.glitchtip.com/), or self-host it) — create a project there, follow
  their [Symfony SDK guide](https://glitchtip.com/sdkdocs/php-symfony), and put the DSN it gives
  you in `SENTRY_DSN`. Nothing else changes.
- **Logging** — structured stdout in dev (`docker compose logs`) needs nothing extra. For
  aggregated prod logs, `config/packages/monolog.php` already ships an
  [`itspire/monolog-loki`](https://github.com/itspire/monolog-loki) handler (wrapped in a
  `whatfailuregroup` — a push failure never breaks a request); point it at
  [Grafana Cloud](https://grafana.com/products/cloud/)'s free tier (includes Loki) or any
  self-hosted Loki via `LOKI_URL`/`LOKI_BASIC_AUTH_USER`/`LOKI_BASIC_AUTH_PASSWORD`. Unset, it
  silently no-ops.
- **Local mail** — Mailpit catches outgoing mail in dev instead of a transactional-email SaaS.

## Getting started

```bash
git clone <this-repo> && cd symfony-ddd-cqrs-es
cp compose.override.yaml.dist compose.override.yaml
make start   # stack up, composer install, event store + read model set up, demo data seeded
```

Then visit `http://localhost/web/` (already showing the seeded orders) or call
`http://localhost/api/v1/sales/orders`. Mailpit's UI is at `http://localhost:8025`.

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
demo/               demo/console (env "demo") + fixtures dispatched through the real Command bus
docker/             Dockerfile, nginx vhost, MariaDB init
make/               the modular Makefile (make/base/*.mk + make/tiers/{dev,ci,demo}.mk)
src/<Subdomain>/<BC>/ a Bounded Context: Domain/, Application/, Infrastructure/
tests/              mirrors src/
tools/PHPat/        architecture rules run through PHPStan
tools/PHPStan/      a custom PHPStan rule
ui/                 templates/assets/translations shared across every Twig-using DM
```

`.claude/` documents the engineering conventions this codebase follows (`.claude/rules/`),
architecture decisions (`.claude/memory/adr/`), and a few task-specific subagents/skills for
working on it with Claude Code.

## License

MIT.
