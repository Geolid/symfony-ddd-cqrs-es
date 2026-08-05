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
- **`Catalog.Product` landed as its own subdomain/BC** — new top-level subdomain `Catalog`, BC
  `Catalog.Product` (`src/Catalog/Product/`), mirroring `Fulfilment.Shipment`'s existing
  cross-subdomain dependency on `Sales.Order`. `Product{id, label, unitAmountInCents}`, ES events
  `ProductListed`/`ProductRepriced`/`ProductDelisted`, commands `ListProductForSale`/
  `RepriceProduct`/`DelistProduct`, queries `GetProduct`/`ListProducts`. `PlaceOrder` snapshots
  label/price via `ProductResolver` (`Sales.Order -> Catalog.Product` Integration Events) at
  placement time, mirroring `BuyerResolver`'s role for `Sales.Order -> Sales.Customer`.

- **Web: pay is now a customer-triggered action, not an automatic `OrderPlaced` reaction —
  closes the checkout dead-end and reworks the Web UI around it.** `RequestOrderPaymentOnOrderPlaced`
  (the `#[Processor]` calling the fake Globex gateway automatically on `OrderPlaced`) is removed
  entirely: the customer now clicks "Payer" on their order. That call is an outbound I/O to a
  vendor, which per `application.md` must never sit inside a Command Handler's transaction — so
  it's a new `#[AsDrivingPort]`, `Sales\Order\Application\Payment\RequestOrderPaymentInterface`
  (mirrors `IssueApiTokenCredentialInterface`'s shape: a DM-callable port whose Infrastructure
  implementation, `OrderPaymentRequestingService`, reads the Order (Repository + Finder — the
  Finder for `customerId`/amount, the Repository for `buyerAddress` specifically to avoid ever
  projecting that PII), calls `PaymentGatewayInterface`, then dispatches the existing
  `RequestOrderPayment` command). Guards added: refuses a second payment request for the same
  order and refuses to request payment for a cancelled order (both via
  `OrderPaymentFinderInterface::ofOrder()`/the Order's own status) — `OrderPaymentAlreadyRequestedException`
  (new, 409) and the existing `OrderAlreadyCancelledException`. Symmetrically, `CancelOrderHandler`
  now refuses to cancel an order once a payment has been requested (same exception) — a real gap
  before this: nothing stopped cancelling an order that was already paid/shipped.
  `OrderControllerTest::itPaysForAnOrder` is the first Web test to exercise the Globex call
  synchronously inside a request (the `#[Processor]` group was already excluded from
  `run_after_aggregate_save` in `test`, so this path was never reached from a Web test before) —
  mirrors the codebase's own convention (`GlobexPaymentGatewayTest`, `OrderPaymentRequestingServiceTest`)
  of mocking the HTTP response explicitly in the test's own `// Given` (`self::getContainer()->set('globex.client', new MockHttpClient(...))`,
  `framework.test: true` makes this override just work) rather than widening
  `config/services/shared.php`'s dev/demo-only fake-client swap to `test` globally — first pass
  did that and got corrected during review, since a blanket env-level swap would silently cover
  for any other test on this path too, not just the one that actually needs it.
  Considered and rejected for confirming the payment: a `demo:*` command simulating the
  `payment-captured` webhook call — `demo/` is for seeding aggregates only, not for playing the
  role of an external actor calling our own DM; and Web calling the Webhook DM's endpoint
  directly — a DM translates *its own* real external actor's input, it doesn't impersonate
  another DM's caller. Instead: `Sales.Order` gains `GetOrderPaymentByOrder`
  (`OrderPaymentFinderInterface::ofOrder()`, `PublishedOrderPaymentStatus`) and
  `Fulfilment.Shipment` gains `GetShipmentByOrder` (`ShipmentFinderInterface::ofOrder()`) — a new
  `GET /sales/orders/{id}` detail page composes both read sides onto the same page as the order
  (a read-side rollup across aggregates, not a change to either aggregate's own domain status:
  `Order` itself still only ever has `placed`/`cancelled`) showing the payment reference + a hint
  pointing at `/webhook/docs`, and the shipment's status/tracking reference once one exists — the
  human sees exactly what a real Globex/carrier webhook call needs and drives both webhooks
  themselves through the Webhook DM, same as they would in a real integration.
  UI reorganized in the same pass, per explicit feedback that the previous layout was
  disorganized: `ShipmentController`/its route/template/nav entry are removed outright (shipment
  info now lives on the order detail page, no separate "Livraisons" tab); the order list gets
  Voir/Annuler/Payer actions per row and a "Passer une commande" button (moved out of the header
  nav, which only listed it as a bare link before); the header's bare "Se déconnecter" link
  becomes an account dropdown (`<details class="dropdown">`, no JS) holding "Changer de mot de
  passe" (new — `SetPasswordCredentialHandler` already upserts, so the existing command sufficed,
  no BC change needed), "Effacer mon compte" (moved out of the bottom of the order list — its
  CSRF token id also dropped the `-{customerId}` suffix, since Symfony CSRF tokens are already
  session-scoped and the shared header has no customer id in scope), and "Se déconnecter".
  Both `fulfilment.shipment.list.*` translations are gone with the page; the stale
  `fulfilment.shipment.list.empty` copy ("shipments are created automatically once an order is
  placed") that a previous pass had only partially fixed is now gone entirely along with the page
  it lived on. `SCENARIO.md`'s end-to-end journey and the `Fulfilment.Shipment` DM-table row
  updated to match (both webhooks are human-triggered through the Webhook DM, not
  demo-simulated; the `Fulfilment.Shipment` trigger is no longer marked "planned").

## Pending

- **#18** — Add a `SentryContextProviderInterface` implementation per BC (the
  `#[AutowireIterator]` mechanism exists in `Shared/Infrastructure/Monitoring/Sentry/` but has
  zero real implementers today).

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
  - ✅ `Catalog.Product` gaps found while scoping this out, needed first: **`ListProducts`** query
    (browsing needs a list, not just `GetProduct` by id — same Finder for Web and API, gated
    differently) and **`ProductDelisted`**/`DelistProduct` (removing a product doesn't exist yet).
  - ✅ `Sales.Customer` gains `identityId` via a **new** event `CustomerIdentityLinked` (never
    modify `CustomerRegistered`'s shape) + a reverse Finder (`Customer` by `identityId`, needed
    to resolve "my orders" from the logged-in Identity).
  - ✅ **Security infrastructure landed** (login for Web, API key for API, generic Voter) —
    with design corrections versus the plan below, refined further after comparing against
    `platform`'s actual `Iam.Network`/`ApiKey` implementation (same architecture, different
    literal code — reviewed for divergences, not copied):
    - `Tools\PHPat\DeliveryMechanismTest` forbids any DM class from depending on a BC's
      Repository/Domain classes directly (DM may only touch `#[AsDrivingPort]` ports +
      published language). So credential verification is NOT done inline in the DM's
      Authenticator; it's a new `#[AsDrivingPort]` port per credential type
      (`Iam\Identity\Application\Security\AuthenticatePasswordCredentialInterface` /
      `AuthenticateApiTokenCredentialInterface` — named `Application/Security/`, not
      `Application/Port/`, mirroring how `Application/Gateway/CarrierGatewayInterface` names
      its folder after the role, not the architecture jargon), `authenticate(...): ?string`
      returning the identityId or null, implemented in
      `Iam\Identity\Infrastructure\Security\*AuthenticationService` (loads the real aggregate
      via Repository + `SecretHasherInterface` — pure credential verification only; see below,
      this used to also check identity status but that moved out).
    - **API key format**: `<identifier>.<secret>` confirmed against `platform`'s own
      `Iam.Network.ApiKey` (same format there). Header: switched from `Authorization: Bearer`
      to `X-Api-Key` — a static, non-expiring, non-OAuth credential is conventionally a custom
      header (`platform` uses `X-API-KEY`); `Authorization: Bearer` is for an OAuth2/ephemeral
      access token, which this isn't.
    - **Account-status gating moved to a `UserCheckerInterface`**
      (`Iam\Identity\Infrastructure\Security\IdentityStatusUserChecker`, shared by both
      firewalls) instead of living inside the two `*AuthenticationService` classes — Symfony's
      dedicated extension point for "can this account authenticate at all", separate from "is
      this secret correct". `UserCheckerInterface` only runs at authentication time though, not
      on every request — so `Web\Security\IamUserProvider::loadUserByIdentifier()` (called by
      `ContextListener::refreshUser()` on every request for a session) independently re-checks
      via a new `GetIdentity` query + `PublishedIdentityStatus::isActive()` (Application/Language
      enum, not the Domain one), closing a live Web session the moment the identity is
      suspended — the stateless Api firewall re-authenticates via the UserChecker every request
      anyway, so needs no separate check.
    - **Verify via the aggregate vs. the read model** — deliberately kept as verify-via-aggregate
      (`platform` verifies against the read-model's hash directly in a `CheckPassportEvent`
      listener). Not "because it's a rule" — it's a genuine trade-off (zero staleness loading the
      aggregate vs. a fast read-model hit), and for `PasswordCredential`/`ApiTokenCredential`
      specifically (2-3 events each) the aggregate load is essentially free, so there's no reason
      to accept even a small staleness window. `platform`'s choice is a legitimate, deliberate
      trade-off at their scale/hot-path, not a mistake to avoid copying.
    - **Permissions cached on the `UserInterface`, not re-queried per check** — `IamUser`
      (`apps/web`, `apps/api`) carries `getRoles(): ['ROLE_USER', ...$grants]`, populated once
      at authentication (`IamUserProvider`/`ApiTokenAuthenticator` each call
      `ListGrantsForIdentity`). `GrantVoter` (`src/Iam/Access/Infrastructure/Security/GrantVoter.php`)
      became a **zero-dependency** class: `\in_array($attribute, $token->getRoleNames(), true)` —
      no QueryBus call per `is_granted()`, reusing Symfony's own native role mechanism instead of
      inventing a parallel `grants()`/custom interface (mirrors `platform`'s `NetworkCaller`
      caching scopes on the User at auth time, though they still use a bespoke `getScopes()`
      rather than roles). `IamUser` is deliberately **not** shared between DMs regardless — a
      `Request`-coupled `UserInterface` implementation can't live in `src/` (delivery vendor
      code), so it's a small, duplicated class per DM, same shape as the DTO-per-DM convention
      already established for Input/Payload/Criteria.
  - ✅ **Design corrections from review, after the above landed:**
    - **`sales:supervise`/`fulfilment:supervise` split into `read`/`write`** — a single
      catch-all grant per subdomain was too coarse. `sales:read`/`fulfilment:read` gate the
      read-only operations (`OrderResource`, `ShipmentResource`'s `GetCollection`);
      `fulfilment:write` gates the one mutation (`ShipmentResource`'s `dispatch`). Every
      fixture/example permission string in tests moved off `supervise` too.
    - **API key identifier/secret generation moved out of the CLI** — `RegisterIdentityCommand`
      (a DM) generated the random `bin2hex(random_bytes(...))` pair inline, which is business
      logic a DM must never carry. Extracted to a third `#[AsDrivingPort]` example,
      `Iam\Identity\Application\Security\IssueApiTokenCredentialInterface` (+ `IssuedApiKey`,
      a `PublishedLanguageInterface` DTO), implemented by
      `Iam\Identity\Infrastructure\Security\ApiTokenCredentialIssuingService` — the CLI now only
      dispatches `RegisterIdentity`/`GrantPermission` and asks the port to issue the credential.
    - **`ValidPermissions` compound replaces inline `Assert\All`** — `RegisterIdentityInput::$permission`
      had `#[Assert\All([new ValidPermission()])]` directly on the DM's Input class, repeating a
      validation shape a BC should own once. `Iam\Access\Application\Validation\ValidPermissions`
      (`Type('array')` + `Count(min: 1)` + `All([...])`, mirroring `Sales.Order`'s
      `ValidOrderLines`) now carries the whole shape, including the previously-missing
      "at least one permission" check.
  - ✅ **Routes gated behind `is_granted(...)`.**
    - Web: `OrderController` requires `IS_AUTHENTICATED_FULLY` throughout; `list()`/`place()`
      resolve the customer from the logged-in Identity (`GetCustomerByIdentityId` +
      `ListOrders(customerId: ...)`, using the pre-existing `OrderFinderInterface::withCustomer()`
      filter) instead of a buyer dropdown — a customer only ever sees/places/cancels their own
      orders. `cancel()` additionally checks `$order->customerId === $customer->id` before
      dispatching (an IDOR gap the old dropdown-based flow didn't have to worry about, since
      identity wasn't wired yet). `CustomerController::list()` (view-all-customers, a staff
      screen) is **removed** — `SCENARIO.md` settled Web as customer-only, no admin population,
      so it has no home there anymore. `erase()` no longer takes an arbitrary `{id}`: it derives
      "myself" from the logged-in Identity, so a customer can only erase their own account (the
      old route let any authenticated caller erase any customer by guessing a UUID — same class
      of gap as the order-cancel one). `ShipmentController::list()` is kept (not filtered to "my
      shipments" — a judgment call, flagged to the user rather than assumed) but now requires
      `IS_AUTHENTICATED_FULLY` too.
    - API: `OrderResource`/`ShipmentResource`'s remaining operations carry
      `security: "is_granted('sales:read')"` / `"is_granted('fulfilment:read')"` (`fulfilment:write`
      for the dispatch mutation) per dm.md's convention. `ApiTokenAuthenticator` gained `AuthenticationEntryPointInterface::start()`
      (a clean 401 when the header is missing entirely) — without it, a fully anonymous request
      hitting a gated operation had no clear 401/403 split, since `supports()` returning false
      means the authenticator never engages and Symfony has no entry point to fall back to.
      `PasswordCredentialAuthenticator` (Web) got the equivalent for a redirect-to-login instead
      of a raw 403 page.
    - Existing customer-shaped API ops (`POST /orders`, per-order cancel) are **removed**
      (`PlaceOrderProcessor`, `CancelOrderProcessor`, `Api\Input\PlaceOrderInput`) — a customer
      never calls the API directly in this model.
    - `ProductResource` gets its admin write side: `POST /products` (`catalog:write`,
      `ListProductForSaleProcessor`, returns the created `ProductResource` — mirrors
      `PlaceOrderProcessor`'s shape, id generated server-side via `Uuid::uuid7()`), `POST
      /products/{id}/reprice` (`RepriceProductProcessor`, `input`/`output: false` split — a body
      but no return, `204`) and `POST /products/{id}/delist` (`DelistProductProcessor`, no body
      at all — mirrors the removed `DispatchShipmentProcessor` shape exactly: `input: false,
      output: false`). `Api\Input\ListProductForSaleInput`/`RepriceProductInput` reuse
      `Shared\Application\Validation\ValidMoney` for `unitAmountInCents` (paired with an explicit
      `Assert\NotNull` — the compound alone treats a missing value as valid, by design, since it's
      also meant for optional money fields). `config/packages/exceptions.php` gained a `// Catalog`
      section (`ProductNotFoundException` → 404, `ProductAlreadyDelistedException` → 409) — it
      was missing entirely, so both fell through to the generic 422 `DomainException` mapping.
    - **`ApiKeySecurityOpenApiFactory`** (`apps/api/src/OpenApi/`) reviewed: a standard
      `#[AsDecorator(decorates: 'api_platform.openapi.factory')]` on `api_platform.openapi.factory`
      adding an `apiKey`/`X-API-KEY` `SecurityScheme` so Swagger UI's "Authorize" button works —
      this is API Platform's own documented recipe for this need, not a divergent pattern; no
      changes made.
  - ✅ **Web registration flow** — `CustomerController::register()` is now a Controller-orchestrated
    sequence, not one mega-handler. Actual dispatch order: `RegisterCustomer` (has the existing,
    tested `AddressAlreadyRegisteredException` handling) → `RegisterIdentity` → `SetPasswordCredential`
    → `LinkCustomerIdentity`. This deviates from the "Identity first, it's the root concept" framing
    in `SCENARIO.md` — that's a statement about the *domain model* (a Customer's `identityId` is
    optional, an Identity never references a Customer back), not a mandate on dispatch order in
    this one Controller. Registering the Customer first means the most likely realistic failure
    (duplicate email) is caught before any Identity/PasswordCredential exists, avoiding an orphaned,
    login-blocking Identity if Customer registration fails after Identity creation already
    succeeded. A `password` field was added to the registration form (`RegisterCustomerType`); the
    customer's login is their email.
  - ✅ **CLI bootstrap**: new `iam:identity:register` (`RegisterIdentity` + `IssueApiTokenCredential`
    + `GrantPermission` per `--permission`). Issues an **`ApiTokenCredential`**, not a
    `PasswordCredential` — `SCENARIO.md` is explicit that the bootstrap admin authenticates via API
    token ("proving the same Identity concept works for both a human session and a token"), so a
    CLI-created admin has no way to log into Web at all, which is the correct shape (Web is
    customer-only). The identifier/secret pair is randomly generated and printed once (shown-once
    UX, like a real API key issuance flow) rather than accepted as CLI arguments (would leak into
    shell history).
  - ✅ **`dispatch-pending` reconsidered, then left in CLI.** Tried moving it to a gated API
    admin action; reversed after review — realistically the nightly carrier hand-off is a cron
    job, not something an admin manually confirms through a dashboard. See `SCENARIO.md`'s DM
    table/rejected alternatives.
  - ✅ **`POST /shipments/{id}/dispatch` (single-shipment API operation) removed entirely** — no
    real admin action in this scenario ever needs to expedite one specific shipment (no VIP/urgent
    case anywhere in the story); the only real trigger is the nightly batch. Kept without a
    concrete reason, it was CRUD-completism the project's own anti-over-engineering stance argues
    against. `DispatchShipmentProcessor` (Infrastructure) removed with it; `DispatchShipment`
    Command/Handler (Application) stays — still the target of the CLI's per-shipment dispatch.
  - ✅ **`sales:order:place` (CLI) removed entirely** — its own description already said "demo/local
    seeding," yet it lived in `apps/cli` mixed into the real DM surface, contradicting the CLI
    dividing line this session settled (bootstrap + recurring ops jobs only, no ad-hoc dev/demo
    actions). Redundant with `demo:sales:orders` (the sanctioned seeder) for actually seeding data,
    so removed rather than relocated to `demo/`.
  - ✅ **Demo seeders now create/link an Identity for seeded customers** — `demo:sales:customers`
    additionally dispatches `RegisterIdentity` + `SetPasswordCredential` + `LinkCustomerIdentity`
    per seeded customer, so `make seed` produces customers that can actually log in (fixed demo
    password, printed once at the end of the command).
  - Explicitly rejected: two Grant-differentiated populations inside Web (no real staff action
    left to justify it), a `Carrier` Iam Identity (redundant with the webhook), stock/inventory
    management (a whole separate feature, deferred).

- ✅ **`dispatch-pending` moved to `symfony/scheduler`, no new class needed.** Reconsidered again
  after landing it in CLI (see the reversal noted above): a CLI command still needs *something*
  external to trigger it on a schedule, and that's exactly what `symfony/scheduler` is for —
  internalizing the cron inside the app instead of relying on an external crontab/k8s `CronJob`.
  Landed as `#[AsCronTask('0 0 * * *')]` directly on the existing `DispatchPendingShipmentsCommand`
  — `AddScheduleMessengerPass` detects a `console.command`-tagged service and schedules a
  `RunCommandMessage` targeting it directly, so there's no separate Task/message class, no
  duplicated logic: one implementation, invoked either by hand or by the scheduler's worker
  (`messenger:consume scheduler_default`). `DispatchShipment` (dispatched once per pending
  shipment inside that command's loop) is routed `async` in `messenger.php` (mirrors
  `CreateShipment`) — without it, each dispatch was a *nested* Messenger call sharing the
  outer/caller's DBAL transaction (no savepoints configured), so one bad shipment's rollback would
  have silently poisoned every sibling already processed in the same loop.
  Two false starts corrected along the way, kept here so they aren't retried: (1) a
  `DispatchForAllShipments` "bulk Command" wrapping the loop — misapplied the `ForAll<X>` naming
  from `application.md`, which was itself over-fitted from a single past example; a Command whose
  entire body is dispatching other Commands is a smell (a Handler carries one business decision,
  not a batch-scan trigger) — the rule is corrected in `application.md` to a general principle
  (route each item's Command `async`, whoever the caller is) instead of a named pattern requiring
  a demonstrated example; (2) a dedicated `Infrastructure/Scheduler/*Task` class calling a shared
  driving port — unnecessary once `#[AsCronTask]` on the console command itself covers it.
  The planned suspend-cascades-to-token-revocation feature stays dropped — not because of any
  pattern-coverage argument (there was never a real one), just deprioritized.

- **Add Identity erasure cascade (GDPR).** *(blocked by #24)* `Iam.Identity` currently tags
  `PasswordCredentialSet`'s login as PersonalData/DataSubjectId but has no erasure event,
  unlike `Sales.Customer` (`CustomerErased`). Add `IdentityErased`
  (`DataSubjectErasureInterface`) cascading key-drop to `PasswordCredential`/
  `ApiTokenCredential` for that identity.

- ✅ **Naming collision: `Command\ListProduct` vs `Query\ListProducts`.** Flagged during
  review — the write-side Command and the read-side Query (browse the catalog) differed only by
  a trailing `s`. Resolved by renaming the Command to `ListProductForSale` — keeps the verb
  (`Product::list()`/`ProductListed`/`DelistProduct` untouched, still correct marketplace
  terminology), just disambiguated from the Query.

- **#26 — Rewrite README.** Held off deliberately until Iam (and its DM wiring) is finished,
  since the README needs to describe the final architecture/scenario, not an intermediate
  state.

- **Empty ADRs and memory registers before delivery.** Explicit instruction: "zéro adr ou
  registre dans le showcase, on fournit le showcase vide de tout registre" —
  `.claude/memory/adr/`, `blockers.md`/`learnings.md`/`evals.md`, and this `TODO.md` itself are
  dev-only working artifacts, must ship empty/removed.

- **#28/#29/#30 — Final audits.** Blocked by every item above landing first.

- **Consolidate `Sales.Order`'s read side into a `Sales.OrderSummary` composite BC (rename/expand
  `Sales.OrderTracking`), replacing per-field N+1 querying with one denormalized report.**
  `OrderController::list()` currently dispatches `ListOrders` then loops `GetOrderTracking` once
  per order (N+1 query-bus dispatch); `show()` dispatches four separate queries (`GetOrder`,
  `GetOrderLines`, `GetOrderPaymentByOrder`, `GetShipmentByOrder`). `Sales.OrderTracking` already
  exists as a composite projection (subscribes to `Sales.Order`'s and `Fulfilment.Shipment`'s
  Integration Events, resolves a unified `status` via `OrderTrackingStatusResolver`) with a batch,
  paginable/filterable query (`ListOrderTrackings`) the Web controller never actually calls — but
  its `OrderTrackingResult` only carries `orderId/customerId/status/placedAt`, not enough to
  replace `ListOrders` outright (`list.html.twig` also needs `totalAmountInCents`, already present
  on `OrderPlacedIntegrationEvent` and simply not captured by
  `DbalOrderTrackingProjector::onOrderPlaced`).
  Real intent surfaced during review: this isn't "add one field to Tracking", it's "the
  customer/admin-facing report was always meant to be Order + OrderLine + Payment + Shipment
  composited, not just a unified status" — `OrderTracking` undersold the actual scope. Rename to
  `Sales.OrderSummary`, widen `sales_order_tracking` into `sales_order_summary` (`order_id,
  customer_id, total_amount_in_cents, order_status, placed_at, cancelled_at, payment_status,
  payment_amount_in_cents, payment_reference, paid_at, shipment_status, tracking_reference,
  dispatched_at, delivered_at` — keep every lifecycle date, not just the ones that looked like
  headline fields at first pass: cancelled/dispatched need their own date shown in "my orders"
  same as delivered/paid do) plus a child table `sales_order_summary_line` (`order_id, position,
  label, quantity, unit_amount_in_cents`, fed by `OrderPlaced.lines`, already present in the
  payload). `ListOrderSummaries`/`GetOrderSummary` replace `ListOrders`+loop and the four-query
  `show()` respectively.
  Both `apps/web` and `apps/api` become consumers of the same composite: `apps/api`'s
  `OrderResource` currently exposes neither payment nor shipment info (no admin visibility on
  either), and `ShipmentResource` leaks `orderCancelledAt` (an `Order`-level fact on a `Shipment`
  resource) while carrying zero payment info itself — `OrderResource::fromResult(OrderSummaryResult)`
  picks up both refs (`paymentReference`/`trackingReference` — "the 2 refs" an admin actually
  needs) and `ShipmentResource` is dropped outright once merged. Checked and ruled out keeping
  `ShipmentResource` alive for a separate "logistics" API consumer: `DispatchShipment` is
  CLI/cron-triggered internally (`apps/cli/DispatchPendingShipmentsCommand`, no external caller),
  the only external inbound flow is the carrier's delivery webhook
  (`Webhook\Consumer\CarrierDeliveryConsumer`) — no external actor ever reads the API for shipment
  status, so there's no consumer left to justify a split resource. (Separately verified not a gap:
  nobody currently holds `sales:read`/`fulfilment:read` in `demo/`'s seeders, but the provisioning
  mechanism already exists and is generic — `iam:identity:register --permission sales:read
  --permission fulfilment:read` via the existing `GrantPermission` command — just never run for a
  demo admin; no code change needed there.)

- **`DbalOrderLineFinder::allForOrder` doesn't follow the Finder convention.** Per
  `infrastructure.md`, a Finder returning more than a single row belongs on `AbstractDbalFinder`
  with `with*(): static` filters (see `DbalOrderTrackingFinder`'s `withCustomer()`/`withStatus()`),
  not an ad-hoc method on a `final readonly` class with a direct `Connection`. Rewrite as
  `withOrder(string $orderId): static` + `buildBaseQuery()` (`ORDER BY position ASC`);
  `GetOrderLinesHandler` calls `iterator_to_array()` instead of the removed `allForOrder()`. Small,
  independent of the `OrderSummary` item above — can land first or separately.

- **Let a placed-but-unpaid order have its lines modified (add/remove/change quantity),
  recalculating the total — currently orders are immutable after `place()`.** Surfaced while
  discussing why `OrderLine` is a read-side Finder family today: `Order::place()` doesn't even
  keep `$lines` in the aggregate's own state after construction (`applyOrderPlaced` only keeps
  `totalAmount`), so nothing today could target "line 3 of order X" even if a Command existed. If
  this lands: `OrderLine` needs a stable identity, gated the same way `cancel()` is
  (`OrderPaymentAlreadyRequestedException` — modifiable only while payment hasn't been requested).
  Implement as a **micro-aggregate**, not a `ChildAggregate` — `Patchlevel\EventSourcing\Attribute\ChildAggregate`
  /`Aggregate\ChildAggregate` has been `@experimental` for 2 years and is what the library's own
  `#[Stream]` attribute was built to replace (confirmed against `patchlevel/event-sourcing@f69120c`,
  "poc replace child aggregates with micro aggregates" — the vendor's own `PersonalInformation`/
  `Profile` test fixture was rewritten from `ChildAggregate` to a sibling `AggregateRoot` +
  `#[Stream(Profile::class)]`). Note the cardinality trap: `#[Stream(X::class)]` co-locates by
  **sharing the same aggregate id** as the target (that's how `Profile`/`PersonalInformation` — a
  genuine 1:1 — stay atomic); `OrderLine` is 1:N per order, so the micro-aggregate is the
  **collection root** `OrderLines` (one instance per `Order`, same `OrderId`,
  `#[Aggregate('sales.order.lines')] #[Stream(Order::class)]`, internal `list<OrderLine>`), not one
  micro-aggregate instance per line — a dedicated `OrderLinesRepositoryInterface` loads/mutates/
  saves the whole thing per edit. Codebase convention is `implements AggregateRoot,
  AggregateRootMetadataAware` + `use AggregateRootAttributeBehaviour` (see `Order.php`/
  `OrderPayment.php`), never `extends BasicAggregateRoot` (that's the vendor's own test-fixture
  style). If this lands, `sales_order_summary_line` above gains a feed from whatever line-changed
  event this introduces, on top of `OrderPlaced.lines`.

## Dependency order

1. `Catalog.Product` and `#24` have no dependency on each other — either can start first.
2. `#25` (OrderPayment) needs `Catalog.Product` first (a real price to charge).
3. The Identity-erasure-cascade item needs `#24` first (auth wiring).
4. `#18`, README, and emptying the registers can happen anytime, but final audits wait on
   everything.
5. The `OrderSummary` consolidation and the `DbalOrderLineFinder` fix have no dependency on each
   other or on the order-lines-editing item above; if the line-editing item lands too,
   `OrderSummary`'s line table just gains a second event feed on top of `OrderPlaced.lines`.
