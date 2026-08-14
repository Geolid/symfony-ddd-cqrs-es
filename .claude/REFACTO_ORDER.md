# Refonte Order / Payment / Shipment — cycle de vie & orchestration

Document de conception, pas encore implémenté. Consolide plusieurs sujets liés : bug réel trouvé dans `OrderSummaryStatusTransformer::compute()`, architecture cross-BC des Resolvers, confiance client/serveur sur `PlaceOrder`, et cycle de vie complet d'`Order` comme agrégat racine de la vente, incluant un paiement en 2 phases et un flux transporteur asynchrone réalistes.

Non gitignored (contrairement à `.claude/TODO.md`) — fichier de conception à part, pas le backlog courant.

## 0. Constats de départ (vérifiés dans le code)

- `OrderState` : `PLACED`/`CANCELLED` seulement. `OrderPaymentState` : `REQUESTED`/`CAPTURED` seulement. `ShipmentState` : `PENDING`/`DISPATCHED`/`DELIVERED`/`CANCELLED`.
- `OrderSummaryStatusTransformer::compute()` a un bug réel : sa branche `shipmentStatus` finit sur un `default` qui fusionne `DELIVERED` et `CANCELLED` — un Shipment annulé (Order/Payment restant placés/capturés) remonterait `DELIVERED`. Actuellement **dormant** : `ShipmentIntegrationEventTranslator` ne traduit que `Dispatched`/`Delivered`/`TrackingReferenceAssigned`, aucun `ShipmentCancelledIntegrationEvent` n'existe, donc le signal n'atteint jamais cette colonne. Scénario réel confirmé : `RequestPickupOnShipmentDispatched` annule un `Shipment` (client erased entre dispatch et pickup) sans jamais toucher `Order`.
- `StreamBuyerResolver`/`StreamProductResolver` lisent en direct le stream Integration Event d'un autre BC à chaque appel, zéro matérialisation — viole `application.md` ("jamais reach direct dans l'infra d'un autre BC pour un point-in-time check").
- `PlaceOrder` ne porte ni prix ni adresse — les deux sont re-résolus/re-dérivés côté serveur au lieu d'être ce que le client a réellement vu et confirmé.
- Aujourd'hui : `CreateShipmentOnOrderPaymentCaptured` réagit à `OrderPaymentCapturedIntegrationEvent` pour créer le `Shipment`. Le paiement est capturé en un seul webhook (`payment-captured`), immédiatement au checkout.
- Aujourd'hui : `DispatchPendingShipmentsCommand` (cron quotidien, `0 0 * * *`) fait tout en un coup — batche les `PENDING`, appelle `requestPickup()` (fire-and-forget, aucune confirmation), et marque immédiatement `DISPATCHED`. Aucune confirmation physique du transporteur n'existe.
- `DbalShipmentProjector::onOrderCancelled` réagit à `OrderCancelledIntegrationEvent` mais écrit seulement une colonne séparée `order_cancelled_at` — ne touche jamais `status`. Aucun Processor ne réagit à cet event pour annuler l'agrégat `Shipment` lui-même : la compensation Fulfilment↔Order sur annulation **n'existe pas** aujourd'hui, contrairement à ce qu'une lecture rapide suggère.
- Bug latent trouvé dans `Shipment::cancel()` : son guard ne rejette que `DELIVERED`, pas `DISPATCHED` — un colis déjà remis au transporteur pourrait être marqué `CANCELLED` sans qu'aucune action physique ne l'accompagne. Actuellement dormant (rien n'appelle `cancel()` via ce chemin), mais devient actif dès qu'un Processor de compensation est construit (§9).

## 1. La refonte centrale : paiement 2-phases + dispatch transporteur asynchrone

Défaut du modèle actuel : le cron fait office de webhook lui-même — il décide seul, sans confirmation externe, que le colis est `DISPATCHED`. Incohérent avec le paiement, qui lui attend correctement un vrai webhook avant de passer `CAPTURED`. Le prélèvement immédiat au checkout est aussi un mauvais choix métier : geler les fonds (autoriser) coûte rien à annuler (void), un paiement déjà capturé coûte des frais bancaires et 3-5 jours à rembourser.

**Nouvelle causalité (inversée par rapport à aujourd'hui)** :

```
PlaceOrder
  └─▶ OrderPayment REQUESTED
        └─▶ webhook (checkout) ──▶ OrderPayment AUTHORIZED
              └─▶ same-BC Processor ──▶ Order CONFIRMED
              └─▶ cross-BC Processor ──▶ Shipment créé, PENDING
                                              └─▶ cron quotidien ("fermeture de quai" — manifeste, appelle requestPickup())
                                                    └─▶ Shipment READY_FOR_DISPATCH
                                                          └─▶ webhook carrier (scan physique du livreur)
                                                                └─▶ Shipment DISPATCHED, ShipmentDispatchedIntegrationEvent
                                                                      ├─▶ Order : Processor pose un fait local "dispatched" (guard cancel, §9)
                                                                      └─▶ cross-BC Processor ──▶ OrderPayment CAPTURED
                                                                            └─▶ webhook (livraison) ──▶ Shipment DELIVERED
                                                                                  └─▶ cross-BC Processor ──▶ Order COMPLETED
```

Le paiement n'est plus prélevé au checkout — il est prélevé au moment où le colis part réellement, confirmé par le transporteur.

## 2. États par agrégat — avant → après

### `OrderPayment`
- Avant : `REQUESTED`, `CAPTURED`.
- Après : `REQUESTED`, `AUTHORIZED`, `CAPTURED`, `FAILED`, `REFUNDED`.
- `AUTHORIZED` : remplace la capture immédiate. Le webhook existant `payment-captured`/`PaymentCapturedParser`/`PaymentCapturedConsumer` est repensé sémantiquement en `payment-authorized`/`PaymentAuthorizedParser`/`PaymentAuthorizedConsumer` → nouveau Command `AuthorizeOrderPayment` (remplace l'actuel `CaptureOrderPayment` à ce stade du flux). **À investiguer avant implémentation** : `GlobexPaymentGateway`/le sandbox (`sandbox/globex/checkout/index.php`, `sandbox/globex/api/charges.php`) fusionnent aujourd'hui authorize+capture en un seul clic "Authorize & Pay" — séparer les deux est faisable (sandbox à nous) mais demande de réécrire la page checkout en 2 étapes distinctes, pas juste un renommage.
- `CAPTURED` : déclenché par un nouveau Processor cross-BC dans Sales.Order réagissant à `ShipmentDispatchedIntegrationEvent` (existe déjà) → dispatch `CaptureOrderPayment`. Plus jamais déclenché par un webhook paiement direct.
- `FAILED` : nouveau webhook `payment-failed` (triplet `PaymentFailedParser`/`PaymentFailedPayload`/`PaymentFailedConsumer`, mirroir du pattern existant) → nouveau Command `FailOrderPayment`.
- `REFUNDED` : voir §9 (annulation) et §3 (retour). Émis par `OrderPayment` elle-même, jamais décidé de l'extérieur.

### `Shipment`
- Avant : `PENDING`, `DISPATCHED`, `DELIVERED`, `CANCELLED`.
- Après : `PENDING`, `READY_FOR_DISPATCH`, `DISPATCHED`, `DELIVERED`, `CANCELLED`, `RETURNED`.
- Création (`PENDING`) : déclenchée par `OrderPaymentAuthorizedIntegrationEvent` (nouveau), pas `Captured` — `CreateShipmentOnOrderPaymentCaptured` devient `CreateShipmentOnOrderPaymentAuthorized`.
- `READY_FOR_DISPATCH` : posé par le cron quotidien ("fermeture de quai" / manifeste) — il rassemble les `PENDING` du jour, appelle `requestPickup()`, mais ne marque plus `DISPATCHED` lui-même.
- `DISPATCHED` : posé par un **nouveau webhook carrier** (scan physique, triplet dédié mirroir de `CarrierDeliveryParser`/`Payload`/`Consumer`) — c'est la vraie confirmation physique, plus une supposition du cron. Émet `ShipmentDispatchedIntegrationEvent` (déjà existant en tant que type, déclenché différemment).
- `RETURNED` : flux entier neuf, retour post-livraison (buyer ou carrier-initié), reachable seulement depuis `DELIVERED`. Nouveau Domain Event `ShipmentReturned` + nouvel Integration Event `ShipmentReturnedIntegrationEvent`. Nouveau webhook dédié (nom à fixer).

### `Order`
- Avant : `PLACED`, `CANCELLED`.
- Après : `PLACED`, `CANCELLED`, `CONFIRMED`, `DISPATCHED`, `COMPLETED`, `REFUNDING`, `RETURNED`.
- Chemin complet : `PLACED`→`CONFIRMED`→`DISPATCHED`→`COMPLETED`→`REFUNDING`→`RETURNED` (linéaire, `RETURNED` seulement accessible depuis `COMPLETED`). `CANCELLED` reste atteignable depuis `PLACED`/`CONFIRMED` uniquement — plus loin que `CONFIRMED` (une fois `DISPATCHED` atteint), le guard rejette (§9). Un seul champ `status` porte cette information, pas un fait mirroré séparément (même principe que le fix déjà décidé sur `Identity`/`Shipment`, TODO.md : jamais deux champs orthogonaux pour une seule question).
- `CONFIRMED` : Processor **same-BC**, réagit à `OrderPaymentAuthorized` (Domain Event, pas d'Integration Event nécessaire — `OrderPayment` vit dans Sales.Order). Nouveau Domain Event `OrderConfirmed`.
- `DISPATCHED` : Processor **cross-BC**, réagit à `ShipmentDispatchedIntegrationEvent` (existe déjà) — c'est CE champ, pas un bool séparé, qui sert de guard à `Order::cancel()` (§9). Nouveau Domain Event `OrderDispatched`.
- `COMPLETED` : Processor **cross-BC**, réagit à `ShipmentDeliveredIntegrationEvent` (existe déjà). Nouveau Domain Event `OrderCompleted`.
- `REFUNDING` : Processor cross-BC, réagit à `ShipmentReturnedIntegrationEvent`. Nouveau Domain Event `OrderReturnRequested` — nommage décidé : "l'utilisateur a cliqué retourner, le processus démarre" (exactitude sémantique, fait passé précis). Déclenche `RefundOrderPayment` (same-BC).
- `RETURNED` : Processor same-BC, réagit à `OrderPaymentRefunded` (Domain Event `OrderPayment`). Nouveau Domain Event `OrderReturned` — nommage décidé : "le processus de retour est totalement achevé (colis reçu + argent rendu)", distinct de `OrderReturnRequested` qui ne fait que démarrer le processus.
- Pas de `OrderConfirmedIntegrationEvent`/`OrderCompletedIntegrationEvent` : Fulfilment a déjà son propre trigger direct (`OrderPaymentAuthorizedIntegrationEvent`), `CONFIRMED`/`COMPLETED` restent des reflets internes à `Order`.

## 3. Orchestration — `Order` seul point d'entrée entre Payment et Shipment

Principe non négociable : un Domain Event Logistique (`ShipmentReturned`) ne pilote jamais directement un flux Financier (`OrderPayment`), et inversement. Tout passe par `Order` :

```
Shipment ──ShipmentReturnedIntegrationEvent──▶ Order (REFUNDING)
                                                  │
                                                  ▼ same-BC, dispatch RefundOrderPayment
                                              OrderPayment (REFUNDED)
                                                  │
                                                  ▼ same-BC, OrderPaymentRefunded
                                              Order (RETURNED)
```

`Order` ne lit jamais synchronement l'état d'un autre agrégat/BC pour un guard. Chaque invariante cross-aggregate/cross-BC est un fait **local, mirroré en avance** via un Processor réagissant à l'event correspondant — jamais un read au moment de la décision.

## 4. Nouveaux Integration Events

- `OrderPaymentAuthorizedIntegrationEvent` — nouveau, remplace `OrderPaymentCapturedIntegrationEvent` comme trigger de création du `Shipment`.
- `ShipmentCancelledIntegrationEvent` — manquant aujourd'hui, corrige le bug `compute()` dormant (§0). Indépendant du reste, à faire de toute façon.
- `ShipmentReturnedIntegrationEvent` — nouveau, pour `RETURNED`/`REFUNDING`.
- `ShipmentCancellationRejectedIntegrationEvent` — nouveau, émis quand `CancelOrder` arrive après que le `Shipment` soit déjà `DISPATCHED` (§9), consommé par Sales/Notification pour prévenir le client.
- `ShipmentDispatchedIntegrationEvent` — existe déjà, déclenché différemment (par le webhook carrier, plus par le cron).

## 5. Nouveaux webhooks

Un triplet dédié par outcome (`Parser`/`Payload`/`Consumer`, mirroir exact de l'existant) — jamais un champ `status` générique ajouté à un payload existant avec branchement dans le Consumer (violerait `dm.md`, business logic en DM).

- `payment-authorized` (reprend la place sémantique de l'actuel `payment-captured`) → `AuthorizeOrderPayment`.
- `payment-failed` → `FailOrderPayment`.
- `carrier-pickup-confirmed` (nommage décidé, cohérent avec l'existant `carrier-delivery`) → transition `Shipment` `READY_FOR_DISPATCH`→`DISPATCHED`.
- Retour shipment (nom à définir) → déclenche `ShipmentReturned`.

## 6. Combinateur de statut (`OrderSummaryStatusTransformer`)

- Avant : `Infrastructure/Projection/Transformer/`, mal nommé (combine N valeurs en 1, pas une transformation 1-vers-1), `match` imbriqué avec `default` catch-all — source du bug §0.
- Après : reconstruit au moment du fold `Sales/OrderSummary`→`Sales/Order` (déjà décidé, `.claude/TODO.md`). **Corrigé deux fois après review — reste en Infrastructure (pas déplacé en Application), et n'entre pas dans la famille `Reducer` non plus** : avis tiers demandé et retenu — pas de nouveau rôle infrastructurel inventé depuis une seule instance ; `Reducer`/`Resolver` restent réservés à ce qui lit réellement l'event store. Renommé `OrderSummaryStatusEvaluator` ("Evaluator" traduit l'arbre de décision — le `match` imbriqué évalue plusieurs faits déjà connus pour une conclusion), reste colocalisé avec le Projector qui l'utilise, sans dossier/convention dédiée dans `infrastructure.md` tant qu'une 2e instance réelle du même besoin n'apparaît pas. `match` **explicite par cas réel**, sur l'ensemble élargi des états (§2) — plus de branche `default` : exhaustive pattern matching, la meilleure défense contre un état async non géré silencieusement mal classé.

## 7. `PlaceOrder` — prix et adresse

- Prix : **actionnable indépendamment du reste.** `PlaceOrder` porte le prix (et le label — même principe "ce que le client a vu", le nom du produit affiché peut lui aussi changer entre rendu de page et soumission) par ligne. `PlaceOrderHandler` valide contre le prix/label réels courants — écart = rejet/reconfirmation, jamais substitution silencieuse ni confiance aveugle envers le client.
- Adresse : **bloqué sur le VO `Address`** (`Address`→`Email` misnomer, item séparé `.claude/TODO.md`). Tant que Sales.Customer ne capture pas une vraie adresse postale et qu'aucune étape de checkout ne l'affiche, `buyerAddress` reste tel quel (email, invisible/auto). Une fois le VO en place : la commande porte l'adresse affichée/confirmée, capturée une fois à `place()`, jamais réévaluée ensuite (Order = contrat figé — changer l'adresse d'une commande déjà placée est un flux support/annulation, jamais un mécanisme de résolution).

## 8. Resolvers cross-BC → Finder local

Une fois prix/label/adresse portés par la commande (§7), `BuyerResolverInterface`/`Buyer` et `ProductResolverInterface`/`Product` (Application VOs, aujourd'hui de purs porteurs de valeur : `Buyer{id, address}`, `Product{id, label, unitAmountInCents}`) **disparaissent entièrement** — pas seulement leur implémentation `Stream*`. Plus aucune valeur à résoudre, seulement un gate existence/validité point-in-time (buyer enregistré et pas erased ; produit existe, encore listé, prix concordant). Remplacés par un Finder local dans Sales.Order (méthodes de garde pures, ex. `ensureRegistered()`/`ensureAvailable()`, zéro valeur de retour), alimenté par un Projector sur les Integration Events déjà existantes (`CustomerRegisteredIntegrationEvent`/`CustomerErasedIntegrationEvent`, `ProductListedIntegrationEvent`/`ProductRepricedIntegrationEvent`/`ProductDelistedIntegrationEvent`) — plus de `Store::load()` direct sur le stream étranger. Le rôle `Resolver` (`EventStore/Resolver/`, occupé aujourd'hui uniquement par les deux `Stream<X>Resolver` ci-dessus) finit sans occupant restant ; revoir `infrastructure.md` à ce moment-là (la convention elle-même peut être retirée, pas juste laissée vide) — ce besoin (lire un stream étranger en direct pour une valeur point-in-time) était lui-même un smell de flux, pas juste un problème de nommage : il devrait toujours passer par un Finder local alimenté par des Integration Events, jamais par une lecture directe.

Sans rapport avec les Resolvers — `Shipment::ensureCustomerNotErased()` est un guard construit sur une comparaison de valeur (`'erased-address' === $this->customerAddress`), pas sur un fait de domaine explicite. **Déjà un item fusionné dans `.claude/TODO.md` (Fulfilment.Shipment), pas à réinventer ici** : design cible déjà écrit — un vrai Processor par Integration Event (`CustomerErasedIntegrationEvent`, `OrderCancelledIntegrationEvent`, ce dernier comblant exactement le même trou identifié en §9) dispatche `CancelShipment`, fait passer `Shipment` par sa transition `cancel()` déjà existante vers `ShipmentState::CANCELLED` (déjà dans l'enum — **dans `status`, pas un bool séparé**, même raison que le fix déjà décidé sur `Identity` : deux champs orthogonaux pour une seule question est le smell à éviter). Le guard devient `ensureNotCancelled()`, vérifie `$this->status->isCancelled()`. Migration `PersonalData`→`SensitiveData` sur `ShipmentCreated` dans le même geste (déjà décidée, TODO.md), pas avant.

## 9. Guard `CancelOrder` — le fait logistique reste dans Sales, jamais dérivé du paiement

**Faille identifiée et corrigée en review** : `OrderPayment::CAPTURED` est maintenant déclenchée de façon asynchrone par `ShipmentDispatchedIntegrationEvent` (§1) — un Processor différent de celui qui pose le fait "dispatched" sur `Order`. Ce sont deux réactions indépendantes au même event, sans ordre garanti entre elles. `CAPTURED` implique que `DISPATCHED` a eu lieu (vrai), mais `NOT CAPTURED` n'implique PAS `NOT DISPATCHED` sous latence réseau/file d'attente (faux) — utiliser l'état du paiement comme proxy du fait logistique est un anti-pattern de système distribué (indicateur retardé). Le colis peut être physiquement parti alors que la capture n'a pas encore été traitée : annuler à ce moment relâcherait le paiement (Void) alors que le client recevra quand même le colis, gratuitement.

**Guard réel** : `Order` garde son propre Processor réagissant à `ShipmentDispatchedIntegrationEvent`, faisant transiter `Order` vers son propre état `DISPATCHED` (§2 — un case de plus dans `OrderState`, pas un champ séparé) — indépendant de tout ce qui se passe côté `OrderPayment`. `Order::cancel()` self-guard sur son propre `status` (rejette au-delà de `CONFIRMED`), jamais sur l'état du paiement.

**Race résiduelle côté logistique** (corrigé après vérification du code — contrairement à ce que ce document affirmait avant) : la transition `DISPATCHED` d'`Order` pourrait elle aussi arriver après coup, exactement le même risque que côté paiement. Aujourd'hui, aucun Processor ne réagit à `OrderCancelledIntegrationEvent` pour agir sur l'agrégat `Shipment` — seule la projection annote `order_cancelled_at` (colonne séparée, ne touche jamais `status`, donc pas de risque d'écrasement lecture).

**Décidé — chemin standard, choisi plutôt qu'une interception logistique (RTS, hors scope)** :
1. Un Processor Fulfilment.Shipment (`CancelShipmentOnOrderCancelled`, nom à fixer) réagit à `OrderCancelledIntegrationEvent`, dispatche `CancelShipment`.
2. `Shipment::cancel()` (bug corrigé dans le même geste — rejette aujourd'hui seulement `DELIVERED`, doit aussi rejeter `DISPATCHED`) lève une exception de domaine quand le colis est déjà `DISPATCHED`.
3. Le Processor rattrape cette exception (même shape que le pattern déjà documenté `application.md` : "catches its own aggregate's rejection... instead of letting the exception bubble") et émet un nouvel Integration Event `ShipmentCancellationRejectedIntegrationEvent` (nom à fixer) — plutôt qu'un rejet silencieux.
4. Sales/Notification écoute ce rejet et prévient le client par email : la commande était déjà dans le camion, refuser le colis à la livraison déclenchera un retour et son remboursement (rejoint le flux `RETURNED`/§3 déjà décrit).

Option non retenue, notée pour mémoire : si le transporteur expose une vraie API "Return to Sender", `Shipment` pourrait viser un état `INTERCEPT_REQUESTED` pour tenter d'annuler la livraison physiquement (le livreur ramène le colis au dépôt) — hors scope de ce showcase, `CarrierGatewayInterface` n'a aujourd'hui rien de tel.

**Compensation du paiement, encapsulée dans `OrderPayment` elle-même** : `CancelOrderHandler` ne lit jamais `OrderPayment.status()` pour choisir entre voider et rembourser — il dispatche un unique `CancelOrderPayment` (ou nom équivalent). `OrderPayment::cancel()` décide en interne, à partir de son propre `$status` déjà connu, d'émettre l'event correspondant. Tell-don't-ask complet : aucun branchement externe sur l'état du paiement. `OrderPayment` ne fait jamais l'appel Gateway elle-même (aucun agrégat de ce repo n'en fait — `RequestOrderPaymentHandler` confirme que l'appel Gateway se fait toujours en amont, en Application) : elle mute son état et émet l'event, un Processor same-BC réagissant à cet event appelle `PaymentGatewayInterface::void()`/`refund()` (nouvelles méthodes à ajouter).

`OrderPayment::cancel()` couvre 4 cas selon son propre `$status` :
- `REQUESTED` → `CANCELLED` directement, aucun appel Gateway (rien n'est encore autorisé chez Globex). Mais si le webhook `payment-authorized` arrive après coup (race), `OrderPayment::authorize()` doit rejeter la transition sur un paiement déjà `CANCELLED` (no-op ou exception domaine — same shape que le guard `transition method's self-guard` déjà documenté `domain.md`) plutôt que de faire silencieusement autoriser un paiement annulé.
- `AUTHORIZED` → émet l'intent de void (`PaymentVoided` ou `PaymentVoidRequested` selon si le void est traité comme instantané ou lui-même confirmé plus tard — void est gratuit/instantané côté métier, donc probablement direct sans webhook de confirmation, contrairement au refund).
- `CAPTURED` → appelle la même `OrderPayment::refund()` que le flux de retour post-livraison (§3) — **décidé : un seul point d'entrée**, l'agrégat se moque de savoir pourquoi on la rembourse (annulation tardive vs retour), sa seule responsabilité est de garantir que les fonds repartent. Deux Processors différents (un pour la race d'annulation, un pour le flux de retour) dispatchent le même `RefundOrderPayment`, qui appelle cette unique méthode. État transitoire avant confirmation webhook (un vrai remboursement prend des jours), mirroir du shape déjà `REQUESTED`→webhook→`AUTHORIZED`.
- `FAILED` → idempotent no-op (même shape que `Order::cancel()` déjà idempotent si déjà `CANCELLED`) — annuler un paiement déjà échoué n'a aucun sens métier à re-signaler, ne doit jamais faire planter l'annulation de `Order`.

`DISPATCHED`/`COMPLETED`/`REFUNDING`/`RETURNED` ne nécessitent pas de guard cancel supplémentaire au-delà du `status` lui-même : `Order::cancel()` rejette dès que `status` dépasse `CONFIRMED`, un seul champ à vérifier.

## 10. Décidé (récapitulatif des points tranchés en review)

- Nommage : `OrderReturnRequested` (démarrage du retour) / `OrderReturned` (retour totalement achevé, colis reçu + argent rendu) — voir §2. Webhooks carrier : `carrier-pickup-confirmed`/`carrier-delivery` (existant) — voir §5.
- `RETURNED` reste strictement terminal (YAGNI) — aucun besoin métier actuel ne justifie une transition sortante. Si un jour une "réouverture de litige post-retour" est demandée, ce sera un nouvel état ou un nouveau BC dédié, pas une extension de celui-ci.
- `OrderPayment::REFUNDED` : un seul point d'entrée canonique, `OrderPayment::refund()` — voir §9.
- **Isolation domaine/infra** : ce document (Commands, Domain/Integration Events, agrégats, interfaces `PaymentGatewayInterface::void()`/`refund()`) constitue la PR principale. Le câblage sandbox (séparation authorize/capture dans `sandbox/globex/`, nouvelle boucle webhook carrier dans `sandbox/acme/`) est un travail d'infrastructure séparé, dans une ou des PR(s)/commits distincts — le domaine guide l'infra, pas l'inverse.
- **Annulation après dispatch (§9)** : chemin standard retenu — `Shipment::cancel()` (bug corrigé : rejette aussi `DISPATCHED`, pas seulement `DELIVERED`) lève une exception domaine, le Processor `CancelShipmentOnOrderCancelled` la rattrape et émet `ShipmentCancellationRejectedIntegrationEvent`, Sales/Notification prévient le client par email (refuser le colis à la livraison déclenche le flux `RETURNED`). Interception logistique physique (RTS transporteur) notée pour mémoire, non retenue — hors scope.

## 11. RGPD — interaction avec le nouveau cycle de vie `Order`

Sujet distinct du reste de ce document (touche Iam.Identity/Sales.Customer, pas seulement Sales.Order), mais s'appuie directement sur les nouveaux états `Order` — traité ici plutôt que dispersé.

- **Trou réel trouvé, indépendant du reste** : aucun Projector Sales.Order/Sales.OrderSummary ne réagit à `CustomerErasedIntegrationEvent` — vérifié, zéro résultat. Le crypto-shred (déjà en place, `domain.md`) protège l'event store, mais une projection qui matérialise du personnel en clair (adresse/email sur l'invoice, `OrderSummary`) n'est pas couverte par le drop de clé — elle doit rediger elle-même (`domain.md`, déjà documenté comme règle, jamais appliqué ici). À corriger : un Projector réagissant à `CustomerErasedIntegrationEvent`, `UPDATE` ciblé sur les colonnes `buyer_address`/`customer_address` des projections concernées.
- **Blocage `EraseCustomer` selon l'état `Order`** (précise l'item déjà tracké `.claude/TODO.md`, vague jusqu'ici) : "en cours" (bloque, exception domaine) = `PLACED`, `CONFIRMED`, `REFUNDING`. "Conclu" (erasure autorisée) = `CANCELLED`, `RETURNED`. `COMPLETED` : **ouvert** — bloque tant qu'une fenêtre de retour existe, ou traité comme conclu si aucune fenêtre n'est modélisée. Pas décidé.
- **Raffinement retenu, pas un blocage binaire** : si `OrderPayment` est encore `REQUESTED` (jamais `AUTHORIZED`) au moment de l'erasure, rien n'est réellement engagé — `EraseCustomer` annule l'`Order` en side-effect (même mécanisme que §9) plutôt que de bloquer l'erasure entièrement. Le blocage dur ne s'applique qu'à partir de `CONFIRMED`.
- **Mécanisme partiel déjà construit (2026-08-13), pas la fermeture finale du trou** : `CancelOrdersOnCustomerErased`/`CancelOrdersForCustomer` (Sales.Order) annulent déjà toute commande annulable d'un client effacé, mais **skip silencieusement** celles à `OrderPayment` `CAPTURED` — `EraseCustomerHandler` n'a toujours aucun guard, donc l'erasure réussit et la commande/le shipment payés continuent leur cours normalement. Ce comportement est l'intérimaire acceptable tant que les états `Order` (`CONFIRMED`/`COMPLETED`) et le guard bloquant ci-dessus n'existent pas — **à retrancher une fois cette étape construite** : soit le guard bloque avant que `CancelOrdersForCustomer` ait besoin de sauter quoi que ce soit, soit un refund remplace le skip.

## 12. Ouvert — à trancher/investiguer avant implémentation

- Séparation authorize/capture dans le sandbox Globex (`sandbox/globex/checkout/index.php`, `sandbox/globex/api/charges.php`) — vrai travail de sandbox, isolé de la PR principale (§10).
- Nouvelle boucle webhook carrier (`carrier-pickup-confirmed`) — aucune infrastructure de simulation n'existe aujourd'hui côté `sandbox/acme`, à construire entièrement (mirroir du pattern checkout→webhook de Globex), isolé de la PR principale (§10).
- `COMPLETED` bloque-t-il encore l'erasure (fenêtre de retour) ou est-il déjà conclu (§11) ?

## 13. Ordre d'implémentation

Application/Domain d'abord, DM (web/api/webhook réels) en dernier — un Processor s'exerce directement en test (`tests.md`), aucune phase n'a besoin du câblage DM pour être développée/testée.

1. **Fait (2026-08-13, PR #64)** : Cross-BC "Gateway namespace, concept-first vs generic" — `PaymentGatewayInterface`/`PaymentSession` → `Application/Payment/`, `CarrierGatewayInterface` → `Application/Carrier/`, vendor clients (`GlobexClient`/`AcmeClient`) démis de `Shared/` vers le BC propriétaire (zéro second consommateur).
2. **Fait (2026-08-13)** : `Shipment::ensureNotCancelled()` (renommé, checke `status.isCancellable()`, plus la comparaison de valeur) + `CancelShipmentOnOrderCancelled` (nouveau Processor, remplace le Projector `onOrderCancelled` qui faisait le travail d'un Processor de façon incomplète, id déterministe) + `ShipmentState::cancellableStatuses()` (règle métier centralisée, réutilisée par `cancel()` et le Finder). **Correction en cours de route** : la cascade `CustomerErased`→Shipment avait d'abord été construite en direct côté Fulfilment.Shipment (`CancelShipmentsOnCustomerErased`/`CancelShipmentsForCustomer`) — deptrac a rejeté ce nouveau lien Shipment→Sales.Customer, et une relecture a confirmé que c'était la bonne architecture à rejeter : annuler un Shipment sur erasure client est une **conséquence business** (pas une protection de données comme le précédent `Sales.Customer→Iam.Identity` cité à tort), donc ça doit passer par Order (principe §3, Order = seul point d'entrée). Design final : `CancelOrdersOnCustomerErased`/`CancelOrdersForCustomer` côté **Sales.Order** (`OrderFinderInterface` étendu en `CollectionFinderInterface`, `byCustomer()` neuf) — annuler l'Order fait déjà partir `OrderCancelledIntegrationEvent`, que `CancelShipmentOnOrderCancelled` consomme déjà. Zéro nouveau lien deptrac Shipment↔Customer. **Reste hors scope, reporté** : nommage CRUD (`create()`/`markDelivered()`/guard `assignTrackingReference()` à l'envers) et redirection `NotifyCustomerOnShipmentDelivered`→`ShipmentDispatched` — bundlés à tort dans le plan initial, en fait des refontes de state machine séparées et plus lourdes ; restent en TODO Fulfilment.Shipment.
3. **Fait (2026-08-13)** : `ShipmentCancelledIntegrationEvent` (signal manquant, corrige le bug `compute()` dormant) **+** la moitié Shipment de §9 (devenue actionnable maintenant, plus en étape 6 — le bug §0 était actif depuis l'étape 2, `CancelShipmentOnOrderCancelled` existant déjà). **Design final, différent de §9 tel qu'écrit** : `CancelShipment` étant routé `async` (`messenger.php`), une exception levée dans `CancelShipmentHandler` n'a plus de Processor à rattraper au même moment d'exécution — `Shipment::cancel()` **enregistre** un nouveau Domain Event (`ShipmentCancellationRejected`) au lieu de throw quand le statut n'est pas cancellable, un fait métier réel (`domain.md` : "no business meaning on the current state → throw" ne s'applique pas ici, un refus constaté a du sens métier). `cancellableStates() = [PENDING]` seul. Nouveau Processor same-BC `NotifyCustomerOnShipmentCancellationRejected` (mirroir exact de `NotifyCustomerOnShipmentDelivered`), notifie directement par email — pas de nouvel Integration Event `ShipmentCancellationRejectedIntegrationEvent`, pas de lien cross-BC vers Sales.Order pour ce refus (scope minimal ; la vraie cohérence Order↔Shipment sur ce cas attend l'étape 6, quand `Order` aura les états pour empêcher ce scénario à la source). `ShipmentInvalidTransitionException::cannotCancel()` supprimée (mort). Seule la compensation `OrderPayment` (void/refund, §9 fin) reste dépendante de l'étape 6 et y reste. **Cascade trouvée en CI** : un `DISPATCHED` ne pouvant plus jamais devenir `CANCELLED`, `RequestPickupOnShipmentDispatched`'s guard `ensureNotCancelled()` (posé en étape 2) protégeait une race devenue impossible — supprimé, avec lui `Shipment::ensureNotCancelled()` et `ShipmentCancelledException` (plus aucun appelant réel), et le test qui l'exerçait. **Dette interim, notée** : `NotifyCustomerOnShipmentDelivered`/`NotifyCustomerOnShipmentCancellationRejected` et leurs `*NotifierInterface`/`Mailer*Notifier` sont candidats à suppression pure (pas migration) une fois le BC `Communication\Notification` construit (`.claude/TODO.md`, Cross-BC) — ne pas les faire évoluer davantage entre-temps, ce sont des Processors jetables.
4. **Fait (2026-08-13/14, PR #69)** : `PlaceOrder` prix (§7) fusionné avec la moitié Product de l'étape 5 (§8) — écrire une comparaison prix/label dans `PlaceOrderHandler` contre `StreamProductResolver` (lecture stream directe, la violation que §8 flague) pour la jeter aussitôt après aurait été le même gâchis que Shipment/§9. `StreamProductResolver`/`ProductResolverInterface`/`Product` (Application) supprimés. **Design final, retravaillé en review (noms ci-dessous remplacent ceux d'un premier jet abandonné — `ProductAvailabilityFinderInterface`/`ProductChangedException`/`ProductNotAvailableException`/`DbalProductAvailabilityProjector`, jamais mergés)** :
   - Nouveau VO Domain `Sales\Order\Domain\ValueObject\Product` (id: string, label: `Label`, price: `Money`) ; `OrderLine` le porte (`product`+`quantity`) au lieu de champs plats.
   - Domain Service `Sales\Order\Domain\Service\OrderLineOffer::ensureStillValid(Product $claimed, ?Product $current): void` — concret, zéro I/O, lève `OutdatedOrderLineException` (Domain).
   - Finder local `ListedProductFinderInterface`/`DbalListedProductFinder` (batch `byIds()`), alimenté par `DbalListedProductProjector` écoutant `ProductListedIntegrationEvent`/`ProductRepricedIntegrationEvent`/`ProductDelistedIntegrationEvent` (delete au delisting) — nommé `ListedProduct` (langage propre à Sales.Order, jamais le nom du BC étranger).
   - `PlaceOrderHandler` orchestre : fetch batch, résout claimed/current par ligne (`resolveLine()`, méthode dédiée), catch `OutdatedOrderLineException` → throw `OutdatedOrderException::forReason($e->getMessage(), $e)` (Application, traduction à la frontière DM — jamais une exception Domain qui fuite dans `apps/web`).
   - `Label` promu `Shared\Domain\ValueObject\Label` (3e occurrence : Catalog.Product + Sales.Order).
   - **Sans JS ni champ soumis par le client** : prix/label snapshotté côté session Symfony (`Web\Session\CatalogSnapshot`, service dédié) au GET, relu au POST — jamais soumis par le client, rien à valider niveau tampering. Snapshot incomplet (session expirée) → `MissingCatalogSnapshotException` (DM-local, `apps/web`), même redirection que le prix périmé.
   - **Buyer non touché** : `BuyerResolverInterface`/`StreamBuyerResolver` restent tels quels, l'adresse (encore une adresse email aujourd'hui) reste nécessaire et bloquée sur le VO `Address` — migrer Buyer avant que `PlaceOrder` porte une vraie adresse serait prématuré.
5. ~~Resolver→Finder Buyer/Product (§8, dépend de 4)~~ — moitié Product faite à l'étape 4 ci-dessus ; moitié Buyer reste bloquée sur l'`Address` VO (Cross-BC TODO.md), reprise quand cet item sera pris.
6. Paiement 2-phases + dispatch confirmé transporteur + `Order` `CONFIRMED`/`DISPATCHED`/`COMPLETED` (§1/§2, le gros morceau) — Application/Domain seulement, sandbox isolée (§12) en sous-morceau à part. Inclut la compensation `OrderPayment` de §9 (void/refund sur `CancelOrder`, désormais seule pièce manquante de ce paragraphe). Reconsidérer au passage TODO ligne 81 (`PaymentSession` sans TTL, staleness du cancel pendant que le client est sur la page checkout) — même surface.
7. Flux retour `REFUNDING`/`RETURNED` (§2/§3) — dépend de 6 (états `Payment`/`Shipment` finaux), réutilise `OrderPayment::refund()` déjà construit en 6, aucun nouveau champ à ajouter dessus.
8. RGPD (§11) — dépend des états `Order` finaux (6+7). Volet suspension (en plus de l'erasure) reste hors scope : bloqué sur `IdentitySuspendedIntegrationEvent`/`ReactivatedIntegrationEvent` (Iam.Identity, TODO ligne 107), pas construits ici.
9. Fold `OrderSummary`→`Order` + combinateur reconstruit (§6) — dernier, une fois tous les états stabilisés, pour ne le construire qu'une fois.
10. Câblage DM réel (formulaire checkout, affichage des statuts, webhooks sandbox) — en dernier, ou incrémentalement par phase si démonstration bout-en-bout voulue à chaque étape.

Hors séquence, découplé du reste : panier + tunnel checkout (`.claude/TODO.md`, "bonus, pas urgent", zéro Domain) — si fait, après l'étape 4 (le shape de `PlaceOrder` doit être stable avant de construire un tunnel qui le rejoue).
