---
paths:
  - "src/**/Domain/**/*.php"
  - "tests/**/Domain/**/*.php"
---

## Source

### Rules

**ALWAYS**
- An event's persisted shape evolves via an upcaster — never retroactively (breaks replay of existing streams).
- Aggregate: private constructor; creation is a named static factory that only does `new self()` + `recordThat()`; state is mutated exclusively inside `#[Apply]` methods.
- `recordThat()` carries primitives only (a VO is serialized via `toString()`/`format('c')`); `#[Apply]` rebuilds each VO through its named constructor (`fromString()`, `::from()`...).
- An event field carrying personal data is tagged `#[PersonalData(fallback: ...)]`, plus one `#[DataSubjectId]` field on the same event — otherwise personal data sits in clear text in an immutable event store forever.
- Autowiring only loads `Domain/Repository/` and `Domain/Service/` — a concrete class anywhere else in `Domain/` is silently never registered as a service.

**NEVER**
- Rename a `#[Event]`/`#[Aggregate]` string identifier without an upcaster in the same change — every existing stream becomes undeserializable on the next replay.

### Conventions
- A business failure is a `final` exception under `Domain/Exception/`, extending `\DomainException`; exposed via a DM → an entry in `config/packages/exceptions.php` (otherwise falls back to 422); named static constructor, no public constructor (e.g. `OrderAlreadyCancelledException::forId($id)`).
- Naming a static constructor: it should read as a sentence at the throw site — a single nameable fact gets a specific class + `for*` (`forId`); a failure that depends on state gets a category class + `cannot<Verb>`.
- A Value Object is `final readonly`; private constructor enforces invariants (`Webmozart\Assert`); a named constructor (`fromCents`, `fromString`...) validates the input shape. Expose `equals()`/`toString()` as needed.
- A Value Object lives in the BC that owns it; it moves to `Shared\Domain\ValueObject` as soon as a second BC uses it, with its `Valid<X>` compound moving to `Shared\Application\Validation` alongside — never promoted before that.
- `#[Aggregate('<subdomain>.<bc>.<aggregate>')]`, three segments even when the aggregate shares its BC's name (e.g. `sales.order.order`); `#[Event('<subdomain>.<bc>.<past-tense verb>')]` — same two-segment prefix, the aggregate segment replaced by the verb (e.g. `sales.order.placed`).
- An aggregate root ID implements `AggregateRootId` via `Shared\Domain\UuidTrait`; an identity that only *references* another aggregate/BC never does.
- An `#[Apply]` method name is `apply<EventClassName>` — the full event class name, never a short verb.
- A reference to another Bounded Context's identity inside an aggregate is a plain `string`, never a local VO duplicating that BC's concept (it can't enforce that BC's invariants and would drift).
- Erasure of personal data is crypto-shredding: a Domain Event implementing the `DataSubjectErasureInterface` marker drops the subject's encryption key — this implicitly covers every past event for that subject, without rewriting the store. A projection that materializes personal data in clear text is not covered by the key drop — it must redact on erasure (a targeted update) or be rebuilt by replay.

## Tests

### Conventions

#### Naming
- Aggregate: success `it*`, failure `itCannot*`.
- Value Object: success `it*`, failure `itProtectsInvariants` (the violated condition lives in the test name or a data set label).
