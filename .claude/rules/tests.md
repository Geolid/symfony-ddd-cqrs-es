---
paths:
  - "tests/**/*.php"
  - "apps/*/tests/**/*.php"
---

## Rules

**ALWAYS**
- Test methods carry the `#[Test]` attribute.
- Code is organized in three blocks separated by blank lines: `// Given`, `// When`, `// Then`. For an exception test, `// Then` (`expectException()`) comes before `// When`.
- Shared fixtures/test doubles are built in `setUp()`.
- Success and failure cases for the same behavior are grouped together, success first.

**NEVER**
- Mock an Aggregate or a Result object directly — build it through the matching Test Factory instead of a test double.
- Call `expects()` on a `createStub()` — a stub simulates a return value, a mock (`createMock()` + `expects()`) verifies a behavior.
- Suffix a variable with `Mock`/`Stub` — use the short class name (`$repository`) or the constructor argument's name when several dependencies share a type.

## Conventions

### Naming
- Method names: `it{Verb}*` — a short, readable sentence, no implementation detail, no quantifier ("Both", "Every").
- A test's name covers exactly what it asserts, nothing more — an assertion outside that scope belongs in a separate test.
- Failure naming: when the failure is a business rule, name the rule/condition that wasn't met; when it's a technical/architectural constraint, the implementation detail is what explains the name (that's fine).
- Data providers: `provide[Context]`, `#[DataProvider]` attribute, `yield` with a descriptive label.
- A class/enum created for a single test: `Dummy` prefix, declared at the bottom of the test file.

### Test Factory
- `<Aggregate>TestFactory extends Shared\Tests\Support\Factory\AbstractAggregateTestFactory`, one per aggregate, colocated at `tests/<Subdomain>/<Bc>/Support/Factory/` (see `Ordering\Tests\Order\Support\Factory\OrderTestFactory`).
- `defaults()` returns Faker-backed attributes; `build()` asserts their shape and calls the aggregate's own named factory (`Order::place(...)`) — never a bare constructor.
- `with*()` methods return a new instance via `static::new(array_merge(...))`; a state-transition modifier (e.g. `cancelled()`) uses `withModifier()` and calls the real aggregate method.
- `// Given` (state setup) is a factory method call; `// When` (behavior under test) is a direct call on the Aggregate returned by `create()`.

### Event Sourcing (`Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase`)
- `given(...events)->when(fn (Aggregate $a) => ...)->then(...expectedEvents)` — the standard shape for an aggregate unit test (see `tests/Ordering/Order/Domain/OrderTest.php`).
- `expectsException(...)` replaces `then(...)` for a failure case.

### CQRS / container (`Support\AbstractIntegrationTestCase`)
- `dispatch()` sends a Command to the Command bus; `ask()` runs a Query and returns its result.
- `service()` resolves a service from the container with a type assertion on the given class/interface.
- A Processor (a BC-to-BC reaction) is exercised directly — construct/fetch it and invoke it with the Integration Event it subscribes to — rather than relying on a running subscription worker in the test suite (see `tests/Shipping/Shipment/Application/Processor/CreateShipmentOnOrderPlacedTest.php`).
