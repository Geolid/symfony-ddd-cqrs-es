# Refacto — frontières de Bounded Context

Document de travail : remise en question du découpage `src/*/*`. Ne pas confondre avec `GOAL.md` (référence as-built) ou `TODO.md` (backlog) — ce fichier ne contient que l'analyse de frontières, pas l'état des états/transitions.

## Périmètre du showcase (2026-09-07) — analyse seule, aucune modification exécutée

Décision de l'utilisateur, pas encore exécutée en code : le but du showcase est de démontrer ES/Onion/DDD/CQRS sur le même domaine exposé par plusieurs DM — pas de couvrir l'exhaustivité d'un vrai e-commerce. Périmètre final visé : catalogue de produit, `Cart`, `Order`, une Compliance qui bloque l'effacement tant que des commandes existent, un cron qui simule la préparation et efface le compte au bout de 30 jours, l'annulation d'une commande tant qu'elle n'a pas commencé à être préparée, un paiement annulé sauf si déjà préparé (capturé), la livraison, et IAM complet (rôles + auth API/password). **`AfterSales.Return`/`Withdrawal` et le leg retour de `Shipment` n'entrent pas dans ce périmètre** — vérifié : ces deux BC ne démontrent aucun patron architectural que Order/Payment/Shipping/Compliance.Erasure ne démontrent déjà (même forme de state machine, mêmes Policy/Integration Event déjà vus ailleurs) ; les garder incomplets (sans jamais aller au remboursement partiel) ne rajoute que du code à maintenir, pas de valeur pédagogique.

### À retirer entièrement (vérifié exhaustivement par grep, rien d'assumé)

