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
- An event field carrying personal data is tagged `#[SensitiveData(fallbackCallable: ...)]`, plus one `#[DataSubjectId]` field on the same event — otherwise personal data sits in clear text in an immutable event store forever.
- Autowiring only loads `Domain/Repository/` and `Domain/Service/` — a concrete class anywhere else in `Domain/` is silently never registered as a service.

**NEVER**
- Rename a `#[Event]`/`#[Aggregate]` string identifier without an upcaster in the same change — every existing stream becomes undeserializable on the next replay.

### Conventions
- A transition method's self-guard is decided by outcome, not re-entry: still matches the caller's intent → no-op; no business meaning on the current state → throw.
- A cross-aggregate invariant (another aggregate's own state gating this one's creation or transition, same BC) is asserted by the aggregate that owns the state being checked, as a nullary `ensure<Invariant>(): void`, throwing its own named exception (`Identity::ensureActive()` → `IdentityNotActiveException`) — same shape as a transition's self-guard, just without a state change on success. The calling Application Handler loads that aggregate through its own Repository and calls the guard; it never re-implements the check inline.
- A business failure is a `final` exception under `Domain/Exception/`, extending `\DomainException`; exposed via a DM → an entry in `config/packages/exceptions.php` (otherwise falls back to 422); named static constructor, no public constructor (e.g. `OrderAlreadyCancelledException::forId($id)`). A failure shape common to every BC is the `abstract` `Shared\Domain\Exception\AggregateNotFoundException`, mapped once in `config/packages/exceptions.php`; each BC's concrete `final` exception extends it under its own `Domain/Exception/`. Its sibling `abstract` `Shared\Domain\Exception\AggregateAlreadyExistsException` follows the same shape, for an aggregate whose creation must reject a second attempt at an identity already taken.
- Naming a static constructor: it should read as a sentence at the throw site — a single nameable fact gets a specific class + `for*` (`forId`); a failure that depends on state gets a category class + `cannot<Verb>`.
- A Value Object is `final readonly`; private constructor enforces invariants (`Webmozart\Assert`); a named constructor (`fromCents`, `fromString`...) validates the input shape. It always exposes `equals()`/`toString()` — value equality is the VO's defining trait, not conditioned on an existing caller — never a generic `toArray()`/`fromArray()` pair: an event/command field carrying a VO's data stays an array of its own primitive properties, mapped by hand at each construction site (`recordThat()`, `#[Apply]`, a Handler) — the array shape is that call site's concern, not the VO's.
- Native `\assert()` narrows a value already guaranteed correct by the calling code, never external/user input. `Webmozart\Assert` stays reserved for a real invariant that must hold in production and throw a catchable exception.
- A Value Object lives in `Domain/ValueObject/` of the BC that owns it; it moves to `Shared\Domain\ValueObject` as soon as a second BC uses it, with its `Valid<X>` compound moving to `Shared\Application\Validation` alongside — never promoted before that. That boundary is decided by whether the concept and its invariants are genuinely the same across both BCs, never by how convenient it would be to share one differently-named validation compound around it — two BCs each keep their own Value Object the moment either one's invariants would otherwise have to special-case the other's.
- A closed-vocabulary lifecycle enum folded from events (`Domain/ValueObject/<X>State`) is suffixed `State`, never `Status` — `Status` names its Application-side counterpart (`Application/Status/<X>Status`), a separate read/UI-facing value space that never proxies this enum's case values.
- A field on an aggregate earns its place, and its getter a caller, by the same test: a self-guard reads it, or a getter serves a sibling invariant guard, an outbound port call, or an Integration Event's construction — never a Delivery Mechanism or a test reading business state for a display/read purpose, which goes through a Finder instead, even right after driving a write through that same Aggregate. A fact none of these read back belongs on the event, for a Read Model, not as aggregate state. Two facts always evaluated together as one combined outcome collapse into a single field; two facts a guard needs distinguished separately stay as separate fields, each shaped by its own cardinality — a binary fact is a bool, a multi-case transition is an enum.
- `#[Aggregate('<subdomain>.<bc>.<aggregate>')]`, three segments even when the aggregate shares its BC's name (e.g. `sales.order.order`); `#[Event('<subdomain>.<bc>.<past-tense verb>')]` — same two-segment prefix, the aggregate segment replaced by the verb (e.g. `sales.order.placed`).
- An aggregate root ID implements `AggregateRootId` via `Shared\Domain\UuidTrait` (root-generated, exposes `generate()`) or `Shared\Domain\DerivedUuidTrait` (deterministically derived from a parent id via a named `for<Concept>()` factory using `uuid5`, no `generate()`) — never both meanings on the same trait. An identity that only *references* another aggregate/BC never uses either.
- An `#[Apply]` method name is `apply<Event>`, `<Event>` being the event's own class name with its aggregate-name prefix dropped (the method already lives inside that aggregate) — never a short verb unrelated to the event, never the full un-trimmed event class name.
- A reference to another Bounded Context's identity inside an aggregate is a plain `string`, never a local VO duplicating that BC's concept (it can't enforce that BC's invariants and would drift).
- Erasure of personal data is crypto-shredding: a Domain Event implementing the `DataSubjectErasureInterface` marker drops the subject's encryption key — this implicitly covers every past event for that subject, without rewriting the store. A projection that materializes personal data in clear text is not covered by the key drop — it must redact on erasure (a targeted update) or be rebuilt by replay.
- A cipher key is held per subject id for the whole store, so personal data carried into another BC on an Integration Event falls with the same drop as long as the receiving event repeats that `#[DataSubjectId]` value — nothing to erase downstream, no coordination between Bounded Contexts.

## Tests

### Conventions

#### Naming
- Aggregate: success `it*`, failure `itCannot*`.
- Value Object: success `it*`, failure `itProtectsInvariants` (the violated condition lives in the test name or a data set label).
