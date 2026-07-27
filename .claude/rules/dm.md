---
paths:
  - "apps/*/src/**/*.php"
  - "apps/*/tests/**/*.php"
---

## Source

### Rules

**ALWAYS**
- HTTP/CLI input becomes a Command or Query and goes through the bus — a Delivery Mechanism calls an Application port directly only if that port is marked `#[AsDrivingPort]` (see `application.md`).
- A webhook payload is verified (HMAC signature, `hash_equals`) and shape-checked *before* any Command is dispatched from it (see `apps/webhook/src/Controller/CarrierWebhookController.php`). Prefer a tolerant reader for unknown event types (acknowledge, don't error) over rejecting the whole request.
- Catching a BC exception locally in a DM is only for that DM's own presentation concern (e.g. flashing a message in `apps/web`) — the actual HTTP status mapping for exceptions is a framework-level concern, not something re-implemented per controller.

**NEVER**
- Put business logic in a Delivery Mechanism.
- Re-implement validation that already exists on the BC side (a Command/Query constructor, a Value Object).

### Conventions
- Console command: `#[AsCommand('<subdomain>:<subject>:<verb>')]` (see `apps/cli/src/Command/`).
- Every Delivery Mechanism shares the same `bootstrap/Kernel.php` — what differs is `apps/<dm>/config/{bundles.php,routes.php,packages/}` layered on top of the global `config/`. A new DM starts from an empty `apps/<name>/{config,src}` and only adds what it actually needs (see `deptrac_dm.yaml` for the ruleset a new DM must be added to).

## Tests

### Conventions

#### Naming

The name describes the interaction with that Delivery Mechanism's own surface (HTTP response, page shown, terminal output) — never an internal implementation detail.

**API** (JSON returned or command executed):
- Read success `itReturns*` / failure `itFailsTo*`
- Write success `itAccepts*` / failure `itFailsTo*` (invalid input, missing resource), `itRejects*` (access denied, business rule not satisfied)

**Web** (page rendered or action triggered):
- Display success `itShows*` / failure `itRefuses*`
- Action success `it*` / failure `itRefuses*`

**CLI** (terminal output or task completed):
- Display success `itDisplays*` / failure `itFailsTo*`
- Action success `it*` / failure `itFailsTo*`

**Webhook** (notification received from the external system):
- Reception success `itAccepts*` / failure `itFailsTo*`, `itRejects*`
