---
paths:
  - "apps/*/src/**/*.php"
  - "apps/*/tests/**/*.php"
---

## Source

### Rules

**ALWAYS**
- HTTP/CLI input becomes a Command or Query and goes through the bus — a Delivery Mechanism calls an Application port directly only if that port is marked `#[AsDrivingPort]`.
- Input mapped to a Command/Query field is validated through the BC's own compound constraint (`Application/Validation/Valid<Name>`); a param outside that model (a URI variable, a filter) gets an `Assert` guard before dispatch.
- A closed vocabulary the DM has to name (a status, a type) is the BC's `Application/Status/<X>Status` enum, never the Domain `<X>State` enum — the Domain enum carries the aggregate's behaviour and `Domain/` is not part of a DM's exposition surface. Where the vocabulary isn't user input but the use case itself, the DM names no value at all: it dispatches a dedicated Query and the BC decides what the filter means.
- A webhook payload is verified (HMAC signature, `hash_equals`) and shape-checked *before* any Command is dispatched from it. Prefer a tolerant reader for unknown event types (acknowledge, don't error) over rejecting the whole request. An aggregate ID derived from the external payload is a deterministic `uuid5` on a bound namespace — a retry must resolve to the same ID. A payload that addresses an *existing* aggregate carries the external system's own reference, never ours: the DM resolves it through a dedicated Query before dispatching. A DM parsing more than one webhook shape shares them through its own abstract base.
- An API DM built without API Platform sets the route default `_format: json` — otherwise an error renders as HTML — and its OpenAPI spec sets `servers` to `/%kernel.app_id%`, or "Try it out" targets the gateway's host root instead of this app's prefix. A DM dispatched behind an intermediate Messenger bus (e.g. a webhook) needs a `kernel.exception` listener unwrapping `HandlerFailedException`, or the `exceptions.php` mapping is bypassed and every failure renders as a 500 — not needed when the DM calls the Command/Query bus directly.
- A DM catching a BC exception for its own presentation may render its own response, but never with a status code diverging from that exception's `exceptions.php` mapping.
- A web write goes through a Symfony Form: a `Form/<Name>Type extends AbstractType<XFormData>` bound to a plain `Form/FormData/<Name>FormData` (public typed properties, no logic) — never read straight off `$request->request`. The Type itself never reaches a bus: what a field needs (a choice list) is asked by the controller and handed over as a form option.
- A CLI command uses `LockableTrait`: guard with `if (!$this->lock())` (return `SUCCESS`), do the work in `try`/`finally { $this->release(); }` — otherwise a concurrent run double-dispatches.
- Every exposed API property carries a `description` + `example`. An externally-consumed DM additionally requires an exhaustive contract test (`assertSame` on the full property map) — the only place a silent drift is invisible to the consumer.
- An externally-consumed API is versioned (`routePrefix: /v<n>/<subdomain>`) with a security scope per operation (`security: is_granted('<subdomain>.<bc>:<action>')`); a scope exists only once the caller's identity provider grants it. A machine-to-machine caller restricted to a single known consumer is the one exception: gate it by network instead of by scope.
- Native `\assert()` narrows a value already guaranteed correct by the calling code, never external/user input. `Webmozart\Assert` stays reserved for a real invariant that must hold in production and throw a catchable exception.

**NEVER**
- Put business logic in a Delivery Mechanism.
- Re-implement validation that already exists on the BC side (a Command/Query constructor, a Value Object).
- Break an externally-consumed endpoint without a deprecation path or a version bump.

### Conventions
- Console command: `#[AsCommand('<subdomain>:<subject>:<verb>', description: '<phrase>')]` invokable (`__invoke()`) — never `extends Command`.
- Every Delivery Mechanism shares the same `bootstrap/Kernel.php` — what differs is `apps/<dm>/config/{bundles.php,routes.php,services.php,packages/}` layered on top of the global `config/`. `services.php` autowires the DM's own `src/` namespace — without it, a Controller/Processor/Command class isn't a container service and its constructor dependencies never get injected. A new DM starts from an empty `apps/<name>/{config,src}`, adds only what it needs, and gets a ruleset row in `deptrac_dm.yaml`.
- CSS/JS are served through AssetMapper (`apps/<dm>/config/packages/asset_mapper.php` + `apps/<dm>/importmap.php`), scoped to shared files under `ui/assets/`. `framework.form`/`asset_mapper` default to `false` globally (`config/packages/framework.php`) and are enabled only in the DM(s) that need them.
- API Platform: a Provider only calls `ask()`, a Processor only calls `dispatch()`; a read Resource exposes `static fromResult(<X>Result): self`; a command operation is `status: 204, input: false, output: false, processor:`.
- The DTO shape follows the receiving mechanism: API write `Input/*Input` (API Platform `input:`), Web read `Controller/QueryString/<Action>QueryString` (`#[MapQueryString]`, `<Action>` named after the controller action consuming it, never the resource), Web form write `Form/<X>Type` + `Form/FormData/*FormData`, Web JSON write `Controller/Payload/*Payload` (`#[MapRequestPayload]`, CSRF required), CLI `Console/Input/*Input` (properties carry both `#[Argument]`/`#[Option]` and their validation constraints, bound via `#[MapInput]` on `__invoke()` — validated automatically before the method runs), Webhook `*Payload` — each carries the BC's own validation compounds.

## Tests

### Rules

**ALWAYS**
- Compare against data re-read through the matching Finder when one exists for the resource under test.
- A surface gated by authentication/authorization asserts its own full access contract (e.g. missing credential, malformed credential, missing permission, correct permission — whichever apply to its authentication mechanism) in its own test file, even when the underlying security mechanism is shared with another surface.
- Each `Abstract<Dm>TestCase` overrides `createKernel()` to pass the app ID.
- An API Platform test case sets `protected static ?bool $alwaysBootKernel = false;` — otherwise the kernel reboots per test and any seeded in-memory stub is lost.
- A Web test case's client uses `disableReboot()` — otherwise the kernel reboots between requests and in-memory stubs reset.
- A Web DOM query — an assertion or an element lookup for interaction (a form to fill/submit, a button to click) — targets `[data-testid=...]` — the template carries that attribute; a test never selects on a structural CSS class or tag.
- All Messenger transports run synchronously in tests — no wait/retry.
- A webhook dispatches by matching the `webhook.routing` key against the `#[AsRemoteEventConsumer]` argument — never the remote event's own type name.

**NEVER**
- Unit-test a Delivery Mechanism — integration only.
- Substitute a shared-mechanism test on one surface for another surface's own access-contract test.

### Conventions

#### Naming

The name describes the interaction with that Delivery Mechanism's own surface (HTTP response, page shown, terminal output) — never an internal implementation detail.

**API** (JSON returned or command executed):
- Read success `itReturns*` / failure `itFailsTo*`
- Write success `itAccepts*` / failure `itFailsTo*` (invalid input, missing resource), `itRejects*` (access denied, business rule not satisfied)

**Web** (page rendered or action triggered):
- Display success `itShows*` / `itShows*By*` / `itShowsPaginated*`, failure `itRefuses*`
- Action success `it*` / failure `itRefuses*`

**CLI** (terminal output or task completed):
- Display success `itDisplays*` / `itDisplays*By*`, failure `itFailsTo*`
- Action success `it*` / failure `itFailsTo*`

**Webhook** (notification received from the external system):
- Reception success `itAccepts*` / failure `itFailsTo*`, `itRejects*`
