# TODO

## Fulfilment.Shipping

- `ManifestDeniedException` (`src/Fulfilment/Shipping/Application/Manifest/Exception/ManifestDeniedException.php`) fusionne deux raisons de refus (`forCancelledShipment`/`forUncapturedPayment`) aux sémantiques de remédiation opposées pour un futur DM/ops : la première est définitive (rien à faire), la seconde est transitoire (retenter après capture). À séparer en deux types distincts une fois un vrai consommateur (DM) branché — actuellement personne ne catch (aucun DM, `apps/` vide), donc aucune preuve d'usage réel à ce stade.
- `Shipment.$buyerId` (`src/Fulfilment/Shipping/Domain/Shipment.php:57`) est de l'état mort sur l'aggregate : écrit une fois dans `applyRequested()`, jamais relu (aucun self-guard, aucune invariant sœur, aucun appel de port, aucun Publisher — les 5 Publishers de cycle de vie ne lisent que `->orderId`). Seul usage réel : `DbalShipmentFinder::byBuyer()` (Finder, pas l'aggregate). À retirer du champ aggregate — reste sur `ShipmentRequested`/le Read Model.
