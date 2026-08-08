# TODO

- `DerivedUuidTrait` (no `generate()`) for deterministically-derived aggregate ids (`PasswordCredentialId`, `OrderPaymentId`, `ShipmentId`), replacing `UuidTrait` on those 3. Update the 14 test call sites currently using `X::generate()` on them to derive from a generated foreign id instead (`X::forY(YId::generate()->toString())`). Update `domain.md` convention accordingly.

- Rename `Resolver`/`Reducer` pattern to `StreamResolver`/`StreamReducer` (shared "Stream" family = `Store::load()`+fold; `Resolver` vs `Reducer` keeps distinguishing why: use-case fold from a Command Handler vs projection-composition fan-out). Touches `BuyerResolver`/`ProductResolver` + their interfaces + `infra.md` wording + call sites (`PlaceOrderHandler`, `CancelOrderHandler`, `CreateShipmentOnOrderPaymentCaptured`, tests), plus this session's new `IdentityStatusReducer`. Frees bare `Resolver`/`Reducer` for unrelated reuse elsewhere (e.g. a DM-side lookup class) without ambiguity.
