---
paths:
  - "demo/**"
---

## Source

### Rules

**ALWAYS**
- Seeder: `#[AsCommand('demo:<subdomain>:<subject>')] final readonly` invokable class, `demo/<Subdomain>/Seed<Subject>Command.php`. Auto-loaded (namespace `Demo\`, `demo` env) — the only registration is an entry in `demo/seeds.php`, in dependency order.
- Data is built via the BC's own Test Factory + `Demo\Shared\WeightedPicker` (weighted status distribution), then persisted through the Repository directly (`$repository->save($aggregate)`) — the Command bus only where the use case enforces an invariant the aggregate cannot (reserving a unique value), otherwise the seeded data violates it. Input is a DTO with `#[MapInput]` on the parameter, properties tagged `#[Option]`.
- Output: `progressStart`/`progressAdvance`/`progressFinish` plus a per-status count, summarized in a final `$io->success()`.

**NEVER**
- Dispatch a Command whose only purpose is to make another BC react — persist through the Repository directly and let the real Domain Event fire (e.g. `OrderPlaced`); cross-BC fan-out (Processor, StreamReducer) then reacts exactly as it would outside a demo.

### Conventions
- `demo/console` is a dedicated entrypoint hardcoding `new Kernel('demo', ...)`.
- `demo/SeedCommand.php` (`demo:seed`) is the only orchestrator: it reads `demo/seeds.php` and runs each listed command through `Application::doRun()`.
