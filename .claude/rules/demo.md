---
paths:
  - "demo/**"
---

## Source

### Rules

**ALWAYS**
- Seeder: `#[AsCommand('demo:<subdomain>:<subject>')] final readonly` invokable class, `demo/<Subdomain>/Seed<Subject>Command.php`. Auto-loaded (namespace `Demo\`, `demo` env) — the only registration is an entry in `demo/seeds.php`, in dependency order.
- Data is built via the BC's own Test Factory + `Demo\Shared\WeightedPicker` (weighted status distribution), then persisted through the Repository directly (`$repository->save($aggregate)`) — not the Command bus. Input is a DTO with `#[MapInput]` on the parameter, properties tagged `#[Option]`.
- Output: `progressStart`/`progressAdvance`/`progressFinish` plus a per-status count, summarized in a final `$io->success()`.

### Conventions
- `demo/console` is a dedicated entrypoint hardcoding `new Kernel('demo', ...)`.
- `demo/SeedCommand.php` (`demo:seed`) is the only orchestrator: it reads `demo/seeds.php` and runs each listed command through `Application::doRun()`.
- A seed command never dispatches a Command that only exists to create data for another BC to react to — a real Domain Event still fires (e.g. `OrderPlaced`), and any cross-BC fan-out (Processor, Reducer) reacts exactly as it would outside of a demo.
