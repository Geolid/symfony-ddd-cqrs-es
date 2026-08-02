# TODO

Dev-only tracking, not part of the showcase deliverable — empty/remove before final delivery
(see "Empty ADRs and memory registers before delivery" below).

Numbers matching the original session backlog (#1-31) are kept where known; unnumbered items
were surfaced later and have no original number.

## Done

- #14 Assert `PlaceOrderInput` schema — no gaps
- #19 Move cents out of human-facing surfaces (currency: euro)
- #21 Obtenir une CI verte
- #23 Scaffold BC Iam (Identity + Access)
- Rework Iam per domain-service-in-aggregate feedback (IdentityStatus, ExpiresAt,
  PasswordCredential/ApiTokenCredential renaming, SecretHasherInterface domain service)
- #27 (already done/merged per earlier session context)

## Pending

- **#18** — Add a `SentryContextProviderInterface` implementation per BC (the
  `#[AutowireIterator]` mechanism exists in `Shared/Infrastructure/Monitoring/Sentry/` but has
  zero real implementers today).

- **Add `Catalog.Product` as its own subdomain/BC.** Corrected twice during design: NOT a
  sibling aggregate inside `Sales.Order` (that pattern is for splitting ONE concept, like
  `Iam.Identity`, not two distinct business capabilities), NOT `Sales.Catalog` either (Catalog
  is a distinct business capability from Sales, not owned by it — merchandising vs. selling).
  Correct model: new top-level subdomain `Catalog`, BC `Catalog.Product`
  (`src/Catalog/Product/`), mirroring `Fulfilment.Shipment`'s existing cross-subdomain
  dependency on `Sales.Order`. `Sales.Order` gains a sanctioned `deptrac_bc.yaml` edge to
  `Catalog.Product` (same mechanism as `Fulfilment.Shipment -> Sales.Order`).
  Requires: new composer.json psr-4 entries (`Catalog\` -> `src/Catalog/`,
  `Catalog\Tests\` -> `tests/Catalog/`), new phpunit testsuite entry, new deptrac layer + edge.
  `Product{id, label, unitAmountInCents}`, ES events `ProductListed`/`ProductRepriced`.
  `PlaceOrder` takes `{productId, quantity}`; `OrderLine` snapshots label/price via a
  cross-subdomain Finder read at placement time (mirrors `BuyerResolver`'s role for
  `Sales.Order -> Sales.Customer`). Use the `bc-scaffold` skill when implementing.

- **#25 — Add `OrderPayment` as an ES micro-aggregate.** *(blocked by `Catalog.Product`: needs
  a real price to charge)* Sibling Aggregate Root in `Sales.Order` (second proof of the
  micro-aggregate pattern beyond Iam). A Processor reacts to `OrderPlaced`, calls a new fake
  `PaymentGatewayInterface` (second vendor Gateway example, deliberately **synchronous** — no
  webhook, avoids the async-simulation problem entirely). `Fulfilment.Shipment`'s trigger
  changes from "on `OrderPlaced`" to "on `OrderPaymentCaptured`" for a more realistic flow.

- **Add demo/CLI tooling to simulate the carrier delivery webhook.** Pre-existing gap,
  independent of Payment: `AcmeClient` points at a fake unreachable host
  (`https://carrier.acme.test`), and there is zero tooling to simulate an inbound carrier
  delivery webhook locally. Add a demo-only console command that replays the same
  payload/signed route `CarrierDeliveryConsumer` expects, so the full order→ship→delivered
  story is observable end-to-end locally without a real Acme server.

- **#24 — Authenticate DMs and add Access voters.** Final scope after extensive back-and-forth
  (see `SCENARIO.md`'s DM table for the reasoning) — Web is customer-only, API is admin-only,
  CLI is bootstrap-only, Webhook is untouched. Concretely:
  - `Catalog.Product` gaps found while scoping this out, needed first: **`ListProducts`** query
    (browsing needs a list, not just `GetProduct` by id — same Finder for Web and API, gated
    differently) and **`ProductDelisted`**/`DelistProduct` (removing a product doesn't exist yet).
  - `Sales.Customer` gains `identityId` via a **new** event `CustomerIdentityLinked` (never
    modify `CustomerRegistered`'s shape) + a reverse Finder (`Customer` by `identityId`, needed
    to resolve "my orders" from the logged-in Identity).
  - Web registration is a Controller-orchestrated sequence, not one mega-handler: `RegisterIdentity`
    → `SetPasswordCredential` → `RegisterCustomer` → `LinkCustomerIdentity`. Identity first — it's
    the root concept, a Customer optionally attaches to one, never the reverse.
  - Web (session, `PasswordCredential` via `GetPasswordCredentialByLogin` + `UserProviderInterface`):
    register, login/logout, browse products, place/cancel/view own orders, pay, GDPR self-erasure.
  - API (token, `ApiTokenCredential` via `GetApiTokenCredentialByIdentifier`): view all orders +
    status, view/reprice/delist/add products, validate the carrier hand-off (`dispatch-pending`
    **moves here from CLI** — an admin action, not a scheduler or cron; this touches existing,
    already-merged CLI code, done carefully). Existing customer-shaped API ops (`POST /orders`,
    per-order cancel) are removed — a customer never calls the API directly in this model.
  - CLI: new `iam:identity:register` (bootstrap the first admin Identity + Credential + Grants —
    the one thing that must work without the API's own auth already existing). Existing
    `sales:order:place`/demo seeding untouched, but demo seeders updated to also create and link
    an Identity for seeded customers (nothing has ever actually been seeded yet, but `make seed`
    must stay consistent once this lands).
  - The `UserInterface`/`UserProviderInterface` Symfony adapter is shared code (same Identity
    concept, two credential types) — lives in `src/Iam/Identity/Infrastructure/Security/`, not
    duplicated per DM. Firewall config stays per-DM (`apps/web/config/packages/security.php`
    session/form_login, `apps/api/config/packages/security.php` stateless + custom Authenticator).
  - A single generic Voter (`src/Iam/Access/Infrastructure/Security/GrantVoter.php`) matches any
    `<subdomain>:<action>` attribute against `Iam.Access` Grants for the current identityId —
    not one Voter per permission.
  - Explicitly rejected: two Grant-differentiated populations inside Web (no real staff action
    left to justify it), a `Carrier` Iam Identity (redundant with the webhook), stock/inventory
    management (a whole separate feature, deferred).

- **Add suspend-cascades-to-token-revocation (`ForAll` bulk pattern).** *(blocked by #24)*
  When an Identity is suspended, cascade-revoke all its active `ApiTokenCredential`s via a
  `ForAll<RevokeApiTokenCredential>` bulk handler — first concrete example of the "ForAll<X>"
  bulk pattern documented in `application.md` but never yet demonstrated.

- **Add Identity erasure cascade (GDPR).** *(blocked by #24)* `Iam.Identity` currently tags
  `PasswordCredentialSet`'s login as PersonalData/DataSubjectId but has no erasure event,
  unlike `Sales.Customer` (`CustomerErased`). Add `IdentityErased`
  (`DataSubjectErasureInterface`) cascading key-drop to `PasswordCredential`/
  `ApiTokenCredential` for that identity.

- **#26 — Rewrite README.** Held off deliberately until Iam (and its DM wiring) is finished,
  since the README needs to describe the final architecture/scenario, not an intermediate
  state.

- **Empty ADRs and memory registers before delivery.** Explicit instruction: "zéro adr ou
  registre dans le showcase, on fournit le showcase vide de tout registre" —
  `.claude/memory/adr/`, `blockers.md`/`learnings.md`/`evals.md`, and this `TODO.md` itself are
  dev-only working artifacts, must ship empty/removed.

- **#28/#29/#30 — Final audits.** Blocked by every item above landing first.

## Dependency order

1. `Catalog.Product` and `#24` have no dependency on each other — either can start first.
2. `#25` (OrderPayment) needs `Catalog.Product` first (a real price to charge).
3. The suspend-cascade and Identity-erasure-cascade items need `#24` first (auth wiring).
4. `#18`, README, and emptying the registers can happen anytime, but final audits wait on
   everything.
