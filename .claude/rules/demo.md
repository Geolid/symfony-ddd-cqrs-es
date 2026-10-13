---
paths:
  - "demo/**"
---

## Source

### Rules

**ALWAYS**
- A demo scenario is a Foundry `Story` under `demo/Story/`, tagged `#[AsFixture(name: 'demo-<name>', groups: ['demo'])]` — auto-loaded, no further registration.
- Data is built through the BC's own factories (aggregate and Value Object) and stored through the Repository directly (`$this->store(...)`) — the Command bus only where the use case enforces an invariant the aggregate cannot (reserving a unique value), otherwise the seeded data violates it.
- A value the demo does not need to fix is drawn by the factory; only what a demo user depends on (a sign-in) is fixed.

**NEVER**
- Dispatch a Command whose only purpose is to make another BC react — store through the Repository directly and let the real Domain Event fire; cross-BC fan-out (Policy, Processor, Reducer) then reacts exactly as it would outside a demo.
