# Next

Travail identifié, discuté et pour partie amorcé pendant la session `chore/checkout-session-aggregate`
(PR #210), mais volontairement laissé hors de son périmètre — capturé ici pour ne pas le perdre.
Complète `.claude/TODO.md` (déjà à jour sur les autres points trouvés cette session — nommage des
dossiers `Application/<Concept>/`, PSP/Carrier réalistes, `Currency`/`Money`) sans les dupliquer.

## Id VO sur les Domain Events — repo entier

**Constat** : sur TOUT le repo, l'id racine d'un agrégat est stocké en `string` brut sur ses propres
Domain Events (`PaymentRequested::$id`, `OrderConfirmed::$id`, `CartStarted::$id`...), même quand la
transition détient déjà le vrai VO (`Payment::request(PaymentId $id, ...)` fait `id: $id->toString()`).
Une VO métier pure (`Money`, `PostalAddress`, `PaymentReference`) est, elle, bien stockée en VO
directement. Incohérence confirmée avec le principe déjà établi (`recordThat()` porte un VO directement
dès que la transition le détient) — pas propre à ce refacto, généralisée à tout le repo.

**Fausse piste explorée puis écartée** : un "rôle structurel de routage/indexation" qui justifierait
que l'id reste `string`. Invalidé — l'hydratation par réflexion reconstruit un id VO exactement comme
n'importe quel autre VO, aucune différence mécanique.

**Vraie contrainte identifiée, vérifiée dans le code vendor** (`patchlevel/hydrator`,
`CryptographyMiddleware::resolveSubjectIds()`) : un champ `#[DataSubjectId]` exige une valeur
`string`, `int`, ou implémentant `\Stringable` — sinon `UnsupportedSubjectId`. Or `Shared\Domain\UuidTrait`
(base de tous les id VO du repo) expose `toString()` mais n'implémentait pas `\Stringable` (pas de
`__toString()`). Donc, pour un event tagué `#[DataSubjectId]` (ex. `CheckoutSessionOpened`,
`OrderConfirmed`), `string` était une contrainte technique réelle, pas juste une convention.

**Fix trouvé et vérifié safe (round-trip hydrate/extract entièrement relu côté vendor)** : ajouter
`__toString()` à `UuidTrait` — PHP 8+ reconnaît automatiquement `\Stringable` dès qu'un `__toString()`
existe, aucune classe consommatrice à modifier. Vérifié que `hydrate()` lit l'id depuis le tableau brut
JSON-décodé (toujours un scalaire, avant toute reconstruction VO) et qu'`extract()` accepte maintenant
un objet `Stringable` — donc le fix débloque même les events `#[DataSubjectId]`, pas seulement les
events sans PII.

**État actuel** : le fix (`UuidTrait::__toString()`) + la conversion `CheckoutSessionExpired`/
`CheckoutSessionStaled`/`CheckoutSessionConsumed` (les 3 seuls events sans PII neufs de ce refacto,
`id` passé en `CheckoutSessionId` au lieu de `->toString()`) est **stashé, non appliqué** —
`git stash list` → `stash@{0}: On chore/checkout-session-aggregate: wip: id VO on non-PII
CheckoutSession events, repo-wide follow-up pending`. Retiré du scope de la PR #210 pour ne pas la
faire déraper davantage (elle avait déjà un conflit de squash-merge à traiter en priorité). Reste à
finir même pour ces 3 events seuls : `DbalCheckoutSessionProjector` (bind `'id' => $event->id` sur 3
sites, DBAL veut un scalaire → `$event->id->toString()`), `CheckoutSessionStaledPublisher`
(`publisher->publish(..., $event->id, ...)` attend un `string` ; `CheckoutSessionStaledIntegrationEvent`
ne doit JAMAIS porter de VO Domain, `application.md` → `$event->id->toString()` aux deux endroits),
et les tests qui construisent ces 3 events directement (`CheckoutSessionTest::expired()`/`staled()`/
`consumed()`, aujourd'hui `$this->id->toString()` → `$this->id`).

**Demande explicite de l'utilisateur : traiter ce sujet "REPO WIDE"**, pas seulement les 3 events de
ce refacto — reprendre tout event Domain portant l'id propre de son agrégat (`PaymentRequested`,
`PaymentAuthorized`, `PaymentAbandoned`, `PaymentVoided`, `PaymentFailed`, `CartStarted`,
`CartLineAdded/Removed/QuantityChanged`, `CartPurchased`, `OrderConfirmed`, `OrderPrepared`,
`OrderCancelled`, `OrderFailed`, `OrderDispatched`, `OrderDelivered`, `OrderErased`,
`OrderErasureApproved`, tous les events `Shipment*`, `ApiKeyCredential*`, `PasswordCredential*`,
`Identity*`, `Product*`, `Erasure*`...) et convertir leur champ `id` de `string` au VO propre de
l'agrégat. Périmètre pas encore inventorié exhaustivement (interrompu par la découverte du conflit
de squash-merge sur #210) — à faire avant de commencer : lister tous les fichiers `Domain/Event/*.php`
du repo, vérifier pour chacun si l'agrégat détient déjà le VO à la construction (cas général) et si
l'event est taggé `#[DataSubjectId]` (déjà débloqué par le fix `UuidTrait`, mais vérifier chaque
cas). Pour chaque event touché : mettre à jour le type du champ, les sites `recordThat()`/
`#[Apply]` dans l'agrégat, tout Publisher/Projector/Policy lisant `$event->id` en tant que `string`
(ajouter `->toString()` au point de sortie vers Infrastructure/Application), et tout test
construisant l'event directement (`given()` baselines). Blast radius large — probablement plusieurs
PR, une par BC, pas un seul gros changement.