- **`src/AfterSales/Return/` en entier** (`Withdrawal`, toutes ses Commands/Events/Policies/Finders/Projectors/Infrastructure) + `tests/AfterSales/Return/`.
- **Le leg retour de `Fulfilment.Shipping`** : `ShipmentDirection` (enum Domain + Application), `RequestReturnShipmentOnWithdrawalRequested`. Une fois `AfterSales.Return` retiré, plus rien n'émet `WithdrawalRequestedIntegrationEvent` — cette policy et l'enum de direction deviennent orphelines.
- **Policies dans les BC qui réagissaient à `Withdrawal`** : `InitiateRefundOnWithdrawalApproved` (`Finance.Refund`), `RequestOrderReturnOnWithdrawalRequested`/`DisputeOrderOnWithdrawalRejected`/`ReturnOrderOnWithdrawalApproved` (`Sales.Order`).
- **`OrderState`/`OrderStatus`** perdent `RETURN_REQUESTED`/`RETURNED`/`DISPUTED` — plus de retour à représenter côté `Order`.
- **`deptrac_bc.yaml`** : layer `AfterSales.Return` retiré, et retiré des rulesets de `Sales.Order`, `Fulfilment.Shipping`, `Finance.Payment`, `Finance.Refund` (les 4 le référencent aujourd'hui).

### `Fulfilment.Shipping` — seul `ShipmentDirection` disparaît, l'aggregate ne se renomme pas

Corrigé après une première erreur (voir Note méthodologique en fin de document) : `Shipment` reste `Shipment`. Le nom n'a jamais affirmé "je gère les deux sens" — c'était le champ `ShipmentDirection` qui l'affirmait, explicitement. Une fois ce champ retiré, l'aggregate redevient un nom générique et correct ("un mouvement physique tracké"), exactement comme `Order`/`Payment` n'ont jamais eu besoin d'un mot pour dire "je ne gère qu'un sens". `origin`/`destination` restent (un futur multi-entrepôt reste possible sans lien avec le retour). Un futur besoin de retour, s'il revient un jour, aurait de toute façon son propre nom distinct (`Withdrawal`, ou autre), jamais un renommage préventif de `Shipment` aujourd'hui.

### `Finance.Refund` reste un aggregate séparé de `Payment` — pas remis en cause par le retrait du retour

Corrigé après une première erreur (idem) : la multiplicité réelle d'un remboursement (un paiement capturé peut légitimement en subir plusieurs dans le temps — retour partiel, mais aussi geste commercial, ajustement) est une propriété du concept `Refund` lui-même, pas de `Withdrawal` qui n'était qu'UN déclencheur parmi d'autres possibles. Retirer `Withdrawal` retire un déclencheur ; ça ne change rien à ce que `Refund` EST. Verdict de la section `Finance.Payment`/`Finance.Refund` plus bas inchangé : fusion en 1 BC, 2 aggregates.

**SUPERSEDÉ (2026-09-07)** — ce raisonnement répond à la mauvaise question. Il vérifie que `Refund` resterait un concept valide SI un déclencheur existait ; il ne vérifie jamais qu'un déclencheur existe encore une fois `Withdrawal` retiré. Voir la correction en fin de section "Analyse actuelle : `Finance.Payment` / `Finance.Refund`" plus bas : le second déclencheur (`Payment::cancel()` sur un paiement capturé) s'avère structurellement inatteignable, indépendamment de `Withdrawal`. `Finance.Refund` est supprimé en entier, pas fusionné.

### Rappel : `Sales.Cart` reste à construire (conception déjà actée plus bas)

`Cart`/`CartLine` (Entity, mutable, `checkout()` gardien de fraîcheur) — voir section `Sales.Order`/`OrderLine`/`Cart` plus bas pour le détail complet. Seule vraie question encore ouverte : `Cart` mérite-t-il sa propre BC (`Sales.Cart`, sœur de `Sales.Buyer`/`Sales.Order`) ou reste-t-il un aggregate à l'intérieur de `Sales.Order` ? Pas encore appliqué le test à 4 critères dessus explicitement.

### `OrderLine` reste un Entity — pas remis en cause par le retrait du retour

Corrigé après une première erreur (idem) : la classification Entity vs VO d'`OrderLine` s'est décidée sur ce que le concept EST (une ligne de commande a une identité locale, indépendamment de qui la lit aujourd'hui), jamais sur l'existence d'une commande d'amendement dans ce showcase. Que ce showcase construise ou non l'amendement post-paiement ne change rien à la nature du concept. Verdict de la section `Sales.Order`/`OrderLine`/`Cart` plus bas inchangé.

## Critères d'évaluation d'une frontière de BC

Une frontière de BC se juge sur la théorie DDD/CQRS/ES, jamais sur la forme du code existant ou une convention déjà écrite. Quatre critères, à appliquer ensemble :

1. **Vocabulaire distinct** — le langage ubiquitaire d'un côté de la frontière a-t-il un sens que l'autre côté n'a aucune raison de porter (ex: "numéro de TVA" n'a aucun sens côté livraison) ? Si les deux côtés emploient les mêmes mots pour la même chose, ce n'est pas une frontière.
2. **Direction d'intégration acyclique — au niveau du processus, pas du graphe brut.** Une dépendance dans les deux sens entre deux BC ne disqualifie que si elle relie les deux dans **une seule opération métier** : compléter cette opération exige un aller-retour entre les deux BC où aucun des deux ne peut conclure seul, et/ou les deux répliquent le même sous-état l'un de l'autre (c'est la forme réelle du cas Payment/Refund ci-dessous). Deux flux d'événements indépendants et unidirectionnels qui pointent chacun dans un sens différent entre les deux mêmes BC — chaque BC réagissant à l'autre pour une raison propre et sans coordination synchrone ni état partagé — ne sont **pas** ce cycle-là, même si le graphe de dépendances, lu brut, semble bidirectionnel. **Erreur commise en session (2026-09-07)** : `register()` sur `Compliance.Erasure` réagissant à `IdentityRegisteredIntegrationEvent` a été rejeté à tort comme "cycle avec `Iam.Identity → Compliance.Erasure`" sans vérifier que `Sales.Order ↔ Compliance.Erasure` est déjà bidirectionnel et accepté dans ce même `deptrac_bc.yaml`, pour exactement la même raison bénigne (holds vs projection `erasurePending`, aucune décision jointe). Toujours vérifier le ruleset complet, jamais seulement l'arête citée, avant de disqualifier sur ce critère.
3. **Cadence et moteur de changement propres** — les deux côtés changent-ils pour des raisons différentes, portées par des parties prenantes différentes (ex: loi fiscale vs intégration technique d'un PSP) ? Un mimétisme d'état (un côté rejoue exactement le sous-cycle de l'autre) n'est pas un moteur de changement propre.
4. **Invariant et multiplicité réels** — le concept a-t-il une règle de cohérence qui n'existe nulle part ailleurs (ex: numérotation séquentielle légale, solde remboursable ≤ montant capturé), et/ou une multiplicité réelle (1-N) qui justifie sa propre identité et son propre stream ? Un aggregate qui ne fait que refléter l'état d'un autre sans invariant propre n'est pas un concept autonome.

Un split qui échoue sur le critère 2 — au sens processus, pas graphe — est disqualifié à lui seul, indépendamment des trois autres.

## Analyse actuelle : `Finance.Payment` / `Finance.Refund`

### Constat dans le code

- `deptrac_bc.yaml` déclare une dépendance **bidirectionnelle** : `Finance.Payment → Finance.Refund` et `Finance.Refund → Finance.Payment`. Échec net du critère 2.
- Exécuter "rembourser un paiement" traverse la frontière 4 fois : `Payment.cancel()` émet `PaymentRefundRequired` (integration event) → policy Refund `InitiateRefundOnPaymentRefundRequired` → `Refund.initiate()` émet `RefundInitiated` (integration event) → policy Payment `RequestPaymentRefundOnRefundInitiated` → `Payment.requestRefund()` émet `PaymentRefundInitiated` (domain event) → policy Payment `RefundPaymentOnPaymentRefundInitiated` appelle le gateway PSP → `PaymentRefundConfirmed` (integration event) → policy Refund `ConfirmRefundOnPaymentRefundConfirmed` → `Refund.confirm()`.
- `Payment` porte déjà tout l'état nécessaire au remboursement dans sa propre state machine (`PaymentState::REFUNDING`/`REFUNDED`, `pendingRefundId`) — `Refund` (`RefundState::INITIATED`/`REFUNDED`/`FAILED`) rejoue ce même sous-cycle en miroir. Échec du critère 3 (pas de moteur de changement propre, juste un reflet).
- L'exécution technique (appel au gateway PSP) est faite par la policy de **Payment**, alors que le suivi d'état "réussi/échoué" appartient à **Refund** — la responsabilité technique et le suivi d'état sont scindés entre les deux BC sans raison métier, seulement un artefact du découpage.

### Ce qui reste un vrai concept propre

- `Refund` a un déclencheur réel indépendant de `Payment` : `AfterSales.Return` (`WithdrawalApproved`) initie aussi un remboursement, en plus du déclencheur `Payment.cancel()`.
- Un paiement capturé peut légitimement subir plusieurs remboursements dans le temps (retour partiel, puis un second) — relation 1-N réelle, avec ses propres invariants (montant ≤ solde encore remboursable, pas de double confirmation) distincts de ceux de `Payment`. Le modèle actuel ne l'exploite pas encore (`pendingRefundId` singulier, `REFUNDING` exclusif) mais l'invariant existe en germe. Passe le critère 4.

### Verdict

Fusionner `Finance.Payment` et `Finance.Refund` en une seule BC. Garder `Payment` et `Refund` comme deux aggregates distincts (justifié par le critère 4 : multiplicité 1-N + invariants propres), mais :

- communication entre les deux via événements de domaine ordinaires (`#[Subscribe]` intra-BC), plus d'Integration Events ni de Publishers pour ce flux — suppression de 4 classes d'Integration Event + 4 Publishers ;
- `Refund` exécute lui-même l'appel au gateway PSP pour son propre remboursement (accès direct à `PaymentGatewayInterface`), au lieu de le laisser à une policy de `Payment` ;
- `Payment` garde un signal minimal ("un remboursement est en cours contre moi") pour protéger sa propre ressource PSP (empêcher capture/second remboursement concurrent) — invariant propre à `Payment`, alimenté par un événement de domaine de `Refund` plutôt que par un aller-retour d'Integration Events.

### Hors scope immédiat, mais qualifié par les critères

Si `Refund` devait un jour émettre des avoirs (crédit fiscal/comptable), ce serait une **troisième** BC (`Finance.Billing`/`Invoicing`), pas un maintien de `Refund` séparé de `Payment`. L'avoir a son propre vocabulaire (numérotation légale, mentions fiscales), sa propre cadence de changement (loi fiscale, pas intégration PSP), et une relation naturellement acyclique (`Billing` écoute `RefundConfirmed`, ne renvoie jamais rien à `Refund`) — passe les 4 critères sans réserve, contrairement au cas `Payment`/`Refund` actuel.

### Correction (2026-09-07) : le verdict de fusion est remplacé par une suppression — plus aucun déclencheur atteignable

Question posée par l'utilisateur pendant la planification de la fusion ci-dessus : si une commande ne peut être annulée qu'avant préparation, et que le paiement n'est capturé qu'au moment de la préparation, la branche `CAPTURED` de `Payment::cancel()` (celle qui produit `PaymentRefundRequired`, seul déclencheur de `Refund` restant après le retrait de `Withdrawal`) a-t-elle seulement une chance de s'exécuter un jour ?

Vérifié dans le code réel, chaîne complète :
- `Payment::capture()` n'est déclenché que par `ShipmentPreparedIntegrationEvent` (`CapturePaymentOnShipmentPrepared`).
- `Order::cancel()` (buyer, seul appelant `CancelOrder` — y compris `CancelOrdersOnBuyerErased`) jette `OrderNotCancellableException` dès que `Order` atteint `PREPARED` — état atteint via ce **même** `ShipmentPreparedIntegrationEvent` (`PrepareOrderOnShipmentPrepared`). L'annulation buyer est donc bloquée exactement au moment où le paiement vient d'être capturé, jamais après.
- `Order::abort()` (système) n'a qu'un seul appelant, `AbortOrderOnPaymentFailed`, réagissant à `PaymentFailedIntegrationEvent`. Or `Payment::fail()` n'est atteignable que depuis `REQUESTED`/`AUTHORIZED` (`TRANSITIONS[CAPTURED]` ne menait qu'à `REFUNDING`, aucun chemin vers `FAILED`) — un paiement déjà capturé ne peut plus jamais échouer, donc `abort()` ne peut plus se déclencher après capture non plus.

Les deux seuls chemins vers `Payment::cancel()` sont donc structurellement bloqués avant que la capture n'ait jamais lieu. La branche `CAPTURED` de `Payment::cancel()` est du code mort — et avec elle, tout `Finance.Refund`, puisque son seul autre déclencheur (`Withdrawal::approve()`) était déjà supprimé. Exactement le même raisonnement que celui qui a justifié le retrait de `Withdrawal` (voir "Périmètre du showcase" en tête de document) : pas de valeur pédagogique à garder un concept que rien ne peut jamais déclencher.

**Verdict remplacé** : `Finance.Refund` est supprimé en entier (aggregate, BC, `deptrac_bc.yaml`, wiring DI), pas fusionné dans `Finance.Payment`. `Payment::cancel()` perd sa branche `CAPTURED`/`PaymentRefundRequired` ; `Payment` perd `PaymentState::REFUNDING`/`REFUNDED`, `pendingRefundId`, `requestRefund()`/`confirmRefund()`/`failRefund()`, les Integration Events `PaymentRefundRequired`/`RefundInitiated`/`PaymentRefundConfirmed`/`PaymentRefundFailed` et leurs Publishers/Policies ; `PaymentGatewayInterface::refund()` (et `PaymentGatewayStatus::REFUNDING`/`REFUNDED`) disparaît aussi, plus aucun appelant. `GOAL.md` mis à jour en conséquence. Toute l'analyse de découpage BC ci-dessus (critères 1-4, verdict "fusion 1 BC/2 aggregates") reste correcte en tant que raisonnement — elle répondait juste à une question qui ne se posait plus une fois la portée d'exécution du domaine vérifiée.

## Analyse conceptuelle : `Iam.Identity` / `Iam.Authentication` / futur `Iam.Access`

Analyse à froid, sans lecture de code — projection des 4 critères sur un découpage à trois : Identity (l'acteur), Authentication (ses moyens de preuve), et un futur Access (rôles/entitlements), avec l'hypothèse "1 Identity : N Role".

1. **Vocabulaire distinct** — "rôle", "entitlement", "permission" appartiennent à un langage de politique d'accès, distinct de "qui existe" (Identity) et "comment il le prouve" (Authentication). Trois métiers réels, souvent portés par des parties prenantes différentes (gestion des comptes / sécurité des credentials / gouvernance des droits). Passe.
2. **Direction acyclique** — Access dépend d'Identity (a besoin de savoir qu'un acteur existe), jamais l'inverse. Point de vigilance : si un jour "il faut un rôle minimum pour se connecter" tente de faire dépendre `Authentication` d'`Access`, ça recrée le cycle constaté sur `Payment`/`Refund`. Un tel gate doit rester un invariant propre d'`Identity` (ex: état actif/suspendu), jamais délégué à `Access`.
3. **Cadence propre** — le modèle de rôles change pour des raisons de politique métier (nouvelle fonctionnalité, séparation des tâches, réorganisation), indépendantes de ce qui fait évoluer l'authentification (nouveaux facteurs, SSO) ou l'identité (cycle d'inscription/effacement). Passe.
4. **Invariant/multiplicité** — "1 Identity : N Role" cache en fait deux concepts à ne pas fusionner :
   - la **définition** d'un rôle (catalogue de référence : quelles permissions compose "admin", versionné, gouverné indépendamment de tout acteur précis) ;
   - l'**octroi** d'un rôle à une identité (fenêtre de validité, octroyeur, révocable, potentiellement temporaire) — un invariant temporel/d'audit propre qu'Identity ne porte pas.

   Modéliser Access comme un simple tableau de rôles accroché à Identity retomberait dans le travers `Payer`/`Buyer` (un état qui ne fait que refléter Identity, sans moteur de changement propre). Modélisé comme un aggregate de Grant (identité propre, fenêtre de validité, octroyeur) référençant un catalogue de Role séparé, le critère 4 passe.

### Verdict

Access mérite sa propre BC, à deux conditions : séparer catalogue de rôles (référence) et octroi (grant avec ses propres invariants), et garder la dépendance strictement à sens unique vers Identity — jamais l'inverse, sous peine de reproduire le cycle Payment/Refund.

## Analyse conceptuelle : `Fulfilment.Shipping` livraison vs retour — un seul aggregate ou deux ?

Analyse à froid, sans lecture de code — le "début différent" entre livraison (déclenchée par la confirmation de commande) et retour (déclenché par une demande de retour acceptée dans la fenêtre colis-reçu+14 jours max) suffit-il à justifier deux aggregates de transport séparés ?

1. **Vocabulaire** — préparer/étiqueter/expédier/livrer, transporteur, numéro de suivi : strictement le même vocabulaire dans les deux sens. Aucun mot propre à "retour" au niveau du transport lui-même. Échec du critère pour justifier deux aggregates.
2. **Direction acyclique** — neutre : un seul aggregate avec une metadata de sens, ou deux aggregates écoutant chacun leur déclencheur amont, aucun des deux ne crée de cycle.
3. **Cadence propre** — le *mécanisme* de transport ne change pas pour des raisons différentes selon le sens, tant que les étapes (préparer/étiqueter/expédier/livrer) restent identiques des deux côtés. La cadence différente perçue (fenêtre de 14 jours, SLA retour) ne pilote pas le transport, elle pilote la décision de le déclencher, en amont.
4. **Invariant/multiplicité** — "colis reçu + 14 jours max" est un garde qui appartient à la **demande** de retour (`Withdrawal::request()`), pas à l'exécution du transport retour. Une fois la demande acceptée, le mouvement physique qui suit n'a plus aucune règle qui le distingue d'un envoi sortant.

**Verdict initial** : un seul aggregate de transport suffit. Le "début différent" est déjà correctement scindé, mais en amont — comme deux déclencheurs distincts (`Sales.Order` / `AfterSales.Return`), chacun avec son propre invariant, poussant tous les deux vers le même mécanisme de transport en aval. Scinder aussi le transport dupliquerait un cycle identique pour capturer une distinction qui existe déjà ailleurs.

La projection d'`Order` agrégeant des infos de `Shipment`/`Payment` pour informer le client est une question orthogonale, côté lecture (CQRS) — elle ne pèse ni pour ni contre le split côté écriture.

**SUPERSEDÉ — voir section suivante.** Le raffinement `PREPARED` sauté pour le retour révèle que ce verdict sous-estimait la divergence d'invariant entre les deux sens.

## Correction : `PREPARED` sauté pour le retour révèle un vrai besoin de deux aggregates

`PREPARED` n'a de référent réel que côté aller : `ShipmentPreparedIntegrationEvent` déclenche `Payment::capture()` (`GOAL.md` ligne 150) — c'est le gate qui protège "ne jamais capturer avant que le colis soit physiquement prêt". Côté retour, rien n'est préparé par le marchand ; le client empaquette lui-même. Passer directement `REQUESTED → MANIFESTED` pour le retour est donc correct : ça retire une étape fictive, pas une étape réelle.

Mais faire cohabiter les deux graphes dans un seul aggregate `Shipment` obligerait chaque méthode concernée (`prepare()`, `manifest()`) à tester `direction` pour savoir quel graphe appliquer. C'est le signal à ne pas ignorer : une invariant aussi critique que "ne jamais expédier sans avoir capturé le paiement" ne serait alors plus garantie par la forme de l'agrégat, mais par la présence correcte d'un `if` dans chaque méthode, pour toujours, dans tout futur changement — un `if` oublié ou mal branché ferait passer un envoi sortant en `MANIFESTED` sans capture de paiement. Un conditionnel qui protège un comportement différent selon un type cache un type manquant, pas un enum de plus (cf. Fowler, *Replace Conditional with Polymorphism* ; Evans sur les invariants protégés par la forme de l'agrégat, pas par discipline).

**Verdict corrigé** : scinder `Shipment` en deux aggregates — `OutboundShipment`/`Delivery` et `ReturnShipment` — au sein de la **même** BC `Fulfilment.Shipping` (aucune des deux paires de critères 1-3 n'a changé, donc pas de raison de scinder la BC elle-même, seulement l'aggregate). Chaque classe n'expose que les méthodes valides pour son propre cycle :
- `OutboundShipment` : `request() → prepare() → manifest() → dispatch() → deliver()` — l'ordre est imposé par l'API elle-même (pas de méthode `manifest()` avant `prepare()` possible), pas par un test runtime.
- `ReturnShipment` : `request() → manifest() → dispatch() → deliver()` — pas de méthode `prepare()` du tout ; l'étape sautée devient inexprimable plutôt que juste non testée.

Ce qui reste partagé sans recréer le problème (infrastructure/services, pas identité ni invariant) : `CarrierGatewayInterface`, `TrackingNumber`, l'appel transporteur, le mécanisme de réconciliation.

Ce cas illustre concrètement le seuil du critère 4 : une différence de *cadence*/metadata ne suffit pas à justifier un split (cf. `ShipmentDirection` avant ce raffinement), mais une différence d'*invariant critique non protégeable par un conditionnel* le justifie.

## Trou fonctionnel confirmé : le leg retour est un point aveugle pour `Order`

Vérifié dans `GOAL.md` (état as-built, lignes 234-243, PR #179), pas dans le code directement — mais `GOAL.md` fait référence.

**Ce qui se passe quand une demande de retour est acceptée (dans le délai)** : `Withdrawal::request()` → `REQUESTED`, ce qui déclenche `WithdrawalRequestedIntegrationEvent`, consommé **en parallèle** par deux BC :
- `Fulfilment.Shipping` crée le `Shipment` retour (`RequestShipment`, direction RETURN) — état `REQUESTED`, pas encore d'étiquette.
- `Sales.Order` passe à `RETURN_REQUESTED` (`RequestOrderReturnOnWithdrawalRequested`).

**Puis plus rien côté `Order` jusqu'au verdict d'inspection** (`WithdrawalApproved`→`Order::return()`→`RETURNED`, ou `WithdrawalRejected`→`Order::dispute()`→`DISPUTED`). Aucune ligne du tableau `GOAL.md` ne fait remonter à `Order` ni `ShipmentPrepared`, ni `ShipmentManifested`, ni `ShipmentDispatched` du leg retour. Même `ShipmentDelivered` (leg retour) ne va qu'à `Withdrawal::receive()` en interne (→ `RECEIVED`) — jamais remonté à `Order`.

**Comparaison avec le leg aller** (lignes 150-156) : Order y reçoit un flux continu — `ShipmentPrepared`→`PREPARED`, `ShipmentDispatched`→`DISPATCHED`, `ShipmentDelivered`→`DELIVERED`. Le leg retour n'a rien d'équivalent : ni étiquette prête (`ShipmentManifested`), ni colis pris en charge par le transporteur (`ShipmentDispatched`), ni colis arrivé à l'entrepôt (`ShipmentDelivered`, capté par `Withdrawal` mais jamais exposé à `Order`) ne sont visibles côté client entre la demande acceptée et le verdict.

**Ce n'est pas un problème de frontière de BC** — `Fulfilment.Shipping` possède légitimement ces jalons. C'est une asymétrie de traitement entre les deux legs qui ne découle d'aucune règle métier énoncée : seul le strict nécessaire pour faire avancer `Withdrawal` a été câblé, pas ce qu'il faut pour informer `Order`/le client pendant le transport retour.

## Conception : `Compliance.Erasure` — Subject / Hold / effacement

**Contexte** : acteur `Subject` (id = identity, même id référencé par les attributs crypto Patchlevel/`#[DataSubjectId]`). Processus : demande d'effacement, pending 30 jours, CLI périodique qui approuve et drop la clé crypto (crypto-shredding). Contrainte : bloquer les nouvelles actions métier dès la demande (pas seulement au moment du drop) pour ne jamais permettre un report indéfini de l'effacement par une activité continue qui reposerait des holds à l'infini.

### Rejeté : lock applicatif (`LockingTrait`) autour du handler d'approbation
Proposé par un pair (`symfony-ddd-cqrs-es-40`), vérifié réel dans ce repo (`Shared\Infrastructure\Locking\LockingTrait`, `LockingRequestWithdrawalHandler` existent tous les deux). Rejeté pour deux raisons cumulatives :
1. Lock coopératif, pas une garantie du moteur de persistance — protège seulement si **chaque** écrivain de hold acquiert la même clé de lock ; un futur hold-writer qui l'oublie rouvre la race silencieusement, sans qu'aucun outil ne le détecte.
2. Plus fondamental : `ActiveHoldCheckerInterface` (code actuel) est backé par un Finder (read-model). `config/packages/patchlevel_event_sourcing.php` montre que `Policy::GROUP`/`Projector::GROUP` ne tournent en synchrone après save qu'en dev/demo/test — en base (donc en prod), seul `Publisher::GROUP` l'est. En prod, ni les Policies qui posent les holds ni les Projectors qui alimentent le Finder ne sont synchrones : la staleness n'est pas une race de quelques millisecondes qu'un lock fermerait, c'est une fenêtre potentiellement large (queue asynchrone) qu'aucun lock autour du seul handler d'approbation ne peut fermer.

### Rejeté : charger 2 aggregates dans le handler pour décider
```php
$subject = $this->subjectRepository->load($subjectId);
$erasure = $this->erasureRepository->load(ErasureId::forSubject($subjectId));
$erasure->approve($this->clock->now(), $subject->hasActiveHolds());
```
Ask-then-Tell : l'invariant "pas d'approbation s'il reste un hold" n'est protégée par **aucun** des deux aggregates, seulement par la justesse du code d'orchestration du handler — rien n'empêche un futur call site de passer un booléen erroné.

### Retenu : un seul aggregate `Subject`, une seule méthode auto-suffisante

Nom : `Subject` conservé (terme GDPR standard, déjà référencé par les attributs crypto) — le problème n'était jamais le nom de la classe, seulement le choix des verbes de transition.

États : `RETAINED → ERASING → ERASED` (retour `ERASING → RETAINED` sur annulation).
- `RETAINED`, pas `ACTIVE` : `IdentityState::ACTIVE` existe déjà (vérifié) pour un axe différent (capacité d'authentification) — réutiliser "ACTIVE" ici collisionnerait deux concepts distincts.
- `ERASING`/`ERASED`, pas `PENDING_ERASURE` : aligné sur un précédent déjà présent dans ce repo, `PaymentState::REFUNDING → REFUNDED` (participe présent = en cours, participe passé = terminé, toujours un seul mot).

```php
public static function register(SubjectId $id, \DateTimeImmutable $registeredAt): self    // né à l'inscription identité
public function placeHold(HoldReference $reference, \DateTimeImmutable $placedAt): void
public function liftHold(HoldReference $reference, \DateTimeImmutable $liftedAt): void
public function requestErasure(\DateTimeImmutable $requestedAt): void                     // RETAINED -> ERASING
public function cancelErasure(\DateTimeImmutable $cancelledAt): void                       // ERASING -> RETAINED
public function release(\DateTimeImmutable $now): void                                     // ERASING -> ERASED
```

`register()` réagit à `IdentityRegisteredIntegrationEvent` via une Policy `RegisterSubjectOnIdentityRegistered` (`Compliance.Erasure`). Tous les `Application/Command/*Handler` (`PlaceHold`, `LiftHold`, `RequestErasure`, `CancelErasureRequest`, `EraseSubject`) font un `load()` inconditionnel — aucun `has()`-check, aucun branchement statique/instance.

### Épisode corrigé (2026-09-07) : genèse paresseuse (`place()`/`request()`) rejetée à tort, `register()` restauré

`register()` a été abandonné en implémentation au profit d'une genèse paresseuse à deux factories statiques (`place()` déclenché par le premier `PlaceHold`, `request()` par la première `RequestErasure`, chaque Handler faisant `has() ? load()+méthode d'instance : Factory statique`), au motif que `register()` exigerait `Compliance.Erasure → Iam.Identity` dans `deptrac_bc.yaml`, alors que `Iam.Identity → Compliance.Erasure` existe déjà (`EraseIdentityOnSubjectErased`) — jugé cycle disqualifiant par le critère 2. **Ce motif était faux** : `Sales.Order ↔ Compliance.Erasure` est déjà bidirectionnel dans ce même `deptrac_bc.yaml` et déjà accepté, pour la même raison bénigne (deux flux d'événements indépendants et unidirectionnels, aucune décision jointe, aucun état partagé) — voir la reformulation du critère 2 plus haut. La disqualification n'aurait jamais dû s'appliquer ici non plus.

La genèse paresseuse est donc revenue en arrière : `register()` restauré comme seule genèse, `deptrac_bc.yaml` reçoit `Iam.Identity` dans les dépendances autorisées de `Compliance.Erasure`, tous les Handlers redeviennent uniformes (`load()` inconditionnel, plus de branchement create-or-load). Ça supprime au passage l'asymétrie de nommage `place()`/`placeHold()` (un mot d'écart pour des préconditions opposées) qui avait été notée sans être reliée au vrai problème.

### Correction : guards de transition par `TRANSITIONS`/`CanTransitionToSpecification`, pas des comparaisons d'enum brutes

`requestErasure()`/`cancelErasure()`/`release()` comparaient directement `$this->state` à un cas de `SubjectState` (`SubjectState::RETAINED !== $this->state`). `Subject` a un vrai graphe de transitions (`ERASING -> RETAINED` est un arc de retour réel, pas seulement des cas isolés à identifier), même taille que `Finance.Refund` (3 états, un état à deux arcs sortants), qui utilise déjà `private const array TRANSITIONS` + `CanTransitionToSpecification` pour ses deux transitions gardées. `Subject` suit désormais le même patron :
```php
private const array TRANSITIONS = [
    SubjectState::RETAINED->value => [SubjectState::ERASING],
    SubjectState::ERASING->value  => [SubjectState::RETAINED, SubjectState::ERASED],
    SubjectState::ERASED->value   => [],
];
```
`requestErasure()`/`cancelErasure()`/`release()` gardent chacun via `CanTransitionToSpecification(self::TRANSITIONS, <cible>)->isSatisfiedBy($this->state)` — `release()` garde ce guard EN PLUS de ses deux guards indépendants déjà en place (fenêtre de rétention, holds actifs), qui ne sont pas des préoccupations de graphe de transitions. `SubjectState::isErasing()`/`isErased()` n'avaient alors plus aucun appelant réel (vérifié par grep) — supprimés avec leur test, `SubjectStateTest.php` (un enum sans méthode n'a pas de test dédié, même précédent que `RefundState`, qui n'en a pas non plus).

`release()` ne prend aucun collaborateur injecté — tout est déjà interne à l'aggregate :
```php
if (!new CanTransitionToSpecification(self::TRANSITIONS, SubjectState::ERASED)->isSatisfiedBy($this->state)) return;
if (!new ErasureRetentionExpiredSpecification($now)->isSatisfiedBy($this->requestedAt)) return;
if (count($this->activeHolds) > 0) return;
$this->recordThat(new SubjectErased(...));
```
Appelée sans condition par la CLI sur chaque sujet `ERASING`, à chaque run (Tell-Don't-Ask) — "pas encore éligible" est un no-op normal (poll périodique), pas une erreur. Plus de `ActiveHoldCheckerInterface`/Finder dans la boucle critique, plus de double-load handler, plus de second aggregate `Erasure` : le suivi des holds et la décision d'achèvement vivent dans le même stream ES, donc la même version/concurrence optimiste protège l'invariant nativement — aucun lock applicatif requis.

### `ErasureRetentionExpiredSpecification` — réutilisée read-side et write-side
Précédent direct dans ce repo : `WithdrawalWindowExpiredSpecification` est déjà utilisée à la fois dans `Withdrawal::request()` (write) et dans la query `CanRequestWithdrawal` (read). Même mécanique ici :
```php
final readonly class ErasureRetentionExpiredSpecification
{
    public function __construct(private \DateTimeImmutable $now) {}
    public function isSatisfiedBy(\DateTimeImmutable $requestedAt): bool
    {
        return $this->now > $requestedAt->modify('+30 days');
    }
}
```
Utilisée dans `Subject::release()` ET dans `ListSubjectsDueForErasureHandler` (déjà existante) pour sélectionner les candidats côté CLI — une seule règle des 30 jours, pas deux endroits à synchroniser.

### `HoldReference` — VO à deux strings, pas un enum fermé côté Compliance
Rejeté : réutiliser `Shared\Application\Uniqueness\UniqueKey` (discriminant `BackedEnum`). `UniqueKey` convient à un vocabulaire fermé et local à une seule BC (`BuyerUniqueKey`, `PaymentUniqueKey`...). Les sources de hold sont ouvertes et croissent au fil du temps, ajoutées par des BC qui n'existent pas encore — un `BackedEnum` défini dans `Compliance.Erasure` forcerait toute nouvelle BC à faire modifier et redéployer l'enum de Compliance avant de pouvoir poser un hold : pas cyclique au sens deptrac, mais un couplage à l'envers (Compliance comme registre central obligatoire).

```php
final readonly class HoldReference
{
    public function __construct(public string $sourceType, public string $sourceId) {}
    public static function for(string $sourceType, string $sourceId): self { return new self($sourceType, $sourceId); }
    public function equals(self $other): bool { return $this->sourceType === $other->sourceType && $this->sourceId === $other->sourceId; }
    public function toString(): string { return \sprintf('%s:%s', $this->sourceType, $this->sourceId); }
}
```

Pas de `const` sur l'aggregate source (`Order::HOLD_SOURCE_TYPE` envisagé puis écarté — ce n'est pas une décision métier d'Order de se savoir "source de hold pour Compliance", lui faire porter cette constante pollue son namespace pour le confort d'un tiers). Le littéral se réécrit une seule fois, à l'endroit qui l'utilise réellement : la Policy de Compliance qui réagit à l'event de la BC source (`PlaceHoldOnOrderPlaced` — une policy = un event = un seul call site, par construction du pattern Policy de ce repo). Une `private const` locale à cette policy évite le magic string pour la lisibilité, sans aucune implication d'architecture :
```php
final readonly class PlaceHoldOnOrderPlaced
{
    private const string SOURCE_TYPE = 'sales.order.order';
    // ...
}
```

### Rejeté : déplacer `ErasedFieldSentinel` de `Shared` vers `Compliance.Erasure.Domain`
Objectif visé : rendre explicite dans `deptrac_bc.yaml` le graphe des BC concernées par le crypto-shredding. Rejeté : `Compliance.Erasure` dépend aujourd'hui de `Sales.Order` seul (holds) ; si `Sales.Buyer`/`Finance.Payer`/futur `Communication.Subscriber` doivent importer le Sentinel depuis `Compliance.Erasure` pour leurs propres champs personnels, la dépendance devient cyclique dès que Compliance a besoin, plus tard, de lire directement l'un de ces BC (probable vu la trajectoire de cette conception). `ErasedFieldSentinel` ne porte d'ailleurs aucune décision métier de Compliance — pure fonction de formatage, légitimement `Shared`, au même titre que `Money`/`UniqueKey`. La visibilité du graphe GDPR recherchée existe déjà, plus précisément, via un grep/règle statique sur `#[SensitiveData]`/`#[DataSubjectId]` — field par field, sans dépendance de code entre BC.

### Correction (2026-09-07) : `release()` → `erase()`, Commands renommés pour référencer `Subject`

Deux incohérences de nommage signalées par l'utilisateur, vérifiées contre le code réel :

1. `EraseSubjectHandler` appelait `$subject->release(...)`, alors que tout autre `Erase<X>` du repo appelle `->erase()` sur son aggregate (`EraseIdentityHandler`→`$identity->erase()`, `EraseBuyerHandler`→`$buyer->erase()`, `ErasePayerHandler`→`$payer->erase()`). `Subject::release()` était même incohérent avec son propre event (`SubjectErased`, pas `SubjectReleased`) — chaque transition à outcome unique du repo enregistre `<Aggregate><VerbeAuParticipe>` (`Refund::confirm()`→`RefundConfirmed`...). Vérifié : `Payment::cancel()` diverge bien de son event selon l'état (plusieurs outcomes possibles), mais `Subject::release()` n'a qu'un seul outcome (`SubjectErased` si les guards passent) — cette exception ne s'applique donc pas ici. Renommé `Subject::release()` → `Subject::erase()`.

2. Un Command référence toujours le nom de l'**aggregate**, jamais celui de la BC, même quand ils diffèrent — preuve la plus nette : `Catalog.Listing` (BC) a pour aggregate `Product`, et ses 3 commands (`DelistProduct`/`PublishProduct`/`RepriceProduct`) référencent tous `Product`, jamais `Listing`. `Compliance.Erasure` a pour aggregate `Subject`, mais 4 de ses 6 commands ne le référençaient pas : `RequestErasure`/`CancelErasureRequest` référençaient "Erasure" (le nom de la BC), `PlaceHold`/`LiftHold` ne référençaient ni la BC ni l'aggregate. Renommés, gabarit verbe+aggregate+qualificatif (`DefinePayerPostalAddress`) et alignement strict sur la méthode d'aggregate appelée (`cancelErasure()`, pas `cancelErasureRequest()`) :
   - `PlaceHold` → `PlaceSubjectHold`
   - `LiftHold` → `LiftSubjectHold`
   - `RequestErasure` → `RequestSubjectErasure`
   - `CancelErasureRequest` → `CancelSubjectErasure`

   Policies renommées en cascade (`<Action>On<Event>` reflète le Command dispatché) : `PlaceHoldOnOrderPlaced`→`PlaceSubjectHoldOnOrderPlaced`, `LiftHoldOnOrder{Aborted,Cancelled,Delivered}`→`LiftSubjectHoldOnOrder{Aborted,Cancelled,Delivered}`. `RegisterSubject`/`EraseSubject` respectaient déjà la convention, inchangés.

### Correction (2026-09-07) : `Hold` seul est un mot métier générique ambigu, `ErasureHold` partout

Nouvelle incohérence signalée par l'utilisateur sur `PlaceSubjectHold` (lu comme "mettre le Subject en pause") : `Hold` seul est un terme anglais métier générique et ambigu hors de tout contexte (credit hold, account hold, shipping hold, fraud hold...), sans lien intrinsèque avec l'effacement — même famille de problème que "Address" pour `PostalAddress`. Vérifié, deux précédents réels et identiques dans ce repo : `Finance.Payment` a `AddressResult` ET `BillingAddressResult` côte à côte, `AfterSales.Return` a `AddressResult` ET `ShippingAddressResult` — un qualificatif devant un nom générique pour lever l'ambiguïté est une pratique déjà établie, pas une invention. Un id d'event à 3 segments de fait n'a rien d'inédit non plus : `finance.payer.payer.postal_address_defined`, `sales.buyer.buyer.postal_address_defined`.

Renommé partout où `Hold` apparaît en API publique/traversante (pas la propriété privée `$activeHolds`, qui ne se lit jamais hors du contexte de sa propre classe) :
- `Subject::placeHold()`→`placeErasureHold()`, `Subject::liftHold()`→`liftErasureHold()`
- `HoldPlaced`/`HoldLifted` → `SubjectErasureHoldPlaced`/`SubjectErasureHoldLifted` — pas `ErasureHoldPlaced` bare : grep exhaustif de tous les Domain Events du repo (Buyer, Identity, Product, Order, Payer, Payment, Refund, Withdrawal, Shipment, ~40 events, zéro exception), chacun est préfixé par le nom de SON PROPRE aggregate même quand il diffère du nom de BC/dossier (`ProductDelisted` pas `ListingDelisted`, `WithdrawalApproved` pas `ReturnApproved`, `ShipmentManifested` pas `ShippingManifested`) — la clause de substitution UL de `domain.md` existe mais n'est en réalité JAMAIS exercée dans ce repo ; `HoldPlaced`/`HoldLifted` (et ma première correction `ErasureHoldPlaced`/`ErasureHoldLifted`) l'invoquaient à tort, sans précédent réel à l'appui. `#[Apply]` reste `applyErasureHoldPlaced`/`applyErasureHoldLifted` (préfixe aggregate "Subject" dropped, même règle que `applyErasureRequested`).
- `HoldReference` (VO) → `ErasureHoldReference` — celui-ci n'a PAS besoin du préfixe `Subject` : ce n'est pas l'identité de Subject (c'est `SubjectId`), c'est une référence vers une cause externe, même statut qu'un `Reason`/`TrackingNumber` ailleurs dans le repo qui ne portent pas non plus le préfixe de leur aggregate porteur.
- Commands/Policies déjà renommés ci-dessus, renommés une seconde fois : `PlaceSubjectHold`→`PlaceSubjectErasureHold`, `LiftSubjectHold`→`LiftSubjectErasureHold`, Policies en cascade.
- `SubjectBuilder` : les modifiers wrappant une méthode d'aggregate verbe+objet s'inversent en objet+participe-passé, jamais verbe+é+objet — vérifié contre `PaymentBuilder`/`OrderBuilder`, les deux seuls précédents réels de ce même patron (`Payment::requestRefund()`→modifier `refundRequested()`, `Order::requestReturn()`→modifier `returnRequested()`). Confusion initiale : `IdentityBuilder`/`WithdrawalBuilder` n'ont que des méthodes à verbe nu (`suspend`, `erase`...), aucune ne porte d'objet, donc aucun des deux n'est un précédent valide pour ce cas. Corrigé : `heldBy()`→`erasureHoldPlaced()`, `liftedHold()`→`erasureHoldLifted()`, `requested()`→`erasureRequested()`, `cancelled()`→`erasureCancelled()`. `erase()`→`erased()` reste correct (verbe nu, aligné sur `Identity`/`Withdrawal`).

La colonne de projection `active_hold_count` reste inchangée — même raisonnement que `$activeHolds` : jamais lue hors du contexte de sa propre table déjà nommée `compliance_erasure_subject`.

### Correction (2026-09-07) : `ErasureHold` devient une Entity, pas un VO ; `SubjectBuilder` requalifié

Signalé par l'utilisateur : `$activeHolds` (`array<string, \DateTimeImmutable>`, keyé par `$reference->toString()`) fait perdre le typage — la clé est une VO stringifiée, la valeur un timestamp nu sans lien explicite avec sa référence. Un Hold a une identité propre (`reference`, unique) et un mini-cycle de vie (placé, puis potentiellement levé) — passe le test classique DDD Entity-vs-VO (continuité d'identité à travers le temps), contrairement à `OrderLine` (`Domain/ValueObject/OrderLine.php`), seul autre exemple de collection imbriquée du repo, mais figée une fois pour toutes à la création d'`Order` (jamais d'event `OrderLineAdded/Removed` propre), donc un vrai VO, pas un précédent valable ici.

