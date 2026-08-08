# TODO

Reorganized 2026-08-08. Each item has enough pointer detail (file/class/method) to resume without re-researching. Where an item blocks another, that's noted inline.

## 1. Namespace/naming — undecided, don't touch `.claude/rules/*.md` until settled

These two are linked (both about Application's Gateway/Resolver-style ports) — resolve together, same session.

- **Resolver/Reducer "Stream" naming.** Bare `Resolver`/`Reducer` is ambiguous for future unrelated reuse. Two shapes floated, not chosen:
  1. Suffix the classes: `BuyerResolver`→`BuyerStreamResolver`, etc.
  2. Group under a shared folder instead, no class rename: `Infrastructure/Persistence/Stream/Resolver/`, `.../Stream/Reducer/` (or `EventStream/` — parent name also open).
  Touches `BuyerResolver`/`ProductResolver` + interfaces, `infra.md` wording, call sites (`PlaceOrderHandler`, `CancelOrderHandler`, `CreateShipmentOnOrderPaymentCaptured`, tests), `IdentityStatusReducer`.
- **Gateway namespace, concept-first vs generic.** Idea: mirror `Buyer`/`Product`'s concept-first grouping (`Application/Buyer/BuyerResolverInterface`) instead of a generic `Gateway/` bucket — e.g. move `PaymentGatewayInterface`+`PaymentSession` from `Application/Gateway/` into `Application/Payment/` (next to `OrderPaymentRequesterInterface`), mirrored on Infra as `Infrastructure/Payment/Globex/...`. Not settled: worth it over current kind-first `Gateway/`? Applies to `Fulfilment/Shipment/Application/Gateway/CarrierGatewayInterface.php` too, or Payment only?

## 2. Naming — decided, just execute (no blockers)

- `DerivedUuidTrait` (no `generate()`) for deterministically-derived ids (`PasswordCredentialId`, `OrderPaymentId`, `ShipmentId`), replacing `UuidTrait`. Update the 14 test call sites using `X::generate()` to derive from a generated foreign id instead (`X::forY(YId::generate()->toString())`). Update `domain.md`.
- Driving-port rename to role/actor noun, decided 2026-08-06, 4 sites: `AuthenticateApiTokenCredentialInterface`→`ApiTokenCredentialAuthenticatorInterface`, `IssueApiTokenCredentialInterface`→`ApiTokenCredentialIssuerInterface`, `AuthenticatePasswordCredentialInterface`→`PasswordCredentialAuthenticatorInterface`, `RequestOrderPaymentInterface`→`OrderPaymentRequesterInterface`. Verify names still current first.

## 3. Iam.Access — Grant/Permission lifecycle (one cluster, one decision needed)

- `Grant::revoke()` is terminal on that aggregate instance (double-revoke throws, no reactivate transition) — the only way back is `GrantPermissionHandler` creating a brand-new `Grant` id for the same identity+permission. **Decision needed**: add a real reactivate transition, or keep "always a new row" and add a uniqueness guard against an active duplicate coexisting with an already-revoked one. Same decision shape as Catalog.Product below — consider together.
- `GrantFinderInterface`/`DbalGrantFinder::buildBaseQuery()` hardcodes `revoked = 0` — no way to ever list a revoked grant, not even for an audit view. Decide if that's fine for the showcase.
- `ValidPermission`'s surface `Assert\Regex('/^\w+:\w+$/')` is looser than `Permission` VO's own webmozart pattern (`/^[a-z][a-z0-9_]*:[a-z][a-z0-9_]*$/`). A value passing the surface regex but not the VO's falls through to the generic "Domain validation failed" message instead of a field-specific one. **Concrete bug, no decision needed** — tighten the surface regex to match (or derive one from the other).
- `ValidPermissions`' `Assert\Count(min: 1)` forbids a zero-permission set — confirm intentional (scope-less key/identity disallowed by design) rather than oversight.

## 4. Catalog.Product

- `Product::delist()` is terminal, no relist transition — same shape as Grant above, same decision needed (relist transition vs "new row" + uniqueness guard against an active duplicate of an already-delisted product).
- `ValidPassword` / a Label VO for Catalog.Product — needs a real policy decision (password rules / label length), don't invent unilaterally.

## 5. Sales.Order / Sales.OrderSummary

- **Fold `Sales/OrderSummary` back into `Sales/Order`** — decided 2026-08-08. `OrderSummary` has zero Domain/Command/invariant (only Finder/Query/Projector) — not a real BC, a cross-aggregate read-model wearing a BC-shaped folder. Restore `GetOrder`/`ListOrders`-equivalent under `Sales/Order/Application/Query/`, fed by the same Integration Events `OrderSummary`'s projectors already consume (keep a dedicated flat table/Result if the list shape genuinely differs). Drop `Sales/OrderSummary` once migrated.
  - Bundle in while rebuilding: `OrderSummaryStatusTransformer::compute()` and `DbalOrderSummaryProjector` compare/write raw string literals (`'cancelled'`, `'requested'`, `'pending'`, `'dispatched'`) instead of `OrderState::CANCELLED->value` etc. — don't fix in isolation, this pair is getting rebuilt by the fold anyway.
- `Address`→`Email` field misnomer on Integration Events (Sales.Customer/Order, Fulfilment.Shipment) — needs an upcaster, real replay risk. Do alongside the `Address` VO composite below (same field).
- `Address` VO composite (street/postalCode/…) + make `Order::buyerAddress` required (currently `?string`).
- `OrderLine` micro-aggregate (add/remove/edit lines) to relieve `Order`.
- `ListOrderSummaries`' `status` filter exists on the Query but isn't wired into `OrderCriteria` (web) — unreachable by a real user.
- Missing notifiers: only `ShipmentDelivered` notifies the customer. Two open questions, not just "add more": (1) is "delivered" even the right moment vs "dispatched" (tracking becomes actionable at dispatch, delivery is just closure)? (2) should payment (requested/captured) notify the customer too?
- `PaymentSession` (`reference`+`checkoutUrl`, built by `GlobexPaymentGateway::requestPayment()`) has no expiry/TTL — nothing invalidates it over time. Needs a design decision (add `expiresAt`? who checks it?). Related: if `Order`/`OrderPayment` transitions away (e.g. cancelled) while the user sits on the sandbox checkout page, `CaptureOrderPaymentHandler` only guards via `OrderPaymentInvalidTransitionException` on the `OrderPayment` aggregate — not confirmed whether that covers the `Order` having moved to `Cancelled`.

## 6. Iam.Identity

- GDPR erasure cascade for `IdentityErased`: `login` stored in clear in `DbalPasswordCredentialProjector`, no erasure mechanism for this BC — needs trigger/wiring design.
- Orphan commands never wired to any DM: `SuspendIdentity`, `ReactivateIdentity`, `RevokeApiTokenCredential`. Direction floated: an admin-scoped surface in `apps/api`. **Blocks on / should be decided together with** `apps/es-admin` having no auth barrier below — wiring admin-only commands behind an unauthenticated surface is worse than leaving them orphaned.
- `apps/es-admin` has no auth barrier beyond `APP_ENV` gating (confirmed: no security config in `apps/es-admin/config/`) — needs a choice (HTTP Basic vs full Iam integration) before the item above.

## 7. Sales.Customer

- Registration UX gaps: no password-confirmation field (`RegisterCustomerType`), no auto-login post-registration (`CustomerController::register` redirects to login).

## 8. Tests — strategy & hygiene

- **DM (API) test strategy** — needs study, not decided. `dm.md` currently mandates every `*ResourceTest` re-assert its *full* access contract, even mechanism shared with other surfaces. Concretely, `ProductResourceTest`/`OrderResourceTest` each re-run the entire API-key battery (malformed/invalid/revoked/expired/suspended) via `AbstractApiTestCase` helpers — identical code path regardless of resource, no resource-specific variation possible (unlike the grant-scope checks, which genuinely are per-operation and should stay). Idea: keep `itRejectsAnUnauthenticatedRequest` per resource, move the key-state battery into one dedicated Authenticator-level test, strip the helper. Would revisit `dm.md`'s "NEVER substitute a shared-mechanism test" rule — don't touch that file until decided.
- `IncrementalRamseyUuidFactory` (patchlevel test utility) generates ids without guaranteeing a valid RFC4122 version nibble — breaks `Requirement::UUID` route matching non-deterministically by suite position. Worked around locally with `->withoutIncrementalIds()`, latent elsewhere. Root cause: `webmozart/assert` domain validation is non-strict on UUID shape, Symfony's `Assert\Uuid` is strict — factories default to non-strict unless opted out.
- Test naming/value audit, repo-wide:
  1. **Method names** — check `application.md`'s `it*`/`itGets*`/`itLists*`/`itFailsWhen*` convention actually holds uniformly per BC/DM. Found: `ProductResourceTest`/`OrderResourceTest` switch grammatical subject for the same kind of refusal (`itRejectsACallerWithoutTheReadGrant` = actor vs `itRejectsACreationWithoutTheWriteGrant` = action) — pick one. Same pass: check no test name leaks business/internal/implementation detail that doesn't belong at that altitude.
  2. **Test values** — literals should read as what they mean (`$3cr3t`, `"hash"`, `"new_hash"`, `"invalid-value"`), never arbitrary random strings. A `uuid` is always a real generated/derived id, never a fabricated literal.

## 9. Dead code

- Pass over every Command/Query/Finder repo-wide for zero-production-caller code (test-only method/filter/param is a smell). Precedent already fixed: `GetOrder`/`ListOrders` (`ac04a20`), `ValidShipmentStatus` (deleted, structurally wrong: closed on `ValidValueObject(ShipmentStatus::class, method: 'from')` assuming Application/Domain shared a value space they never guaranteed). Find the rest.

## 10. UI / UX

- Payment button placement on the order page needs revisiting.
- Shipment dates missing from the order view: show "dispatched on"/"delivered on", matching payment's "requested on"/"paid on".
- Notification copy too raw for a showcase — e.g. dumps two raw UUIDs inline ("your order 019fd7ad-... (shipment 69b0e365-...) has just been delivered"). Rewrite fictional notification texts more naturally.
- `apps/web` declares `enabled_locales: ['en', 'fr']` but there's no way to switch — no `_locale` route param, no `set_locale_from_accept_language`, no UI switcher anywhere. Add a real switch or drop the unused locale.

## 11. Messages / validation consistency

- Audit exception messages + `webmozart/assert` messages repo-wide for tone/format consistency (Domain/Application exception vs `Assert::` guard).
- Audit native `\assert()` calls — some likely guard what PHP's type system already guarantees (a typed constructor property), making them dead weight.

## 12. Process (sequence matters — see order below)

1. `.github/pull_request_template.md`'s `## Summary` bullet (`-`) has no hint text, unlike `Relates to / Fixes #` and `Test plan` which do — decided: add a short hint there (what changed and why), consistent with the other two sections. Exact wording still to write.
2. "Qualité académique" audit phase (12 agents, clean-code/SOLID/DRY/DDD, independent of `.claude/rules/`) was planned during the earlier full-repo audit but never run. Scope must explicitly include Eventual Consistency (projector lag, read-after-write on every Finder-backed Query) — not checked anywhere yet.
3. README.md rewrite (stale BC/DM counts, outdated `make` targets) — gated behind step 2 finishing. Don't start unprompted even once unblocked.
4. Rules meta-audit: full pass over `.claude/rules/*.md` for terseness, generic placeholders (not concrete-example dumps), no "enforced by `Tools\PHPat\...`" tooling-guarantee claims, restructure flat `ALWAYS`/`NEVER` bullet lists into real sections (Convention/Rule/Naming...). Do this **after** section 1's naming decisions land, or it'll need redoing.
5. Empty `.claude/memory/adr/`, `blockers.md`, `learnings.md`, `evals.md` — final step before delivery, once everything above has landed.

## 13. Other studies (no dependency, low urgency)

- PHP 8.4 property hooks (`get`) as a possible getter replacement — must confirm a hook can't accidentally widen an aggregate's mutation surface before using one.
- `README.md:149` references `.claude/memory/adr/`, already removed from `CONTRIBUTING.md` — fix in isolation vs fold into the README rewrite (step 12.3).

## 14. CI

- PR #26 (`claude/order-summary-consolidation`) — mutation testing was red: one `CastInt` mutant escaping on `DbalOrderSummaryFinder.php:95`, suspected-but-unconfirmed equivalent mutant. **Check `gh pr checks 26` first — this note may be stale.**
