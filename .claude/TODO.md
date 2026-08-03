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
    job (`crontab`/k8s `CronJob` invoking `fulfilment:shipment:dispatch-pending`), not something
    an admin manually confirms through a dashboard. See `SCENARIO.md`'s DM table/rejected
    alternatives. The single-shipment `POST /shipments/{id}/dispatch` API operation stays gated
    as-is; no bulk API equivalent added.
  - ✅ **Demo seeders now create/link an Identity for seeded customers** — `demo:sales:customers`
    additionally dispatches `RegisterIdentity` + `SetPasswordCredential` + `LinkCustomerIdentity`
    per seeded customer, so `make seed` produces customers that can actually log in (fixed demo
    password, printed once at the end of the command).
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

## Dependency order

1. `Catalog.Product` and `#24` have no dependency on each other — either can start first.
2. `#25` (OrderPayment) needs `Catalog.Product` first (a real price to charge).
3. The suspend-cascade and Identity-erasure-cascade items need `#24` first (auth wiring).
4. `#18`, README, and emptying the registers can happen anytime, but final audits wait on
   everything.
