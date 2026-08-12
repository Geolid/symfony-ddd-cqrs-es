# `store()` : create() + persist, sur les Test Factory

## Contexte

Suite du refactor précédent (single exit point `create()` sur
`AbstractAggregateTestFactory` / `AggregateCollectionTestFactory`, déjà en place). Le
pattern `$x = XTestFactory::new()->create(); $this->store($x);` revient partout dans les
tests d'intégration (repéré: `create()` apparaît ~244 fois, `$this->store(` ~233 fois).
But: un `store()` sur la factory qui fait `create()` + persist en un seul appel, à la
Laravel/Zenstruck Foundry — `$x = XTestFactory::new()->store();`.

Décisions actées dans la conversation:
- Nom retenu: `store()` (pas `make()`/`create()` inversés) — `create()` garde son sens
  actuel inchangé (build seul, aligné avec le vocabulaire domaine `Shipment::create()`,
  zéro I/O). `store()` est additif, et porte le même nom que le `store()` du trait qu'il
  remplace à l'usage — zéro renommage.
- La factory accepte de se coupler au container (validé explicitement par l'utilisateur),
  alors qu'elle en était indépendante jusqu'ici.
- `EventSourcingTrait::store()` (trait, `$this->store(...$aggregates)`) **reste** — il
  est nécessaire pour le pattern "re-store" (mutation directe sur l'aggregate puis
  persist de l'event suivant), présent dans 42 fichiers, ex.
  `tests/Iam/Identity/Infrastructure/Persistence/Projection/Reducer/IdentityStatusReducerTest.php:51-59`
  (`create()+store()`, puis `->reactivate()`, puis un 2e `store()`). Ce `store()` de
  factory ne couvre que le 1er persist, jamais un re-persist après mutation manuelle.
- Les 8 fichiers `*RepositoryTest.php` (ex.
  `tests/Catalog/Product/Infrastructure/Persistence/EventStore/Repository/ProductRepositoryTest.php`)
  gardent `create()` seul — leur `// When` teste `$this->repository->save()`
  explicitement; auto-persister dans `create()`/`store()` viderait ce `When`.

## Design retenu — accès container depuis la factory

`AbstractAggregateTestFactory` (namespace `Shared\Tests\Support\Factory`) n'a aujourd'hui
aucune dépendance Symfony/container. Pour persister, elle doit atteindre
`RepositoryManager` via le container de test — mais `KernelTestCase::getContainer()` est
`protected static`, donc inappelable depuis une classe extérieure à la hiérarchie.

Le repo a déjà l'outil pour ça: `tests/Support/PHPUnit/KernelTestCaseHelper.php` (vendored
depuis zenstruck/foundry, `@internal`, déjà utilisé par
`tests/Support/PHPUnit/EventSourcing/ResetStateOnPreparationStarted.php`). Il expose
`KernelTestCaseHelper::getContainer(class-string $class): Container` via `Closure::bind`
sur la classe donnée.

Point clé vérifié dans le vendor Symfony (`KernelTestCase`): `$kernel`/`$booted`/`$class`
sont des propriétés statiques déclarées **une seule fois** dans `KernelTestCase` et
jamais redéclarées par aucune sous-classe — vérifié explicitement dans le vendor pour
`WebTestCase` et `ApiPlatform\...\ApiTestCase` (celles utilisées par les DM), en plus de
`Support\AbstractIntegrationTestCase`. Donc un seul slot statique partagé par **toute**
la hiérarchie `KernelTestCase`, quel que soit le DM (api/cli/web/webhook) ou la BC — peu
importe quelle classe de cette hiérarchie on utilise pour y accéder, on lit/écrit le même
stockage physique.

Vu ça, pas besoin de passer par une classe concrète du projet (`AbstractIntegrationTestCase`
ou autre) — on cible directement `\Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class`
elle-même, la vraie source de vérité de ce mécanisme, plus honnête qu'un hardcode d'une
classe métier sans rapport apparent. Seul ajustement nécessaire: le guard actuel de
`KernelTestCaseHelper` utilise `is_subclass_of($class, KernelTestCase::class)`, qui
renvoie `false` pour `KernelTestCase::class` lui-même (une classe n'est jamais sa propre
sous-classe) — à remplacer par `is_a($class, KernelTestCase::class, true)` (inclut
l'égalité) dans ses 3 méthodes (`getContainer()`, `bootKernel()`, `ensureKernelShutdown()`),
pour que la classe de base elle-même soit un `$class` valide.

**Déplacement du helper.** `KernelTestCaseHelper` vit aujourd'hui sous
`tests/Support/PHPUnit/` (namespace `Support\PHPUnit`), un dossier réservé au mécanisme
d'extension PHPUnit (subscribers d'événements, bootstrap — `AggregateFactoryExtension`,
`ResetStateOnPreparationStarted`). On va s'en servir depuis du code de test normal
(`AbstractAggregateTestFactory::store()`, appelé dans le corps d'une méthode de test, pas
dans un subscriber) — autre contexte d'usage. Il déménage vers
`tests/Support/Helpers/KernelTestCaseHelper.php` (namespace `Support\Helpers`), à côté de
`EventSourcingTrait`/`ServiceLocatorTrait`/`CqrsTrait`. Met à jour le seul consommateur
existant, `tests/Support/PHPUnit/EventSourcing/ResetStateOnPreparationStarted.php`
(juste le `use`).

**Pas de nouveau risque d'ordre de boot.** `.claude/rules/tests.md` documente déjà le cas
d'un test Web dont `setUp()` toucherait le container avant que le test appelle lui-même
`self::browser()`/`createClient()` (`WebTestCase::createClient()` lève une
`LogicException` si le kernel est déjà booté). `store()` boote le kernel exactement au
même moment que le `store()` du trait aujourd'hui (paresseux, au premier accès
container) — la migration ne change *pas* le timing, elle change juste l'écriture.
Vérifié sur les helpers DM existants qui font déjà `create()` + `$this->store()`:
- `apps/web/tests/Support/AbstractWebTestCase.php::loginAs()` — reçoit `$client` déjà
  créé par l'appelant (`self::browser()` déjà appelé avant), donc le kernel est déjà
  booté avant le `store()`.
