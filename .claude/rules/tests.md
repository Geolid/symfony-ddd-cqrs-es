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
- Mock an Aggregate or a Result object directly — construct it through its real API (aggregate factory method, or a small local test factory) instead of a test double.
- Suffix a variable with `Mock`/`Stub` — use the short class name (`$repository`) or the constructor argument's name when several dependencies share a type.

## Conventions

### Naming
- Method names: `it{Verb}*` — a short, readable sentence, no implementation detail, no quantifier ("Both", "Every").
- A test's name covers exactly what it asserts, nothing more — an assertion outside that scope belongs in a separate test.
- Failure naming: when the failure is a business rule, name the rule/condition that wasn't met; when it's a technical/architectural constraint, the implementation detail is what explains the name (that's fine).

### Event Sourcing (`Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase`)
- `given(...events)->when(fn (Aggregate $a) => ...)->then(...expectedEvents)` — the standard shape for an aggregate unit test (see `tests/Ordering/Order/Domain/OrderTest.php`).
- `expectsException(...)` replaces `then(...)` for a failure case.

### CQRS / container (`Support\AbstractIntegrationTestCase`)
- `dispatch()` sends a Command to the Command bus; `ask()` runs a Query and returns its result.
- `service()` resolves a service from the container with a type assertion on the given class/interface.
- A Processor (a BC-to-BC reaction) is exercised directly — construct/fetch it and invoke it with the Integration Event it subscribes to — rather than relying on a running subscription worker in the test suite (see `tests/Shipping/Shipment/Application/Processor/CreateShipmentOnOrderPlacedTest.php`).
