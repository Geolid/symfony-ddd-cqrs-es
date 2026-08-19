---
name: dm-scaffold
description: Procedure to create a new Delivery Mechanism (apps/<dm>) or expose an existing use case through a DM (API/Web/CLI/Webhook).
when_to_use: Before any implementation under `apps/<dm>/`.
paths: apps/**
allowed-tools: Read, Grep, Glob, Edit, Write, Bash(make:*)
effort: low
---
## New DM

### 1. Choose the appId

Unique — list `apps/` for precedent.

### 2. Create the structure

- `apps/<dm>/src/` — new psr-4 entry in the root `composer.json` (`autoload` + `autoload-dev`), then `composer dump-autoload`
- `apps/<dm>/config/routes.php` (attribute routing over `apps/<dm>/src/Controller/`) for an HTTP-facing DM; skip it for a console-only DM. `bundles.php` only if the DM needs a bundle absent from the global `config/bundles.php` (e.g. Twig — see `apps/web`); `config/packages/*` only for config specific to that DM, not already covered by the shared `config/packages/`. `Kernel.php` always imports from `apps/<dm>/config/` once `appId` is set — the directory itself must exist even for a console-only DM with nothing else to put there; an empty `bundles.php` returning `[]` is enough.

### 3. Wire the isolation

`deptrac_dm.yaml`: a layer (collector `apps/<dm>/.*`) + a ruleset row listing the BCs actually granted (real imports only, minimum `[ Shared, Vendors ]`)

### 4. Tests

`apps/<dm>/tests/` — a plain PHPUnit test for a console command, a `KernelTestCase`/`WebTestCase` for an HTTP controller (compare with the closest existing DM). Add `<testsuite name="<dm>">` to `phpunit.dist.xml`.

### 5. Validate

`castor qa:deptrac --scope=dm` and `castor qa:stan` before exposing the first use case

---

## Exposing a use case in an existing DM

### 1. Check the BC prerequisite

The Command/Query must already exist on the BC side — implement it there first if not.

### 2. Implement

- Choose the exposition mode (API/Web/CLI/Webhook)
- Mirror the closest existing case (same mode, same DM, otherwise a neighboring DM)
- Integration test against the DM's test base class

### 3. Validate

`castor qa:stan` + `castor qa:test --filter=<Dm>`