- `apps/api/tests/Support/AbstractApiTestCase.php::authenticatedClient()` (et
  `invalidApiKeyClient()`/`revokedApiKeyClient()`/`expiredApiKeyClient()`) — `store()`
  puis `createClient()` juste après; `ApiTestCase::createClient()` ne lève pas si déjà
  booté (`$alwaysBootKernel = false` dans `AbstractApiTestCase`), donc pas de risque même
  dans cet ordre.

`AbstractAggregateTestFactory::store()`:
```php
public function store(): AggregateRoot
{
    $aggregate = $this->create();

    // Kernel/container statics live on KernelTestCase itself, never redeclared by any
    // subclass — this reaches whichever one the running test already booted.
    $manager = KernelTestCaseHelper::getContainer(KernelTestCase::class)
        ->get(RepositoryManager::class);
    \assert($manager instanceof RepositoryManager);

    $manager->get($aggregate::class)->save($aggregate);

    return $aggregate;
}
```
(3 lignes de persist, dupliquées avec `EventSourcingTrait::store()` — volontairement pas
extrait dans un helper partagé, cf. règle du repo "3 lignes similaires > abstraction
prématurée"; les deux call sites ont un contexte d'accès container différent.)

`AggregateCollectionTestFactory::store(): array` (miroir de `create()`):
```php
public function store(): array
{
    return array_map(fn () => $this->factory->store(), range(1, $this->count));
}
```

## Fichiers à modifier

1. `tests/Support/PHPUnit/KernelTestCaseHelper.php` → déplacé vers
   `tests/Support/Helpers/KernelTestCaseHelper.php` (namespace `Support\PHPUnit` →
   `Support\Helpers`), guard `is_subclass_of` → `is_a(..., true)` dans ses 3 méthodes.
2. `tests/Support/PHPUnit/EventSourcing/ResetStateOnPreparationStarted.php` — met à jour
   son `use Support\PHPUnit\KernelTestCaseHelper;` vers le nouveau namespace.
3. `tests/Shared/Support/Factory/AbstractAggregateTestFactory.php` — ajoute `store()`
   (import `RepositoryManager`, `Support\Helpers\KernelTestCaseHelper`,
   `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`). `create()` inchangé.
4. `tests/Shared/Support/Factory/AggregateCollectionTestFactory.php` — ajoute `store()`.
5. Sweep sur les tests d'intégration existants **et** les helpers partagés des DM (voir
   règle de migration ci-dessous) — notamment:
   - `apps/web/tests/Support/AbstractWebTestCase.php::loginAs()`
   - `apps/api/tests/Support/AbstractApiTestCase.php::authenticatedClient()`,
     `invalidApiKeyClient()`, `revokedApiKeyClient()`, `expiredApiKeyClient()`
   - tous les `*Test.php` sous `tests/` et `apps/*/tests/` qui suivent le pattern.
