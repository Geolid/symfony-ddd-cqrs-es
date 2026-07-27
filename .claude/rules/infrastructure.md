---
paths:
  - "src/**/Infrastructure/**/*.php"
  - "tests/**/Infrastructure/**/*.php"
---

## Source

### Rules

**ALWAYS**
- Rehydrating a date from the database: `new \DateTimeImmutable($value, new \DateTimeZone('UTC'))` — an explicit timezone, otherwise the behavior silently depends on the server's local timezone.
- An Event Store repository's `load()` catches `AggregateNotFound` and re-throws the BC's own `<Aggregate>NotFoundException` — otherwise a vendor exception leaks out as a raw 500 instead of a mapped domain failure.
- A Projector extends `AbstractDbalProjector` — outside that base class it isn't in the `projector` group and its schema is never created.
- A Finder reads `from(<Projector>::TABLE)`, never a re-declared table-name literal — otherwise it silently drifts if the projector's table changes.

### Conventions
- Event Store repository: `Persistence/EventStore/Repository/<Agg>Repository`, `final readonly`, implements `<Agg>RepositoryInterface`; injects `#[Autowire(service: 'event_sourcing.<subdomain>.<bc>.<aggregate>.repository')] Repository<Agg> $repository` and delegates `load()`/`save()` almost as-is.
- Projector: `#[Projector('<subdomain>.<bc>.<name>')] final readonly Dbal<X>Projector extends AbstractDbalProjector` (e.g. `ordering.order.orders`), a `public const string TABLE`, one `#[Subscribe(<Event>::class)] on<Event>()` method per event, and `configureSchema()`.
- One projection per materialization shape (one table, named after what it materializes); as many Finders as there are read axes over that table. A new Projector is warranted only when the access shape itself differs (key, granularity, denormalization) — never just for a new DTO.
- Integration Event translator: `Persistence/EventStore/Translator/<Bc>IntegrationEventTranslator`, `final readonly` extends `AbstractIntegrationEventTranslator`, `#[Translator('<subdomain>.<bc>.integration_translator')]`; one `#[Subscribe(<DomainEvent>::class)] on<Event>()` method per event, calling `$this->append(<correlationId>, new <X>IntegrationEvent(...))`.
- A DBAL Finder that filters/paginates is `final` (NOT readonly) and extends `AbstractDbalFinder<Result>` — implement `buildBaseQuery()` + `mapRow()`, and expose filters as `with*(): static` built on `$this->filter()`.
- An external gateway lives at `Gateway/<Vendor>/<Vendor><X>Gateway implements <X>GatewayInterface` (the port is declared in Application); it injects a scoped HTTP client (host/auth configured on the service) and wraps transport errors in a typed exception. A raw `HttpClientInterface` is reserved for `Shared\Infrastructure\Gateway` (enforced by `Tools\PHPat\GatewayTest`).
- A composite projection reads a foreign BC's Integration Event stream directly at fold time to denormalize onto its own table — `Persistence/Projection/Reducer/<X>Reducer`, reading via `Store::load(new Criteria(new StreamCriterion($streamId)))`, folded with `Patchlevel\EventSourcing\Message\Reducer` (`initState()`/`when()`/`reduce()`), converted to a small immutable DTO. A Processor mutates (dispatches a Command); a Reducer only reads.

## Tests

### Conventions

#### Naming
- Repository: success `it*`, failure `itThrowsOn*`.
- Projector: `itProjects*On*` (no dedicated failure shape).
- Finder: success `it*`, `itFilters*By*`, `itPaginates*`, failure `itThrowsOn*`.