## Nettoyage "unused properties" (session paire, `chore/order-payment-unused-properties`) — traduit Buyer→Shopper et revérifié sur le code courant

Un autre chunk de la même branche que le fix Hydrator/le design 2-tables Cart (base pré-#207/#208,
d'où les noms `Buyer`/`EraseBuyerHandlerTest` dans le rapport original — traduit ci-dessous vers
`Shopper`, nom courant depuis #208, et chaque point revérifié sur le code actuel plutôt que recopié
tel quel) :

- **6 Integration Events morts** — publiés mais souscrits par personne (vérifié par grep exhaustif
  `#[Subscribe(X::class)]`) : `PaymentRequestedIntegrationEvent`, `ShipmentCancelledIntegrationEvent`,
  `ShipmentManifestedIntegrationEvent`, `IdentityRegisteredIntegrationEvent`,
  `OrderDeliveredIntegrationEvent`, `OrderDispatchedIntegrationEvent`. Proposé : supprimer (event +
  Publisher + test), YAGNI, l'historique git reste le chemin de récupération si un futur BC en a
  besoin — le Domain Event sous-jacent reste inchangé (toujours enregistré/projeté). Aucun de ces
  6 noms n'est affecté par le renommage Buyer→Shopper (Payment/Shipment/Identity/Order, pas
  Sales.Buyer). **Spot-check fait sur `chore/checkout-session-aggregate`** : confirmé pour
  `PaymentRequestedIntegrationEvent`, zéro `#[Subscribe(PaymentRequestedIntegrationEvent::class)]`
  dans `src/` actuel. Les 5 autres pas revérifiés ici.
- **5 propriétés d'agrégat écrites mais jamais relues** (violent le test de justification `domain.md`
  — self-guard/invariant frère/port sortant/construction d'Integration Event) : `Payment::$checkoutUrl`,
  `Shipment::$buyerId`, `Order::$billingAddress`, `Order::$totalAmountInCents`,
  **`Shopper::$email`** (`src/Shopping/Checkout/Domain/Shopper.php:40`, confirmé écrit dans
  `applyRegistered()` ligne 132 — pas revérifié s'il est vraiment jamais relu ailleurs dans
  `Shopper.php`, seul le nom de la propriété est confirmé exister). Chaque fait reste légitime sur
  son propre Domain Event + read model (toujours consommé là) — seule la copie redondante sur
  l'agrégat est proposée à la suppression. **`Shipment::$buyerId` est déjà tracké indépendamment
  dans ce même `TODO.md`** (section Fulfilment.Shipping, trouvé le 2026-09-09, diagnostic
  identique) — ne pas dupliquer le travail, cet item confirme juste le même constat depuis une
  autre branche.