Créé `Domain/ErasureHold.php` — Entity (pas VO : constructeur public, aucun invariant à protéger derrière une factory nommée), `reference`+`placedAt`. D'abord modélisé `list<ErasureHold>` + `array_any()`/`equals()` pour l'appartenance + `array_values(array_filter(...))` pour le retrait — **mutant survivant en CI** : retirer le `array_values()` n'a aucun effet observable (rien ne lit les clés/l'ordre de `$activeHolds`, seulement son `count()`), même piège déjà rencontré cette session sur `array_diff`/`array_values`. Corrigé en repassant à une vraie map keyée par `$reference->toString()` (`array<string, ErasureHold>`) — sémantique de set, `isset()`/`unset()` directs, aucune réindexation à faire donc aucun mutant de ce type possible. `ErasureHold` reste un objet réel comme valeur (au lieu du timestamp nu d'avant), gardant le bénéfice recherché (plus de VO stringifié comme clé) sans réintroduire une opération sans effet observable.

`SubjectBuilder` requalifié en cascade (vérifié contre `PaymentBuilder`, qui partage la même forme — cycle de vie propre de `Payment` + sous-concept `Refund` dans un seul tableau d'attributs plat : `refundRequested()`→`refundRequestedAt`/`refundFailed()`→`refundFailedAt` sont qualifiés, mais `refundConfirmed()`→`confirmedAt` ne l'est PAS — la règle n'est donc pas "toujours qualifier un sous-concept", mais "qualifier seulement quand la clé nue attribuerait le verbe au mauvais acteur") : `placedAt`/`liftedAt` (mal attribués — Subject ne se "place"/"lève" jamais, c'est un hold sur `reference`) → `erasureHoldPlacedAt`/`erasureHoldLiftedAt`. `requestedAt`/`cancelledAt` restent nus : Subject est bien le vrai acteur qui demande/annule sa propre effacement, aucune mauvaise attribution possible. La méthode d'aggregate et le field d'event correspondants restent nus dans les deux cas — déjà désambiguïsés par leur propre méthode/event englobant, même raisonnement que `Payment::requestRefund(..., \DateTimeImmutable $requestedAt)` qui reste nu malgré la clé qualifiée du Builder.

### Correction (2026-09-07) : `reference`/`erasureHoldPlacedAt`/`erasureHoldLiftedAt` n'ont rien à faire dans `SubjectBuilder::defaults()`

Correction ci-dessus insuffisante, signalée par l'utilisateur : grep exhaustif de `PaymentBuilder`/`OrderBuilder`/`WithdrawalBuilder` confirme bien que `defaults()` porte des clés au-delà des propriétés stockées de l'aggregate (`authorizedAt`/`capturedAt`... n'existent pas comme propriétés de `Payment`) — MAIS dans chacun de ces cas, la valeur, si elle était stockée, le serait DIRECTEMENT sur l'aggregate lui-même dans son propre `#[Apply]` (`$this->authorizedAt = ...`). `reference`/`placedAt`/`liftedAt` ne suivent PAS ce patron : dans `Subject::applyErasureHoldPlaced()`, ces valeurs sont assignées à une **Entity enfant** (`new ErasureHold($event->reference, $event->placedAt)`), jamais à `$this` directement. Le précédent Payment/Order/Withdrawal ne s'applique donc pas : aucun des trois n'a de collection d'Entity imbriquée, leur `defaults()` ne couvre que des paramètres de transition qui appartiennent conceptuellement à L'AGGREGATE lui-même, jamais à un sous-objet.

Retenu : `SubjectBuilder::defaults()` ne garde que les vrais paramètres de transition de `Subject` (`id`, `registeredAt`, `requestedAt`, `cancelledAt`, `activeHolds`, `erasedAt`) — plus de `reference`/`erasureHoldPlacedAt`/`erasureHoldLiftedAt`. `erasureHoldPlaced()`/`erasureHoldLifted()` construisent `ErasureHold` directement (référence/dates générées inline), exactement comme `SubjectId` échappe déjà à `defaults()` (`sample('id')` lève une exception dédiée — "l'id de l'aggregate racine ne se lit jamais via `sample()`, appeler sa propre factory nommée directement"). `SubjectTest.php` construit désormais `$this->reference`/`$this->placedAt`/`$this->liftedAt` directement de la même façon (plus de `SubjectBuilder::sample('reference')`), pour la même raison que `$this->id` le fait déjà.

Bug détecté au passage en écrivant `withActiveHolds()`/`erasureHoldPlaced()` : `AbstractAggregateBuilder::create()` réinitialise `$this->generatedAttributes = []` à chaque appel — résoudre un attribut PARESSEUX (comme l'ancien `reference` de `defaults()`) avant `create()` puis le relire APRÈS (`$builder['reference']` dans un test, après `->create()`) régénère une valeur fraîche différente, puisque le cache a été vidé entre les deux lectures. D'où les tests lisant désormais `array_first($builder['activeHolds'])->reference` — `activeHolds` est un attribut EXPLICITE (`withAttributes()`), jamais affecté par ce reset, contrairement à une valeur uniquement mise en cache par la résolution paresseuse de `defaults()`.

Corrigé aussi : `erasureHoldLifted()`'s `liftedAt` par défaut s'ancre sur `$hold->placedAt->modify('+2 days')` (le hold RÉELLEMENT concerné), jamais sur l'horloge globale indépendamment — un `placedAt` custom très éloigné du "now" du test ne peut alors jamais produire un `liftedAt` par défaut antérieur au placement.

### Note méthodologique
Une itération de cette conception a justifié un choix (`Order::HOLD_SOURCE_TYPE`) en citant qu'une convention de `.claude/rules/domain.md` "couvrait déjà ce cas" — erreur signalée en session : les rules sont extraites du code, pas une source de vérité théorique indépendante. Toute conclusion de ce document s'appuie sur la théorie DDD/CQRS/ES et la structure réelle du code vérifiée en session, jamais sur le texte d'une règle comme justification en soi.

**Récidive (2026-09-07), dans la section "Périmètre du showcase" elle-même** : au moment même d'écrire le principe "restriction de scope ≠ dégradation de la modélisation", il a été violé dans le même message — `Finance.Refund` et `OrderLine` présentés comme "questions ouvertes" au seul motif que ce showcase ne construira pas le remboursement partiel/l'amendement, exactement le travers que le principe interdit. Corrigé, tranché : les deux restent inchangés (voir sections dédiées). Renommage `Shipment`→`Delivery` proposé au même moment, justifié à tort par "laisser la place à un futur `Return`" — `return` est un mot-clé PHP réservé, cette classe n'aurait de toute façon jamais pu exister sous ce nom ; sans cette fausse prémisse, aucune raison réelle de renommer ne restait. Retiré. Signalé par l'utilisateur, pas détecté en session.

## Conception : `Sales.Order` / `OrderLine` / futur `Sales.Cart`

Déclenché par une question de l'utilisateur : `OrderLine` (`Domain/ValueObject/OrderLine.php`) est-il vraiment un VO, ou un faux VO qui simplifie un problème de conception non résolu ? Section reconstruite le 2026-09-07 après une perte accidentelle (édition concurrente ayant écrasé une version antérieure du fichier).

### `OrderLine` : Entity, sur le principe, pas sur l'usage actuel

Vérifié dans le code : `Order::place()` prend `list<OrderLine>` une fois, calcule `totalAmountInCents` par un simple fold, et **ne garde même pas `$lines` comme propriété de l'aggregate** — seul le total dérivé survit dans l'état. Aucune méthode `addLine()`/`removeLine()` n'existe. Première réponse (erronée) : `OrderLine` reste VO parce que rien ne le mute aujourd'hui. **Corrigé après contestation de l'utilisateur** : le test Entity-vs-VO d'Evans n'est jamais "est-ce mutable aujourd'hui" — c'est "le domaine a-t-il besoin de référencer CETTE occurrence précise, distincte d'une autre identique en valeur, à travers le temps ?". Deux lignes de même produit/quantité dans une commande, si un retour partiel par ligne existe un jour, doivent rester individuellement adressables — impossible sans identité propre. Classer Entity/VO sur "y a-t-il un appelant aujourd'hui" est la même erreur que celle déjà commise sur `Subject`/`register()` : juger la conception sur le code actuel plutôt que sur le concept.

Retenu : `OrderLine` mérite une identité locale (position/séquence dans la commande, pas nécessairement un UUID globalement significatif) — mais les **commandes** `AddOrderLine`/`RemoveOrderLine` restent volontairement non construites tant qu'aucun besoin réel ne les réclame (vrai YAGNI, sur la capacité, pas sur la donnée). `Order` doit conserver `list<OrderLine> $lines` comme véritable état — vérifié : `OrderPlaced` porte déjà `list<OrderLine> $lines` en permanence dans le store, donc ajouter la propriété et la peupler dans `applyPlaced()` ne demande aucun changement d'event, aucun upcaster.

### Le calcul du total : pas un Domain Service, la résolution du prix reste à la frontière Application

Question de l'utilisateur : le total devrait-il être calculé par un Domain Service, instancié par l'aggregate ? Nuancé : un Domain Service se justifie pour une opération ayant besoin de collaborateurs hors de ce que l'Entity/VO possède déjà (taxes, promotions, synchronisation catalogue) — pas pour sommer des `Money` déjà résolus. Le principe déjà validé sur `Subject` s'applique : un fait pouvant changer demain pour la même donnée (le prix catalogue) ne rentre jamais dans une transition, il se résout avant, à la frontière Application — ce que `PlaceOrderHandler` fait déjà en lisant `ListedProductFinderInterface` avant d'appeler `Order::place()`. Une fois les prix résolus/figés dans chaque `OrderLine`, sommer reste un calcul pur, sans collaborateur — pas de Domain Service nécessaire pour ça spécifiquement.

### `PriceIntegrityService` proposé puis rejeté : patcher un trou avec un autre trou

Constat initial : `PlaceOrderHandler::resolveLine()` compare le prix **soumis par le client** au prix catalogue courant, rejette (`OutdatedOrderException`) si ça diverge. Proposition initiale : extraire cette comparaison en Domain Service nommé (`PriceIntegrityService`). **Rejetée par l'utilisateur, à raison** : nommer/extraire la comparaison ne supprime pas le vrai trou — tant qu'un champ `unitPriceInCents` existe dans le contrat de `PlaceOrder` (vérifié : il y est), n'importe quel appelant peut relire le prix courant et le soumettre tel quel, validant trivialement n'importe quelle vérification côté domaine. Un service mieux nommé autour d'une entrée non fiable reste une entrée non fiable.

Vérifié dans `apps-pre-freeze` (`apps/web/src/Session/CatalogSnapshot.php`) : le vrai mécanisme existant capture le prix affiché en session à la visite ; le formulaire de checkout ne soumet que `productId`+`quantity`, jamais de prix — le DM Web synthétise lui-même le prix depuis la snapshot. Le check domaine actuel (submitted vs courant) protège donc contre la **péremption** d'un appelant honnête (le DM Web), pas contre la **fraude** d'un appelant qui contrôlerait directement le prix — et rien dans le domaine ne distingue les deux cas ; un futur DM API/CLI pourrait soumettre n'importe quel prix tant qu'il correspond au prix courant.

Deuxième proposition (rejetée aussi, par l'utilisateur) : supprimer le prix du contrat de `PlaceOrder`, résoudre inconditionnellement depuis le catalogue courant, déplacer l'alerte de péremption dans une Query non contraignante avant confirmation. Rejetée parce que "accepter silencieusement" prive `Order` de tout rôle de gardien sur sa propre invariant — la lecture soigneuse de la projection Catalog devient décorative si rien n'est jamais rejeté dessus.

### Retenu : `Sales.Cart`, nouvel aggregate, gardien réel de la fraîcheur

Un `Cart` (aggregate `Sales`, propriété du Buyer) résout le problème sans les deux trous précédents :
- porte le cycle de vie mutable (`addLine()`/`removeLine()`/`changeQuantity()`) — la mutabilité appartient réellement à la phase pré-achat, jamais après.
- se tient à jour en réagissant réellement au catalogue (Policy sur `ProductRepriced`/`ProductDelisted`, Integration Events de `Catalog.Listing`) — la fraîcheur devient un état maintenu en continu, pas un artefact de session d'un seul DM.
- `checkout()` est un vrai guard d'aggregate (même patron que `CanTransitionToSpecification` ailleurs dans ce repo) : refuse si une ligne est devenue invalide — pas une suggestion d'UI contournable.

`PlaceOrder` devient `PlaceOrder(cartId, buyerId)` — plus aucune ligne ni prix soumis. `Order::place()` se construit depuis l'état déjà validé du `Cart` au checkout.

### États de `Cart` : pas de "PÉRIMÉ"/"ABANDONNÉ" — même raisonnement déjà appliqué deux fois à `Withdrawal`/`Subject`

Question de l'utilisateur : `Cart` a-t-il besoin d'un état "périmé"/"abandonné", une durée de vie ? Non — un fait purement temporel sans conséquence distincte se calcule en direct à la lecture, jamais stocké comme transition d'aggregate, exactement le principe déjà énoncé dans `GOAL.md` pour `CanRequestWithdrawal` : *"Un check TTL/expiration se calcule en live à la lecture, jamais stocké — le matérialiser demanderait un mécanisme actif pour le recalculer périodiquement, on réintroduirait le job planifié qu'on a précisément éliminé."* Le test décisif : un panier périmé doit-il refuser quelque chose que `checkout()` (guard de fraîcheur des lignes) ne refuse pas déjà ? Non — l'âge du panier n'est pas le bon signal, la fraîcheur des lignes l'est déjà plus précisément.

`Cart` n'a donc que deux états réels : ouvert (mutable) → checkout (terminal, devient `Order`).

Hypothèse soulevée : un cron quotidien de relance email ("n'oublie pas ton panier") reste possible sans contredire ça, à condition de ne pas confondre "état" et "fait enregistré". Sans rien enregistrer, un cron interrogeant "paniers inactifs depuis 24h" toutes les 24h renverrait le même panier indéfiniment (spam) — pas un problème d'état du panier, un problème d'idempotence de l'action d'envoi. Solution dans le même patron déjà établi (`ListSubjectsDueForErasureHandler`) : le cron interroge en live "paniers ouverts, inactifs, jamais relancés (ou relancés il y a plus de X jours)", dispatch une commande par panier éligible, et si l'envoi réussit, `Cart` enregistre un fait horodaté (`Cart::remind()` → `CartAbandonmentReminderSent`, ou un simple `lastRemindedAt`) — jamais une transition d'état qui bloquerait `checkout()`, juste une donnée lue par la prochaine exécution du cron. Ce fait mérite sa place par le même test que `domain.md` applique déjà aux champs d'aggregate : un accès légitime (la requête du cron) suffit, pas besoin d'être une garde. Hors périmètre du showcase actuel — juste vérifié que la modélisation ne l'empêche pas.

### Réconciliation : `Cart` et l'identité d'`OrderLine` répondent à deux questions différentes, pas une seule

Point de friction relevé par l'utilisateur ("on tourne en rond") : `Cart` semblait annuler le besoin d'identité sur `OrderLine`. Distingué : `Cart`/`CartLine` répond à *avant l'achat* (fraîcheur, édition libre) ; l'identité sur `OrderLine` répond à *après l'achat* (un futur amendement SAV/retour partiel sur une commande déjà validée) — `Cart` a fini son rôle au moment du `checkout()`, il ne fournit rien pour l'après. Les deux se cumulent, aucune contradiction : `CartLine` (Entity, mutable librement) avant, `OrderLine` (Entity, identité conservée mais aucune commande de mutation construite) après.

### Chantiers ouverts, non résolus par ce qui précède — vérifié, "amender une commande payée" n'est PAS juste "ajouter une commande"

Question directe de l'utilisateur : la possibilité d'amendement reste-t-elle "juste extensible en ajoutant des commands plus tard" ? Non — vérifié, deux aggregates déjà connus comme sous-dimensionnés bloquent réellement, indépendamment de la forme de données `OrderLine` :

- **`Finance.Refund` ne supporte pas le remboursement partiel/multiple** — déjà noté dans l'analyse Payment/Refund plus haut : `pendingRefundId` singulier, `REFUNDING` exclusif sur `Payment`. Amender une ligne d'une commande payée implique de rembourser exactement le montant de cette ligne tout en gardant le reste capturé — aucun mécanisme actuel ne sait représenter "combien reste encore remboursable" ni gérer plusieurs remboursements partiels contre un seul paiement.
- **`Fulfilment.Shipping`/`Sales.Order` n'ont pas de granularité par ligne** — un seul `Shipment` par `Order` aujourd'hui (nuance : ce chantier concernait le retour, désormais hors périmètre — voir "Périmètre du showcase" en tête de document ; reste vrai pour un hypothétique amendement futur qui devrait scinder une expédition déjà partie).

Le fix `OrderLine`/`$lines` reste correct et nécessaire (il évite de fermer la porte au niveau de la donnée), mais il n'est pas suffisant — ces chantiers restent entiers, non commencés, à traiter le jour où l'amendement post-paiement devient un besoin réel plutôt qu'une possibilité conceptuelle à préserver.

## Décline pré-auth : le retry ne colle pas avec `Order::abort()` immédiat — question posée avec l'arrivée de `Cart`

Question posée par l'utilisateur en session : le decline (`PaymentGatewayStatus::DECLINED`, cf. `GOAL.md` Cycle 3) recouvre deux points distincts — réconciliation d'un paiement `REQUESTED` (`RequestedPaymentReconciler`, échec à l'auth) et échec de `capture()` après préparation (`CapturePaymentOnShipmentPrepared`, échec à la capture). Dans les deux cas, la seule policy consommatrice de `PaymentFailedIntegrationEvent` (`AbortOrderOnPaymentFailed`) annule immédiatement l'`Order` — aucune étape de nouvelle tentative sur la même commande, aucune Command `RetryPayment`.

Comparé à un tunnel de paiement usuel (Stripe Checkout, Amazon...) : un decline **pré-auth** (avant que l'`Order` soit même confirmé) garde d'ordinaire le panier vivant et propose de rejouer le paiement (autre carte) sur la même commande. Le decline **post-préparation** (à la capture, après engagement d'expédition) est un cas plus tardif et rare où annuler reste défendable.

Le repo place déjà `Order::place()` **avant** tout paiement (Cycle 1, `GOAL.md` ligne 97-98 : `Order::place()` puis seulement ensuite `Payment::request()`) — donc un decline pré-auth annule aujourd'hui un `Order` qui vient tout juste d'être créé, avant même sa confirmation. Avec `Sales.Cart` désormais retenu comme aggregate (section précédente), portant tout l'état mutable pré-achat, la question se repose différemment : **`Order::place()` doit-il continuer à intervenir avant la tentative de paiement, ou seulement après une autorisation réussie ?**

Si `Order` n'était créé qu'au moment où `Payment::authorize()` réussit (le `Cart` restant ouvert, `checkout()` non consommé pendant toute la tentative de paiement), un decline pré-auth n'aurait plus aucun `Order` à annuler : rejouer le paiement redeviendrait un aller-retour sur le même `Cart`, sans jamais toucher `Sales.Order`. `Order::abort()` ne resterait alors utile que pour le seul cas post-préparation (capture échouée après engagement d'expédition), qui reste un vrai abandon légitime — pas de retry attendu à ce stade.

**Pas tranché** : où placer exactement la frontière `Cart`→`Order` (dispatch de `RequestPayment` depuis le `Cart`, ou seulement à réception d'`AuthorizePayment`) change la forme du driving port paiement et du contrat `PlaceOrder`/`RequestPayment`. Aucune modification exécutée — analyse seule, à trancher par l'utilisateur.

## Code mort confirmé : `CancelOrdersOnBuyerErased`/`CancelOrphanedOrder*` — ancien système d'erasure, jamais retiré

Signalé par l'utilisateur, vérifié dans le code : `CancelOrdersOnBuyerErased` (`Sales.Order`) souscrit à `BuyerErasedIntegrationEvent` et annule tout de suite chaque `Order` encore annulable du buyer (fan-out `CancelOrphanedOrdersOfBuyer` → `CancelOrphanedOrder` par item, silencieux via `catch (OrderNotCancellableException)`). **`ApproveOrdersErasureOnBuyerErased` souscrit au même event**, en parallèle — c'est le mécanisme décrit dans `GOAL.md` (§ Cycle GDPR, ligne 254) : `Order` passe `APPROVED`, termine sa vie normalement (`DELIVERED`/`CANCELLED` naturel), puis `ERASED` — jamais annulé de force.

Les deux tournent aujourd'hui en même temps sur le même trigger, avec des effets contradictoires : la version "ancien système" annule immédiatement ce qui est encore annulable, avant même que la commande ait pu suivre son cycle normal. `GOAL.md` ne documente que la seconde (le modèle retenu) — la première (`CancelOrdersOnBuyerErased`, `CancelOrphanedOrdersOfBuyer`, `CancelOrphanedOrder` + leurs tests) est un reliquat de l'ancien design d'erasure (annulation active à la demande d'effacement), jamais retiré au moment de la refonte vers le modèle "fan-out sans cascade, item termine sa vie".

**Non lié** à la question du décline pré-auth ci-dessus, contrairement à l'hypothèse initiale de l'utilisateur (déplacer `Order::place()` après authorize n'aurait pas fait disparaître ce mécanisme) — un `Order` déjà confirmé/livré peut toujours voir son buyer effacé bien après tout paiement. Le nom "orphaned" prêtait à confusion avec un état "non payé" ; en réalité il désignait "orphelin de son buyer", pas "orphelin de paiement".

**À faire** : retirer `CancelOrdersOnBuyerErased`, `CancelOrphanedOrdersOfBuyer`(Handler), `CancelOrphanedOrder`(Handler) + tests associés — le modèle `ApproveOrdersErasureOnBuyerErased`/`ErasureState` couvre déjà le besoin proprement. Aucune modification exécutée ici — à porter dans `TODO.md` avant action.

## Autres paires examinées (pour mémoire, non tranchées ici)

- **`Finance.Payer` / `Sales.Buyer`** : même identité (`PayerId === BuyerId === IdentityId`), mais rétention légale distincte (Payer conservé plus longtemps que Buyer) qui justifierait la frontière — **non implémenté** : les deux s'effacent aujourd'hui sur le même `IdentityErasedIntegrationEvent`, via un mécanisme `Compliance.Erasure` (`Subject`/`Hold`) qui ne porte qu'une seule temporalité globale, pas deux calendriers de rétention distincts. Écarté du jugement de découpage actuel car le code du cycle d'effacement est en cours de refonte sur cette branche.
- **`Fulfilment.Shipping` (`ShipmentDirection`)** : constat initial correct pour le code tel qu'il est aujourd'hui (la state machine ne teste jamais `direction`) — mais voir la section "Correction" plus haut : dès que `PREPARED` est sauté pour le retour, `direction` cesse d'être une simple métadonnée et le split en deux aggregates devient justifié.
