# Learnings

Patterns observed while working on this project — what worked, what surprised, what we'll
repeat.

## Index

| # | Pattern | Context | Date |
|---|---------|---------|------|
| [LRN-001](#lrn-001) | Test Factories bypass UniqueValueRegistryInterface | Web integration test audit | 2026-08-15 |
| [LRN-002](#lrn-002) | Cross-BC Processors never fire inside a single test/request | Web integration test audit | 2026-08-15 |

---

## LRN-001

**Pattern:** Building an aggregate via TestFactory bypasses UniqueValueRegistryInterface; domain uniqueness guards live only in Application Handlers, never in aggregate constructors.

**Context:** Web integration test audit (apps/web/tests/Controller/*.php). Discovered when a test seeded "login/email already taken" via TestFactory, then expected a domain exception on registration—but got a PDOException instead, because the registry was never reserved.

**Future application:** To test "value already taken" scenarios, call `$this->service(UniqueValueRegistryInterface::class)->reserve($type, $value->fingerprint())` directly in the Given phase, rather than building a full aggregate via TestFactory. Only use TestFactory to build full aggregates when the test must also read them back afterward.

---

## LRN-002

**Pattern:** Cross-BC Processors (e.g., `EraseCustomerOnIdentityErased`) run asynchronously and never execute as a side effect of a single test/request, even in web-integration tests. Per the project's documented Processor testing convention, no test suite has a worker tick.

**Context:** Web integration test audit (apps/web/tests/Controller/*.php). Tested `/sales/customers/erase` expecting `Customer.email` to null via the cross-BC `EraseCustomerOnIdentityErased` reaction, but the state remained unchanged because that Processor was never invoked in-process.

**Future application:** After a Controller action, assert Finder state only for the BC whose command was directly and synchronously dispatched. Cross-BC side effects via Integration Events and Processors belong to that other BC's own dedicated Processor test, never re-asserted from a DM-level test in a different BC.
