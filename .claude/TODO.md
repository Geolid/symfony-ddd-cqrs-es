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

- **#24 — Authenticate DMs and add Access voters.** Web gets session-based login
  (`PasswordCredential` via a Symfony Authenticator/UserProvider using
  `GetPasswordCredentialByLogin`), API gets token-based auth (`ApiTokenCredential` via
  `GetApiTokenCredentialByIdentifier`). A custom Voter checks `Iam.Access` Grants for
  `<subdomain>:<action>` permissions. `Sales.Customer` gains an optional `identityId`
  (nullable, pointing to Iam, never the reverse). CLI and Webhook stay out of scope
  (trusted operator tool / transport-secret-verified respectively).

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
