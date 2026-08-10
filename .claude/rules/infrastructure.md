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

**NEVER**
- Use a raw `HttpClientInterface` outside `Shared\Infrastructure\Gateway` — enforced by `Tools\PHPat\GatewayTest`.
- Add `#[OnFailed]` to an Integration Event Translator's subscriber — a failed translation must halt the subscription, not skip silently.

### Conventions
- `Infrastructure/`'s top-level folders name a technical concern (`Persistence/`, `Monitoring/`, `Security/`...), never a framework mechanism (e.g. no `HttpKernel/`) — the mechanism stays visible in the class name's own suffix instead (`SentryMessengerMiddleware`), not the path.
- Event Store repository: `Persistence/EventStore/Repository/<Agg>Repository`, `final readonly`, implements `<Agg>RepositoryInterface`; injects `#[Autowire(service: 'event_sourcing.<subdomain>.<bc>.<aggregate>.repository')] Repository<Agg> $repository`; `has()`/`save()` delegate as-is, `load()` catches `AggregateNotFound` and re-throws.
- Projector: `#[Projector('<subdomain>.<bc>.<name>')] final readonly Dbal<X>Projector extends AbstractDbalProjector` (e.g. `sales.order.orders`), a `public const string TABLE`, one `#[Subscribe(<Event>::class)] on<Event>()` method per event, and `configureSchema()`.
- One projection per materialization shape (one table, named after what it materializes); as many Finders as there are read axes over that table. A new Projector is warranted only when the access shape itself differs (key, granularity, denormalization) — never just for a new DTO.
- Integration Event translator: `Persistence/EventStore/Translator/<Bc>IntegrationEventTranslator`, `final readonly` extends `AbstractIntegrationEventTranslator`, `#[Translator('<subdomain>.<bc>.integration')]`; one `#[Subscribe(<DomainEvent>::class)] on<Event>()` method per event, calling `$this->append(<correlationId>, new <X>IntegrationEvent(...))`.
- DBAL Finder: `Dbal<X>Finder extends AbstractDbalFinder<Result>`, `final`, never `readonly` (`$queryBuilder` is clone-reassigned) — a collection/filterable read extends `AbstractDbalCollectionFinder` instead (itself an `AbstractDbalFinder`) for `with*(): static`, iteration, `paginate()`. A single-shot lookup is named `ofX()` regardless of outcome: it throws its `<X>ResultNotFoundException` (mapped in `config/packages/exceptions.php`) when the Finder should own the "must exist" decision, or stays `ofX(): ?Result` when absence is valid for at least one caller — the return type alone carries which.
- One `Dbal<X>Finder` per materialization shape (same table, same `Result`) — a new implementation is warranted only when the shape itself differs, same threshold as the Projector rule above.
- Reaching a vendor is two levels. `Shared/Infrastructure/Gateway/<Vendor>/<Vendor>Client` is the only holder of the scoped HTTP client (host/auth on the service definition) and speaks the vendor's protocol, turning transport and decoding failures into `<Vendor>ClientException`. `<Bc>/Infrastructure/Gateway/<Vendor>/<Vendor><X>Gateway implements <X>GatewayInterface` (the port is declared in Application) holds no client: it calls that one and maps the payload, raising the same typed exception on a response it cannot read.
- A composite projection subscribes to its own Domain Events plus a foreign BC's Integration Events — never a foreign Domain Event, never another BC's projection. It reads the foreign stream directly at fold time to denormalize onto its own table — `Persistence/Projection/Reducer/<X>Reducer`, reading via `Store::load(new Criteria(new StreamCriterion($streamId)))`, folded with `Patchlevel\EventSourcing\Message\Reducer` (`initState()`/`when()`/`reduce()`), converted to a small immutable DTO. Each foreign attribute that can change over time gets its own `#[Subscribe(<IntegrationEvent>)]` fan-out method, materialized as a nullable column. A Processor mutates (dispatches a Command); a Reducer only reads.
- A fold serving a use case rather than a projection is `Persistence/EventStore/Resolver/<X>Resolver implements <X>ResolverInterface` (the port is declared in Application), same `Store::load()` + `Reducer` mechanism, returning a small immutable DTO or `null`. Every folder under `Persistence/` names the *role* — `Repository/`, `Translator/`, `Resolver/`, `Projection/Reducer/` — never the vendor mechanism, so `Reducer` stays reserved for the projection case above.
- A pure computation over primitives a Projector already has in hand, zero I/O, zero fold, is `Persistence/Projection/Transformer/<X>Transformer`, `final readonly`, one public method, injected as the concrete class. Never `Reducer`/`Resolver` — both read the event store.
- A cross-BC provider contract is an interface tagged `#[AutoconfigureTag]`; the owning BC's Infrastructure supplies the implementation; the consumer injects `#[AutowireIterator(<X>Interface::class)] iterable` — never a direct dependency on the owning BC.
- A business time threshold is a container parameter under `config/services/<subdomain>.php` (`<subdomain>.<x>_window_days` looking back, `<subdomain>.<x>_days_ahead` looking forward), injected via `#[Autowire(param:)]` into the service that computes the date — never a literal in code.

## Tests

### Conventions

#### Naming
- Repository: success `it*`, failure `itThrowsOn*`.
- Projector: `itProjects*On*` for every reaction, whether it inserts a new row or mutates an already-projected one (no dedicated failure shape).
- Finder: an exact lookup that throws when absent is `itGets*`, failure `itThrowsOn*`; one that stays nullable is `itFinds*` for both outcomes — absence is a valid result there, not a failure, so it never takes the `itThrowsOn*`/`itFailsWhen*` shape. A collection read (iterate/filter/paginate) is `itLists*`/`itFilters*By*`/`itPaginates*` — `itGets*`/`itFinds*` are reserved for a single-shot lookup, never a collection scan.

#### Structure
- A Projector test asserts DB state through a `private fetchRow(string $id): array|false` helper — never an inline `SELECT`.
