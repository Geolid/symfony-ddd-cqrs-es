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
- A Query reads through an `Application/Finder/<Aggregate>/<X>FinderInterface` (with its `<X>Result` co-located), never through a Repository. A Command writes through a Repository (Domain); it may also read through a Finder if it needs to. A Finder parameter is a `string`/enum, never an identity Value Object — a read stays primitives end-to-end.
- A side effect reacting to an event (`#[Processor]`, whether the event is a Domain Event from the same BC or an Integration Event from another) carries zero business logic and must be replay-safe: it either dispatches a Command, calls an outbound port directly, or enriches by reading a Repository — never a Finder.
- A bulk operation is a `ForAll<X>` handler dispatching one `For<X>` Command per item — never an inline loop. Each item gets its own transaction.

**NEVER**
- Translate or persist an Integration Event from Application — Domain-to-Integration translation and appending to the store is the Translator's job (Infrastructure). Application only defines the contract (`Application/Event/`) and reacts to it (`#[Processor]`).
- Put a non-native-type field on an Integration Event — enforced by `Tools\PHPat\BoundaryMessageTest`.

### Conventions
- An application failure is a `final` exception extending `\RuntimeException`, implementing `Shared\Application\Exception\ApplicationExceptionInterface`; named static factory (`forId`, `forIdentifier`...).
- An Application port meant to be called directly by a Delivery Mechanism (bypassing the Command/Query bus) is marked `#[AsDrivingPort]` (`Shared\Application\Attribute`) — a pure marker read by phpat, zero DI effect.
- An Integration Event lives in `Application/Event/`, is `final readonly`, implements `IntegrationEventInterface`; named `#[Event('<subdomain>.integration_event.<aggregate>_<verb>')]`; tagged `#[PersonalData]`/`#[DataSubjectId]` when it carries personal data.
- A side-effect reaction lives in `Application/Processor/<Action>On<Event>`, named `#[Processor('<subdomain>.<bc>.<action>_on_<event>')]`.
- A Query's class documents its return contract: `@implements QueryInterface<TResult>` (otherwise `ask()` only returns `mixed`). Its return shape is one of: `ListResult<X>` (paginated), `StreamResult<X>` (streamed, for volume), `list<X>` (all), `?XResult` (one or none), `XResult` (exactly one).
- A Service holds pure logic (zero I/O) or extracts steps of a use case: inject it as the concrete class plus bound scalars. An interface is reserved for an actual I/O boundary (substitution/stub in tests).

## Tests

### Conventions

#### Naming
- Command Handler: success `it*`, failure `itFailsWhen*`.
- Query Handler: success `itGets*` / `itLists*` / `itPaginates*`, failure `itFailsWhen*`.
- Side effect (`#[Processor]`): `it*On*` (no dedicated failure shape — it must not fail).