- **2 tests à corriger pour lire l'état métier via Builder/Finder plutôt que directement sur
  l'agrégat — vérifiés réels et toujours présents sur le code courant** :
  - `tests/Shopping/Checkout/Application/Command/EraseShopper/EraseShopperHandlerTest.php:35,41` —
    `$shopper->email->value` lu directement sur l'agrégat construit (`$shopper =
    ShopperBuilder::new()->erasureRequested()->create()`), alors que `$shopper->id` juste à côté est
    l'exception explicitement documentée (id racine, lecture directe sur l'instance construite
    tolérée) — `email` n'en fait pas partie, doit passer par le Builder gardé en variable
    (`$builder['email']->value`). Confirmé : le test actuel fait toujours ainsi, pas corrigé.
  - `tests/Sales/Ordering/Infrastructure/Projection/Finder/DbalOrderFinderTest.php:47` et
    `tests/Sales/Ordering/Infrastructure/Projection/Projector/DbalOrderProjectorTest.php:47` —
    `$order->totalAmountInCents` lu directement sur l'agrégat (`Order` non renommé, confirmé tel
    quel) ; à dériver depuis les lignes de l'Order à la place, aligné sur le calcul déjà fait par
    `Order::confirm()`.

**Pas encore fait** : la suppression des 5 propriétés/6 events elle-même, et la correction des 2
tests — seule la vérification "est-ce que ça correspond encore au code actuel" a été faite ici.
Reste à confirmer les 4 items non spot-checkés (5 events restants, `Payment::$checkoutUrl`,
`Order::$billingAddress`) avant d'agir, contacter la session paire (`symfony-ddd-cqrs-es-9d` dans
`ListAgents`) pour son code final si utile.

## Port du fix Hydrator JSON (session paire, jamais appliqué ici)

`Shared/Infrastructure/Patchlevel/Hydrator/Normalizer/JsonObjectNormalizer.php` → `JsonNormalizer` :
au lieu de réimplémenter l'hydratation, décoder la string JSON puis déléguer à l'`ObjectNormalizer`/
`ArrayNormalizer` du vendor (qui gèrent déjà `list<X>` correctement). `TypeBasedNormalizerEnricher`
(`Shared/Infrastructure/Patchlevel/Hydrator/Metadata/`) à réécrire pour envelopper le normalizer déjà
deviné par le vendor plutôt que de le remplacer. Un mock PHPUnit classique a été testé et rejeté côté
session paire (ne distingue pas un appel 1-arg d'un 2-arg quand le second a un défaut, vérifié
empiriquement) — un spy écrit à la main est la seule solution qui tue réellement les mutants,
construction factorisée en `setUp()`.

**Pourquoi pas fait ici** : investigation menée sur cette branche (vendor `AttributeMetadataFactory`,
`BuiltInGuesser`, `ObjectNormalizer`, `ArrayNormalizer`) montre que le mécanisme actuel fonctionne
correctement pour tous les cas réels de ce repo aujourd'hui (1093 tests verts, y compris hydratation
de `PostalAddress` sur `CheckoutSessionOpened`) — aucun bug concret reproduit que ce renommage
corrigerait. Réécrire ce mécanisme partagé, fondamental pour toute hydratation du repo, sans
reproduction ni référence fiable au code final de la session paire (resté dans son propre stash), a
été jugé trop risqué pour être fait à l'aveugle. À reprendre si/quand un vrai cas cassé apparaît, ou
si la session paire partage son code final.

## Design 2-tables `DbalCartFinder`/`DbalCartLineFinder` (session paire) — pas retenu ici, à revoir si besoin futur

La session paire (`chore/order-payment-unused-properties`, base antérieure à #207/#208, ciblant
l'ancien `Sales.Ordering.Cart` désormais obsolète) avait construit : deux Finders séparés (jamais l'un
n'appelle l'autre), `DbalCartFinder` (scalaire id/shopperId/status) + `DbalCartLineFinder` (nouvelle
table `*_cart_line`, une ligne par ligne de panier), `DbalCartLineProjector` avec upsert atomique sur
`CartLineAdded` (fusion de quantité, `line_id` dérivé `uuid5` = clé primaire globale de la table, tous
paniers confondus), colonne `added_at` + tri `(added_at, line_id)` (id dérivé sans composante
temporelle, jamais un tie-breaker seul).

**Pourquoi pas retenu dans #210** : `CheckoutSessionOpener` n'a qu'un seul lecteur, une seule
transaction, aucune concurrence cross-panier à gérer — le Finder simple déjà construit (une seule
table `shopping_checkout_cart`, `lines_json` en colonne, même logique de fusion que
`Cart::applyLineAdded()`) est vérifié correct et suffisant. Le design 2-tables répondait à un besoin
de concurrence à l'échelle globale que ce refacto n'a pas.

**À revoir si un jour un vrai besoin de ce genre apparaît** (accès concurrent à grande échelle sur les
lignes de panier, pagination indépendante des lignes, etc.) — pas urgent, noté pour référence future
uniquement.

## Petits constats non actionnés, pas encore dans `TODO.md`

- `Shared\Application\ErasureStatus` n'a AUCUN prédicat `is<Case>()` (contrairement à tous les autres
  enums closed-vocabulary du repo, ex. `CartState::isActive()`) — `CheckoutSessionOpener.php` compare
  `ErasureStatus::REQUESTED === $shopper->erasureStatus` en brut, seul site de comparaison sur cet
  enum dans tout `src/`. Ajouter `isRequested()` (et les autres cases si besoin) directement sur
  l'enum.
- `Shopping\Checkout\Domain\Cart\ValueObject\Product` (la copie figée d'un produit dans une `Line`)
  et `Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult` (le DTO Finder vivant,
  déjà qualifié "Listed" précisément pour éviter la confusion avec un `Product` catalogue réel)
  désignent conceptuellement la même chose (un instantané figé d'un produit catalogue) sous deux noms
  incohérents — `Product` n'a pas reçu la même précaution que `ListedProduct`. Vocabulaire à
  harmoniser (renommer `Cart\ValueObject\Product` en quelque chose comme `LineProduct`/
  `PricedProduct`, ou confirmer que c'est acceptable tel quel) — pas tranché.

## Vérification jamais aboutie sur cette branche

`castor qa:mutation` n'a jamais pu terminer sur `chore/checkout-session-aggregate` — deux tentatives
distinctes (une par l'implémenteur, une par moi) ont échoué en moins d'une seconde avec un signal
SIGTERM (exit 143), avant même de lancer les tests instrumentés PCOV. Pas un problème de code (la
suite passe parfaitement sans coverage, `qa:stan`/`qa:deptrac`/`qa:test` tous verts) — cause
non identifiée (pas de limite mémoire container, pas d'OOM dans `dmesg`). À relancer/investiguer
avant de considérer la PR #210 totalement vérifiée.
