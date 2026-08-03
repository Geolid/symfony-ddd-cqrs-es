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
| `Fulfilment.Shipment` | ES | Shipment lifecycle, reacts to `Sales.Order` (created on `OrderPaymentCaptured`, not on bare `OrderPlaced`), drives the `AcmeCarrierGateway`, reacts to the carrier's delivery webhook. | `Sales.Order` |
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

The real dividing line turned out not to be "internal vs. external" but **interactive vs.
batch/bootstrap**: Web is for anything a human does live (browsing, filling a form); CLI is
for what must work *before* the app's own API auth exists (bootstrapping the first admin), pure
dev/demo tooling, and any recurring ops job (`dispatch-pending` — realistically a nightly cron,
not something a human confirms by clicking a dashboard button); API is for anything another
authenticated actor (including our own admin, using the API as their tool) does interactively
over the network without a live UI.

| DM | Who calls it | Auth | Scope |
|---|---|---|---|
| `web` | The customer, in a browser | Session, `PasswordCredential` | Self-service only: register, login/logout, browse `Catalog.Product`, place/cancel/pay for/view own orders, GDPR account deletion. No admin population here. |
| `api` | The admin, scripted or via a dashboard | Token, `ApiTokenCredential` | Supervision + catalog + fulfilment admin: view all orders and their status, view/list/reprice/delist products. Also proves Identity is credential-agnostic (same concept as Web, different credential). |
| `cli` | An internal operator with shell access, or `symfony/scheduler`'s internal worker invoking it unattended | None — trusted by construction | Bootstrap: create the first admin Identity/Credential/Grant (chicken-and-egg — nothing else can, since the API itself requires an Identity to authenticate). Plus the recurring `fulfilment:shipment:dispatch-pending` batch job (the "truck comes once a day" carrier hand-off). |
| `webhook` | The carrier, asynchronously | Shared secret (HMAC), not an Identity | Delivery status updates only. Deliberately NOT promoted to an Iam-authenticated caller — HMAC verifies message integrity from an external partner (like Stripe/GitHub webhooks), a different trust boundary than our own admin's Iam identity. No `Carrier` BC/Identity — redundant with what the webhook already proves. |

Rejected alternatives, kept here so they aren't re-proposed:
- Web hosting two Grant-differentiated populations (customer + staff) — dropped once the only
  candidate staff action (an agent placing a phone order) turned out not to exist as a real
  scenario; with no interactive staff action left, Web is customer-only.
- `dispatch-pending` as a plain admin API action ("the admin validates the hand-off") — tried,
  then reversed: no real ops team manually confirms a routine nightly batch job like that: it's
  exactly the kind of thing you automate away.
- `dispatch-pending` as an external `crontab`/k8s `CronJob` calling the CLI command — works, but
  `symfony/scheduler`'s `#[AsCronTask]` internalizes the same cron inside the app itself (still
  targets the *same* `fulfilment:shipment:dispatch-pending` console command via
  `RunCommandMessage` — no separate class), one less piece of external infra to wire per
  environment, and it's the idiomatic modern-Symfony way to do this.
- A `Carrier` Iam Identity so the carrier can call our API to confirm pickup — rejected, redundant
  with the webhook already covering "carrier tells us about a status change."

## End-to-end journey (once everything below is built)

1. A visitor registers (Web) → `RegisterIdentity` + `SetPasswordCredential` (Iam) →
   `RegisterCustomer` (Sales) → `LinkCustomerIdentity` (Sales, new event `CustomerIdentityLinked`
   — `CustomerRegistered` itself is never modified). Identity is created first: it's the root
   concept (an Identity can exist without ever being a Customer — e.g. the admin), never the
   reverse.
2. They log in (Web, session) → browse `Catalog.Product` (`ListProducts`) →
   `PlaceOrder(productId, quantity)` → price/label frozen into `OrderPlaced` at that instant. The
   order starts unpaid — no payment is requested automatically.
3. The customer clicks "Pay" on the order (Web) → `RequestOrderPaymentInterface` (driving port)
   calls the fake `PaymentGatewayInterface` synchronously and records `OrderPaymentRequested` with
   the provider's reference, shown on the order. Confirming the capture is a signed external
   callback (`POST /webhooks/payment-captured`) — nothing calls it automatically; the human plays
   the payment provider's role and triggers it themselves through the Webhook DM using the
   reference shown on the order (`demo/` seeds aggregates only, it never simulates an external
   actor calling one of our own DMs).
4. `Shipment` is created once `OrderPaymentCaptured` lands (not on bare `OrderPlaced`). A nightly
   cron (CLI, `fulfilment:shipment:dispatch-pending`) hands every pending shipment to the carrier
   → `AcmeCarrierGateway` → the carrier's delivery webhook is, the same way, triggered by hand
   through the Webhook DM (no demo simulation, no real Acme server) → the customer is notified.
5. The bootstrap admin Identity (created once via CLI) manages the catalog and supervises all
   orders through the API, authenticated with an `ApiTokenCredential` — proving the same Identity
   concept works for both a human session (Web) and a token (API).
6. The customer requests GDPR erasure (Web) → `CustomerErased` (already there) + `IdentityErased`
   cascading to their credentials.

## Architecture-pattern coverage this scenario is meant to close

Patterns already demonstrated elsewhere in the codebase are not repeated here (see git history /
prior session notes for the full inventory). Gaps this scenario specifically closes:

- **GDPR erasure outside `Sales.Customer`** — step 6 (`IdentityErased`). Today only
  `Sales.Customer` demonstrates erasure.
- **A second vendor Gateway** — `PaymentGatewayInterface`, alongside `AcmeCarrierGateway`.
- **A second proof that micro-aggregates aren't a one-off** — `OrderPayment` beside
  `Sales.Order`, echoing Iam's `Identity`/`PasswordCredential`/`ApiTokenCredential` split.

Deliberately **not** forced into existence for lack of a natural business reason: an Upcaster
and a "pure logic" Application Service (concrete-injected, no I/O). Manufacturing a case for
these would be the kind of over-engineering this project has repeatedly steered away from — if
a real need for one shows up while building the above, use it there; otherwise leave the gap
open and say so rather than inventing a contrived example.

(`#[AsDrivingPort]` no longer belongs on this list — `#24` landed three real examples:
`AuthenticatePasswordCredentialInterface`/`AuthenticateApiTokenCredentialInterface` for
credential verification, and `IssueApiTokenCredentialInterface` for the CLI's API key
generation — see `TODO.md`.)

See `TODO.md` for the concrete, ordered task breakdown implementing this scenario.
