# Refacto — frontières de Bounded Context

Document de travail : remise en question du découpage `src/*/*`. Ne pas confondre avec `GOAL.md` (référence as-built) ou `TODO.md` (backlog) — ce fichier ne contient que l'analyse de frontières, pas l'état des états/transitions.

## Critères d'évaluation d'une frontière de BC

Une frontière de BC se juge sur la théorie DDD/CQRS/ES, jamais sur la forme du code existant ou une convention déjà écrite. Quatre critères, à appliquer ensemble :

1. **Vocabulaire distinct** — le langage ubiquitaire d'un côté de la frontière a-t-il un sens que l'autre côté n'a aucune raison de porter (ex: "numéro de TVA" n'a aucun sens côté livraison) ? Si les deux côtés emploient les mêmes mots pour la même chose, ce n'est pas une frontière.
2. **Direction d'intégration acyclique** — le context map doit être orienté. Une dépendance dans les deux sens entre deux BC (A dépend de B et B dépend de A) signifie qu'aucun des deux ne peut évoluer ou se déployer indépendamment de l'autre : c'est un seul modèle coupé en deux, pas deux BC.
3. **Cadence et moteur de changement propres** — les deux côtés changent-ils pour des raisons différentes, portées par des parties prenantes différentes (ex: loi fiscale vs intégration technique d'un PSP) ? Un mimétisme d'état (un côté rejoue exactement le sous-cycle de l'autre) n'est pas un moteur de changement propre.
4. **Invariant et multiplicité réels** — le concept a-t-il une règle de cohérence qui n'existe nulle part ailleurs (ex: numérotation séquentielle légale, solde remboursable ≤ montant capturé), et/ou une multiplicité réelle (1-N) qui justifie sa propre identité et son propre stream ? Un aggregate qui ne fait que refléter l'état d'un autre sans invariant propre n'est pas un concept autonome.

Un split qui échoue sur le critère 2 (cycle) est disqualifié à lui seul, indépendamment des trois autres.

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
public static function register(SubjectId $id, \DateTimeImmutable $registeredAt): self   // né à l'inscription identité, pas à la demande d'effacement — état initial RETAINED
public function placeHold(HoldReference $reference, \DateTimeImmutable $placedAt): void
public function liftHold(HoldReference $reference, \DateTimeImmutable $liftedAt): void
public function requestErasure(\DateTimeImmutable $requestedAt): void                     // RETAINED -> ERASING
public function cancelErasure(\DateTimeImmutable $cancelledAt): void                       // ERASING -> RETAINED
public function release(\DateTimeImmutable $now): void                                     // ERASING -> ERASED
```

`release()` ne prend aucun collaborateur injecté — tout est déjà interne à l'aggregate :
```php
if ($this->state !== SubjectState::ERASING) return;
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

### Note méthodologique
Une itération de cette conception a justifié un choix (`Order::HOLD_SOURCE_TYPE`) en citant qu'une convention de `.claude/rules/domain.md` "couvrait déjà ce cas" — erreur signalée en session : les rules sont extraites du code, pas une source de vérité théorique indépendante. Toute conclusion de ce document s'appuie sur la théorie DDD/CQRS/ES et la structure réelle du code vérifiée en session, jamais sur le texte d'une règle comme justification en soi.

## Autres paires examinées (pour mémoire, non tranchées ici)

- **`Finance.Payer` / `Sales.Buyer`** : même identité (`PayerId === BuyerId === IdentityId`), mais rétention légale distincte (Payer conservé plus longtemps que Buyer) qui justifierait la frontière — **non implémenté** : les deux s'effacent aujourd'hui sur le même `IdentityErasedIntegrationEvent`, via un mécanisme `Compliance.Erasure` (`Subject`/`Hold`) qui ne porte qu'une seule temporalité globale, pas deux calendriers de rétention distincts. Écarté du jugement de découpage actuel car le code du cycle d'effacement est en cours de refonte sur cette branche.
- **`Fulfilment.Shipping` (`ShipmentDirection`)** : constat initial correct pour le code tel qu'il est aujourd'hui (la state machine ne teste jamais `direction`) — mais voir la section "Correction" plus haut : dès que `PREPARED` est sauté pour le retour, `direction` cesse d'être une simple métadonnée et le split en deux aggregates devient justifié.
