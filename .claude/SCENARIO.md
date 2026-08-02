# Scenario

Dev-only reference for the duration of the build — not part of the showcase deliverable,
empty/remove before final delivery alongside `TODO.md` and the ADR/memory registers.

Captures the *why* behind the current shape of the showcase so decisions aren't re-litigated
(and re-drifted) across sessions. If a decision below turns out wrong, correct it here rather
than silently diverging in code.

## Pitch

A small order/customer/shipment sales platform. A customer registers, browses a fixed
catalog, places an order, pays, and gets shipped by an external carrier — secured end to end
by a proper cross-cutting Identity & Access Management layer. The business story exists to
give every DDD/CQRS/Event-Sourcing pattern a believable reason to appear — it is not a
product, and no piece should be added without a concrete pattern or a concrete believability
gap it closes.

## Bounded Contexts

| Subdomain.BC | Nature | Role | Depends on (cross-subdomain) |
|---|---|---|---|
| `Sales.Customer` | ES | The buyer as a business record (register/erase). Carries an optional `identityId` pointing OUT to Iam — never the reverse. | — |
| `Sales.Order` | ES | The order lifecycle (place/cancel), `OrderLine` snapshots product data at placement time, `OrderPayment` (planned) as a sibling micro-aggregate. | `Sales.Customer` (buyer), `Catalog.Product` (planned, price/label snapshot) |
| `Catalog.Product` (planned) | ES | What exists to sell and at what price (`ProductListed`/`ProductRepriced`). A distinct business capability from Sales (merchandising vs. selling) — its own subdomain, not nested under `Sales`, mirroring how `Fulfilment` already sits beside `Sales` rather than inside it. | — |
| `Fulfilment.Shipment` | ES | Shipment lifecycle, reacts to `Sales.Order` (planned: to `OrderPaymentCaptured` rather than `OrderPlaced`), drives the `AcmeCarrierGateway`, reacts to the carrier's delivery webhook. | `Sales.Order` |
| `Iam.Identity` (planned wiring, BC done) | ES | Who (`Identity` + status) and how they prove it (`PasswordCredential`, `ApiTokenCredential`) — credential-type-agnostic by design. | — |
| `Iam.Access` (planned wiring, BC done) | ES | What an Identity may do (`Grant`/`Permission`, `<subdomain>:<action>` strings). Fully isolated from `Iam.Identity` — a Grant only ever carries a plain `identityId` string. | — |

Two corrections made along the way, kept here so they aren't relitigated:
- `Catalog.Product` is **not** a sibling aggregate inside `Sales.Order` (that pattern — see
  Iam — is for splitting *one* concept for aggregate-size reasons, not merging two distinct
  business capabilities), and **not** `Sales.Catalog` either (Catalog isn't owned by Sales).
  It's its own subdomain, connected the same way `Fulfilment.Shipment` already connects to
  `Sales.Order`: a sanctioned cross-subdomain `deptrac_bc.yaml` edge, not shared code.
- `OrderPayment`'s gateway is deliberately **synchronous** (immediate fake response, no
  webhook) — a second async webhook consumer next to the carrier's would be redundant
  illustration, not a new lesson, and it sidesteps the "how do you advance the story locally
  without a real external system" problem entirely for Payment.

## Delivery Mechanisms — each has a distinct reason to exist

| DM | Who calls it | Auth | Distinct responsibility |
|---|---|---|---|
| `web` | A human in a browser | Session, `PasswordCredential` | Two Identity populations, separated by **Grant**, not by DM: customer self-service (register, manage own orders) and staff/admin (assign grants, suspend identities). Proves Access/Grant models roles without needing a DM per role. |
| `api` | An external system (partner ERP, mobile backend) | Token, `ApiTokenCredential` | Machine-to-machine integration surface. Its real purpose is to **prove** the Iam design claim that Identity is agnostic to credential type — without a second, differently-authenticated caller actually exercising the same use cases, that claim stays theoretical. |
| `cli` | An internal, trusted operator (shell/server access) | None — trusted by construction, deliberately out of Iam's scope | Batch/ops jobs (`dispatch-pending`) and internal-agent scenarios (e.g. placing an order on behalf of a customer who called by phone) — never reachable by an external actor. |
| `webhook` | The carrier, asynchronously | Shared secret (HMAC-style), not an Identity | The only *passive/inbound* channel — a tier we don't control notifies us on its own schedule. Fundamentally different shape from the other three (nobody here "logs in"). |

## End-to-end journey (once everything below is built)

1. A visitor registers → `Customer` (Sales) + `Identity`/`PasswordCredential` (Iam), linked via
   `Customer.identityId`.
2. They log in (Web, session) → browse `Catalog.Product` → `PlaceOrder(productId, quantity)` →
   price/label frozen into `OrderPlaced` at that instant.
3. `OrderPayment` captures payment synchronously against the fake `PaymentGatewayInterface`.
4. `Shipment` is created once payment is captured (not on bare `OrderPlaced`) → CLI's
   `dispatch-pending` batch job hands it to `AcmeCarrierGateway` → the carrier's webhook later
   confirms delivery (simulated locally via a demo CLI command, since there is no real Acme
   server) → the customer is notified.
5. An external partner performs the same order/query flow via the API, authenticated with an
   `ApiTokenCredential` instead of a session.
6. An admin (Web, elevated Grant) suspends a suspicious Identity → cascades to revoking all its
   active API tokens via a `ForAll<RevokeApiTokenCredential>` bulk operation.
7. The customer requests GDPR erasure → `CustomerErased` (already there) + `IdentityErased`
   cascading to their credentials.

## Architecture-pattern coverage this scenario is meant to close

Patterns already demonstrated elsewhere in the codebase are not repeated here (see git history /
prior session notes for the full inventory). Gaps this scenario specifically closes:

- **`ForAll<X>` bulk pattern** — step 6 (suspend → cascade token revocation). No other example
  exists yet.
- **GDPR erasure outside `Sales.Customer`** — step 7 (`IdentityErased`). Today only
  `Sales.Customer` demonstrates erasure.
- **A second vendor Gateway** — `PaymentGatewayInterface`, alongside `AcmeCarrierGateway`.
- **A second proof that micro-aggregates aren't a one-off** — `OrderPayment` beside
  `Sales.Order`, echoing Iam's `Identity`/`PasswordCredential`/`ApiTokenCredential` split.

Deliberately **not** forced into existence for lack of a natural business reason: an Upcaster,
a "pure logic" Application Service (concrete-injected, no I/O), and a genuine bypass-the-bus
`#[AsDrivingPort]` example. Manufacturing a case for these would be the kind of
over-engineering this project has repeatedly steered away from — if a real need for one shows
up while building the above, use it there; otherwise leave the gap open and say so rather than
inventing a contrived example.

See `TODO.md` for the concrete, ordered task breakdown implementing this scenario.
