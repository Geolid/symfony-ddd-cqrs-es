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
- Autowiring only loads `Domain/Repository/` and `Domain/Service/` (see `Bootstrap\DependencyInjection\SubdomainServiceLoader`) — a concrete class anywhere else in `Domain/` is silently never registered as a service, by design (Domain has no framework wiring).

**NEVER**
- Rename a `#[Event]`/`#[Aggregate]` string identifier without an upcaster in the same change — every existing stream becomes undeserializable on the next replay.

### Conventions
- A business failure is a `final` exception under `Domain/Exception/`, extending `\DomainException`; named static constructor, no public constructor (e.g. `OrderAlreadyCancelledException::forId($id)`).
- A Value Object is `final readonly`; private constructor enforces invariants (`Webmozart\Assert`); a named constructor (`fromCents`, `fromString`...) validates the input shape. Expose `equals()`/`toString()` as needed.
- `#[Aggregate('<subdomain>.<bc>')]` mirrors the class path; `#[Event('<same prefix>.<past-tense verb>')]`.
- An aggregate root ID implements `AggregateRootId` via `Shared\Domain\UuidTrait`.
- An `#[Apply]` method name is `apply<EventClassName>` — the full event class name, never a short verb.
- A reference to another Bounded Context's identity inside an aggregate is a plain `string`, never a local VO duplicating that BC's concept (it can't enforce that BC's invariants and would drift).

## Tests

### Conventions

#### Naming
- Aggregate: success `it*`, failure `itCannot*`.
- Value Object: success `it*`, failure `itRejects*` / `itProtectsInvariants` (the violated condition lives in the test name or a data set label).