6. `.claude/rules/tests.md`, section `### Test Factory` — documente `store()` à côté de
   la puce `create()` déjà présente.

## Règle de migration (sweep)

**Migrer** un site où `create()` est suivi **immédiatement** (sans mutation entre les
deux) d'un persist, sous une de ces formes:
- `$x = XTestFactory::new()...->create(); $this->store($x);` → `$x = XTestFactory::new()...->store();`
- `$this->store(XTestFactory::new()...->create())` → `XTestFactory::new()...->store();`
- `$this->store(...XTestFactory::new()...->many(n)->create())` → `XTestFactory::new()...->many(n)->store()`
  (assigné à une variable si le résultat est réutilisé plus loin)
- Un `$this->store($a, $b, $c)` groupant plusieurs aggregates fraîchement créés sur les
  lignes juste au-dessus → éclate en `->store()` individuels, la ligne `$this->store(...)`
  de regroupement disparaît.

**Ne jamais toucher:**
- Les 8 `*RepositoryTest.php` (`create()` + `->save()`/`->store()` explicite = comportement testé).
- Tout `$this->store($var)` qui n'est **pas** immédiatement précédé de la construction de
  `$var` (re-store après mutation manuelle, ex. `IdentityStatusReducerTest.php` — 42
  fichiers recensés) — reste tel quel, sur le trait.
- Tout `create()` jamais passé à `store()` (aggregate construit sans être persisté).
- `tests/Fulfilment/Shipment/Support/Factory/ShipmentTestFactory.php` reste tel quel (WIP
  existant hors-scope, cf. décision précédente) — ses call sites migrent quand même la
  syntaxe `create()`→`store()` si le pattern correspond, sans chercher à corriger le bug
  WIP sous-jacent.

Vu le volume (79 fichiers utilisent une `*TestFactory`, ~233 `$this->store(`), le sweep se
fait fichier par fichier avec vérification visuelle du contexte (pas de remplacement
regex à l'aveugle), pour ne pas confondre un premier store d'un re-store.

## Vérification

- `make stan.tests` et `make stan.<dm>` (api/cli/web/webhook) — les 2 nouvelles méthodes
  et les sites migrés dans les DM passent PHPStan niveau 9.
- `make test filter=<Bc>` par sous-domaine (Iam, Sales, Fulfilment, Catalog) au fur et à
  mesure du sweep sur `tests/`, puis `make test suite=<dm>` pour chaque DM touché
  (api/web) pour valider `AbstractWebTestCase`/`AbstractApiTestCase`, puis `make test`
  complet à la fin pour confirmer l'absence de régression sur l'ensemble.
