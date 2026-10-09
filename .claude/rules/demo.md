---
paths:
  - "demo/**"
---

## Source

### Rules

**ALWAYS**
- A demo scenario is a Foundry `Story` — `demo/Story/<Name>Story.php`, `final`, extending `Support\Foundry\Story\AbstractAggregateStory`, tagged `#[AsFixture(name: 'demo-<name>', groups: ['demo'])]`. Auto-loaded (namespace `Demo\Story\`, `demo` env), no further registration.
- Data is built through the BC's own factories (aggregate and Value Object), then persisted through the Repository directly (`$this->persist(...)`) — the Command bus only where the use case enforces an invariant the aggregate cannot (reserving a unique value), otherwise the seeded data violates it.

**NEVER**
- Dispatch a Command whose only purpose is to make another BC react — persist through the Repository directly and let the real Domain Event fire; cross-BC fan-out (Policy, Processor, Reducer) then reacts exactly as it would outside a demo.

### Conventions
- The demo loads with `foundry:load-fixtures demo` under the `demo` castor context (`castor --context=demo sh -- php bin/console foundry:load-fixtures demo`); the `demo` env only isolates the database.
