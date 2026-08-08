---
paths:
  - "src/**/Application/**/*.php"
  - "tests/**/Application/**/*.php"
---

## Source

### Rules

**ALWAYS**
- One folder per message: `Application/{Command,Query}/<Name>/` = `<Name>.php` + `<Name>Handler.php` (+ `<Name>Result.php` for a Query). The `Handler.php` suffix is mandatory — `SubdomainServiceLoader`'s glob silently skips anything else.
- A handler carries the project's own `#[AsCommandHandler]`/`#[AsQueryHandler]` attribute, never Symfony's `#[AsMessageHandler]`.
- A Query reads through an `Application/Finder/<Aggregate>/<X>FinderInterface` (with its `<X>Result` co-located), never through a Repository. A Command writes through a Repository (Domain); it may also read through a Finder if it needs to. A Finder parameter is a `string`, never an enum or an identity Value Object — a read stays primitives end-to-end.
- A side effect reacting to an event (`#[Processor]`, whether the event is a Domain Event from the same BC or an Integration Event from another) carries zero business logic and must be replay-safe: it either dispatches a Command, calls an outbound port directly, or enriches by reading a Repository — never a Finder.
- An outbound call belongs to a Processor reacting to a recorded fact, never to a Command handler: inside a transition the aggregate's invariant is checked after the call has already left, and the failure of a third party rolls back a decision that was ours to keep. What comes back is carried by a second Command.
- Acting on many items (a DM scanning for eligible ones, a Handler cascading to related aggregates) dispatches one Command per item through the Command bus, routed `async` in `messenger.php` — never mutated inline in a shared loop. Async is what actually isolates each item (its own transport message, its own transaction); a synchronous nested dispatch shares the caller's transaction, so one item's rollback poisons every sibling already processed in the same loop.

**NEVER**
- Translate or persist an Integration Event from Application — Domain-to-Integration translation and appending to the store is the Translator's job (Infrastructure). Application only defines the contract (`Application/Event/`) and reacts to it (`#[Processor]`).
- Put a non-native-type field on an Integration Event — enforced by `Tools\PHPat\BoundaryMessageTest`.

### Conventions
- An application failure is a `final` exception extending `\RuntimeException`, implementing `Shared\Application\Exception\ApplicationExceptionInterface`; named static factory (`forId`, `forIdentifier`...).
- An Application port meant to be called directly by a Delivery Mechanism (bypassing the Command/Query bus) is marked `#[AsDrivingPort]` (`Shared\Application\Port`) — a pure marker read by phpat, zero DI effect. The data crossing that boundary is `CommandInterface`, `QueryInterface`, `ResultInterface`, `ApplicationExceptionInterface`, `DrivingPortOutcomeInterface`, a validation compound (`extends Compound`), or an `Application/Enum/App<X>` enum. `App<X>` never proxies its Domain enum's case values. It may carry the same pure identity predicates as its Domain enum (`self::CASE === $this`) for a DM to gate a UI hint — never a transition/precedence rule, Domain-only.
- A validation compound (`Application/Validation/Valid<Name> extends Compound`) lists the Symfony `Assert` rules that carry the user-facing message, then closes on `ValidValueObject(<Vo>::class)` as a net. The Value Object keeps the last word; the asserts exist so the client reads *why* the field is refused rather than that the domain refused it.
- An Integration Event lives in `Application/Event/`, suffixed `*IntegrationEvent`, is `final readonly`, implements `IntegrationEventInterface`; named `#[Event('<subdomain>.<bc>.integration.<verb>')]` — same prefix as its Domain Event, an `integration` segment before the verb keeps the two names distinct in the shared store. Tagged `#[PersonalData]`/`#[DataSubjectId]` when it carries personal data.
- A side-effect reaction lives in `Application/Processor/<Action>On<Event>`, named `#[Processor('<subdomain>.<bc>.<action>_on_<event>')]` — snake_case of its own class name. It is invokable: `#[Subscribe(<Event>::class)]` sits on `__invoke()`, one reaction for one event, where a Projector or a Translator carries one `on<Event>()` per event.
- A Processor that creates an aggregate derives the identity from the triggering event (`uuid5` on a bound namespace) and its handler returns early when that aggregate already exists — otherwise a replay opens a duplicate.
- A Query's class documents its return contract: `@implements QueryInterface<TResult>` (otherwise `ask()` only returns `mixed`). Its return shape is one of: `ListResult<X>` (paginated), `StreamResult<X>` (streamed, for volume), `list<X>` (all), `?XResult` (one or none), `XResult` (exactly one).
- A Command/Query name referencing another aggregate names the concept (`By<Concept>`, `For<Concept>`), never its carrier field (`By<Concept>Id`) — the constructor argument itself stays `<concept>Id`.
- A Service holds pure logic (zero I/O) or extracts steps of a use case: inject it as the concrete class plus bound scalars. An interface is reserved for an actual I/O boundary (substitution/stub in tests).

## Tests

### Conventions

#### Structure
- A validation compound is exercised through `CompoundConstraintTestCase`, one data set per refused value, naming the rules it trips — the assertion compares the whole ordered list, so a rule dropped from the compound makes its data set fail. A value that trips the closing `ValidValueObject` alone is a missing `Assert`: the message the client gets is the Value Object's, not the field's.

#### Naming
- Command Handler: success `it*`, failure `itFailsWhen*`.
- Query Handler: success `itGets*` / `itLists*` / `itLists*By*` / `itPaginates*`, failure `itFailsWhen*`.
- Side effect (`#[Processor]`): `it*On*` (no dedicated failure shape).
