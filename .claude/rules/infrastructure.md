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
- Native `\assert()` narrows a value already guaranteed correct by the calling code, never external/user input. `Webmozart\Assert` stays reserved for a real invariant that must hold in production and throw a catchable exception.

**NEVER**
- Use a raw `HttpClientInterface` outside `Shared\Infrastructure\Gateway` — enforced by `Tools\PHPat\GatewayTest`.
- Add `#[OnFailed]` to an Integration Event Translator's subscriber — a failed translation must halt the subscription, not skip silently.

### Conventions
- `Infrastructure/`'s top-level folders name a technical concern (`Persistence/`, `Monitoring/`, `Security/`...), never a vendor mechanism — the mechanism stays visible in the class name's own suffix instead, not the path.
- Event Store repository: `Persistence/EventStore/Repository/<Agg>Repository`, `final readonly`, implements `<Agg>RepositoryInterface`; injects `#[Autowire(service: 'event_sourcing.<subdomain>.<bc>.<aggregate>.repository')] Repository<Agg> $repository`; `has()`/`save()` delegate as-is, `load()` catches `AggregateNotFound` and re-throws.
- Projector: `#[Projector('<subdomain>.<bc>.<name>')] final readonly Dbal<X>Projector extends AbstractDbalProjector` (e.g. `sales.order.orders`), a `public const string TABLE`, one `#[Subscribe(<Event>::class)] on<Event>()` method per event, and `configureSchema()`.
- One projection per materialization shape (one table, named after what it materializes); as many Finders as there are read axes over that table. A new Projector is warranted only when the access shape itself differs (key, granularity, denormalization) — never just for a new DTO.
- Integration Event translator: `Persistence/EventStore/Translator/<Bc>IntegrationEventTranslator`, `final readonly` extends `AbstractIntegrationEventTranslator`, `#[Translator('<subdomain>.<bc>.integration')]`; one `#[Subscribe(<DomainEvent>::class)] on<Event>()` method per event, calling `$this->append(<correlationId>, new <X>IntegrationEvent(...))`.
- DBAL Finder: `Dbal<X>Finder extends AbstractDbalFinder<Result>`, `final`, never `readonly` (`$queryBuilder` is clone-reassigned) — a collection/filterable read extends `AbstractDbalCollectionFinder` instead (itself an `AbstractDbalFinder`), immutable, consumed via `foreach`/`count()`, never a terminal `get()`. A single-shot lookup is named `ofX()` when absence is an anomaly: it throws its `<X>ResultNotFoundException` (mapped in `config/packages/exceptions.php`). It's `ofXOrNull(): ?Result` instead when absence is a valid outcome for at least one caller.
- A collection Finder's dynamic filter is `by<Concept>(string $value): static` — a single equality parameter by default. It becomes variadic (`by<Concept>(string ...$values): static`, `IN` instead of `=`) only once a real caller actually needs to filter by more than one value at a time — never speculatively, since a single-value `IN` optimizes identically to `=` and an unused variadic parameter is dead flexibility. A non-equality operator appends one suffix from this exhaustive list, never an improvised one: `Not` (`!=`/`NOT IN`), `Containing`/`StartingWith`/`EndingWith` (`LIKE`), `After`/`GreaterThan` and `Before`/`LessThan` (`>`/`<`, date vs numeric field), `AfterOrEqual`/`GreaterThanOrEqual` and `BeforeOrEqual`/`LessThanOrEqual` (`>=`/`<=`). `Not` always precedes any other suffix when composed (`byNameNotContaining`, never the reverse) and never attaches to a bare predicate. A boolean or nullity check with one meaningful polarity is a bare nullary predicate instead (`active()`, `untracked()`) — the opposite polarity, if ever needed, gets its own separately-named method, never a `Not` suffix on this one. An `OR` across exactly two columns fuses both names in alphabetical order (`byEmailOrName`, not `byNameOrEmail`); across three or more, abandon concatenation for an intent-named method instead (`matching(string $term)`). Sorting is `sortedBy<Field>(): static` — same chainable shape, but it orders rather than narrows.
- One `Dbal<X>Finder` per materialization shape (same table, same `Result`) — a new implementation is warranted only when the shape itself differs, same threshold as the Projector rule above.
- Reaching a vendor is two levels, both named after the business concept, never a generic `Gateway/` bucket: the port is `<Bc>/Application/<Concept>/<X>GatewayInterface`, its adapter `<Bc>/Infrastructure/<Concept>/<Vendor>/<Vendor><X>Gateway implements <X>GatewayInterface`. That same folder is the only holder of the scoped HTTP client, `<Vendor>Client` — speaks the vendor's protocol, turns transport/decoding failures into `<Vendor>ClientException`; the adapter holds no client of its own, calls that one and maps the payload, raising the same typed exception on a response it cannot read. The client moves to `Shared/Infrastructure/Gateway/<Vendor>/` only once a second BC actually consumes the same vendor, never in anticipation of one.
- A composite projection subscribes to its own Domain Events plus a foreign BC's Integration Events — never a foreign Domain Event, never another BC's projection. It reads the foreign stream directly at fold time to denormalize onto its own table — `Persistence/Projection/Reducer/Stream<X>Reducer`, reading via `Store::load(new Criteria(new StreamCriterion($streamId)))`, folded with `Patchlevel\EventSourcing\Message\Reducer` (`initState()`/`when()`/`reduce()`), converted to a small immutable DTO. Each foreign attribute that can change over time gets its own `#[Subscribe(<IntegrationEvent>)]` fan-out method, materialized as a nullable column. A Processor mutates (dispatches a Command); a Reducer only reads.
- Every folder under `Persistence/` names the *role* — `Repository/`, `Translator/`, `Projection/Reducer/` — never a bare vendor mechanism name; the `Stream` prefix on a class distinguishes a fold over the event stream from a sibling case built over already-materialized data, and comes first (`Stream<X>Reducer`, matching `Dbal<X>Finder`/`<Vendor><X>Gateway`'s own mechanism-first ordering), never as an infix.
- A cross-BC provider contract is an interface tagged `#[AutoconfigureTag]`; the owning BC's Infrastructure supplies the implementation; the consumer injects `#[AutowireIterator(<X>Interface::class)] iterable` — never a direct dependency on the owning BC.
- A business time threshold is a container parameter under `config/services/<subdomain>.php` (`<subdomain>.<x>_window_days` looking back, `<subdomain>.<x>_days_ahead` looking forward), injected via `#[Autowire(param:)]` into the service that computes the date — never a literal in code.

## Tests

### Conventions

#### Naming
- Repository: success `it*`, failure `itThrowsOn*`.
- Projector: `itProjects*On*` for every reaction, whether it inserts a new row or mutates an already-projected one (no dedicated failure shape).
- Translator: `itPublishes*On*` for every reaction (no dedicated failure shape) — names the cross-BC effect (an Integration Event becomes visible on the shared stream), not the class's own mechanism.
- Finder: an exact lookup that throws when absent is `itGets*`, failure `itThrowsOn*`; one that stays nullable is `itFinds*` for both outcomes — absence is a valid result there, not a failure, so it never takes the `itThrowsOn*`/`itFailsWhen*` shape. A collection read (iterate/filter/paginate) is `itLists*`/`itFilters*By*`/`itPaginates*` — `itGets*`/`itFinds*` are reserved for a single-shot lookup, never a collection scan.

#### Structure
- A Projector test asserts DB state through a `private fetchRow(string $id): array|false` helper — never an inline `SELECT`.
