# Next

`Cart`/`CheckoutSession` redesign (PR #211) and the repo-wide id-VO/dead-code/JsonNormalizer
cleanup are done and merged/mergeable — nothing left to track from that work here. What remains:

Complète `.claude/TODO.md` (nommage des dossiers `Application/<Concept>/`, PSP/Carrier réalistes,
`Currency`/`Money`, audit dette technique globale) sans dupliquer.

## 1. Point ouvert, PAS tranché — `LineId` d'`Order.Line`

`ConfirmOrderHandler::resolveLine()` dérive `LineId::forProduct($cartId, $productId)` (stopgap
mécanique, pas un design validé) faute d'un `lineId` reçu de l'amont depuis que `Cart` n'en produit
plus. Question de fond, pas résolue : `LineId::forProduct()` est une dérivation `uuid5`, dont la
seule justification académique ailleurs dans ce repo (`PaymentId::forCheckoutSession`, etc.) est de
donner "au plus un X par parent" via le rejet du store sur un second stream au même id. `Line` n'est
PAS un aggregate root séparé — c'est une Entity à l'intérieur du stream d'`Order`, donc aucun store
séparé ne peut rejeter quoi que ce soit : la dérivation ne protège rien de réel ici. Par le même
raisonnement qui a fait conclure que `Cart` n'a plus besoin de `LineId` du tout (juste `productId`
brut suffit comme clé naturelle), `Order.Line` pourrait n'avoir besoin, lui non plus, d'aucun id
dérivé — à condition qu'un `Order` ne puisse pas légitimement porter 2 lignes pour le même produit
(à vérifier). À trancher dans une conversation dédiée à `Order`/`OrderLine` (cycle de vie
indépendant : préparation/expédition séparée, backorder partiel, litige, retour), pas en passant.

## 2. Point ouvert, PAS tranché — `CheckoutItem` VO pour `CheckoutSession`

Contrairement à `CartItem` (read-side, un `array{productId, quantity}` suffit, jamais de calcul),
`CheckoutItem` (productId+quantity+unitPrice) a une vraie justification théorique : `quantity ×
unitPrice` est un calcul réel à partir de 2 champs (`subtotal(): Money`), qui aura un vrai appelant
une fois `Currency`/taxe/frais de port en jeu (déjà au TODO) — pas aujourd'hui (`CheckoutSession::
open()` reçoit `totalAmountInCents` déjà précalculé). `subtotal()` resterait une méthode de VO
légitime (ne dépend que des 2 champs propres du VO) ; taxe/frais de port seront un Domain Service
séparé (dépendent d'une donnée externe). Pas tranché : est-ce que `CheckoutSession::open()` continue
de recevoir `totalAmountInCents` précalculé, ou somme lui-même les `subtotal()` de chaque
`CheckoutItem` une fois taxe/frais en place ? À reprendre dans une conversation dédiée à
`CheckoutSession`, ne pas construire `CheckoutItem` avant.

## 3. Petit constat

`Shared\Application\ErasureStatus` n'a aucun prédicat `is<Case>()` (contrairement à tout le reste du
repo) — `CheckoutSessionOpener` compare `ErasureStatus::REQUESTED === ...` en brut. Ajouter
`isRequested()`.

## 4. Limites d'environnement connues (pas des bugs)

- `castor qa:mutation` n'a jamais pu terminer localement (SIGTERM avant même de lancer les tests
  instrumentés PCOV) — fonctionne en CI. Vérifier via CI, ne pas compter sur le local.
- `castor qa:stan tests/<Bc>` échoue avec "Container ... KernelTestDebugContainer.xml does not
  exist" tant qu'aucun test n'a encore tourné dans cet environnement — lancer un test unitaire
  simple avant `qa:stan` sur `tests/` résout ça (cf. mémoire
  `project_phpstan_container_cache_warmup.md`).
