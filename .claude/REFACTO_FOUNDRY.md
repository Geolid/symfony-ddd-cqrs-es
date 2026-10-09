# REFACTO_FOUNDRY — état du chantier et plan

Document de reprise : ce qui est décidé, ce qui est fait (spike), ce qui reste, les pièges appris.
Source du code de référence : le spike, branche `ai/foundry-spike-storefront` (issue de `feat/storefront-signin-tests`, PR #254). Le spike est commité **localement** (non poussé) en commits thématiques WIP, non individuellement verts (voir `git log` : `chore(test): run tests on a real event store…`, `test(storefront): drop disableReboot…`, `refactor(iam): build Identity through factories…`, `test(iam): add credential factories and account Stories…`, puis restes du spike et ce document). `stash@{0}` (« foundry-spike: composer, phpunit.dist.xml, reference.php ») vient de `ai/foundry-spike-app`, dont le tip == `origin/main`. Le worktree `../symfony-ddd-cqrs-es-foundry` (branche `ai/foundry-spike`) est obsolète.

## 1. Décisions prises (par l'utilisateur)

1. **Foundry v2.14.1** (`zenstruck/foundry`, dev) pour factories, Stories, fixtures, faker. Sa couche de persistance (ORM, proxies, `repository()`, `assert()`) n'est pas utilisée.
2. **Une factory par Value Object, une factory par aggregate.** Jamais de provider faker qui retourne un VO (seul `CountryCodeFakerProvider`, qui retourne une chaîne ISO, subsiste).
3. **Stories** pour les scénarios nommés (E2E et tests src) afin d'alléger les Given tout en restant DAMP : clé de Story explicite, Stories empilables par dépendance. Un Given mono-aggregate reste en factories inline. `AccountBuilder` disparaît.
4. **Stores réels en test** : event store et clés de chiffrement en base ; **store de subscriptions en mémoire uniquement** (`static_in_memory`). Conséquences : plus de `disableReboot()`, second `activeBrowser()` possible, plus de remise à zéro des positions, `AggregateAlreadyExists` testable.
5. **Aggregates à état complet** : toutes les propriétés en `public private(set)`, un `#[Apply]` par champ d'événement. Les tests lisent `$agg->x` : plus de `WeakMap`, plus de `$builder['x']`. La décision reste dans l'aggregate ; l'Application ne lit un état que pour un appel de port ou un Integration Event ; l'affichage passe par un Finder.
6. **`sample()` supprimé** : un VO seul → `<Vo>Factory::new()->create()` ; une chaîne pour une Command → `->value` ou `faker()` direct ; une date → ancre `Clock::get()->now()` + `modify()` dans le test.
7. **`/demo`** ne garde que les Stories de démo (`#[AsFixture]`). **Toutes** les tâches castor `demo:*` sont supprimées au profit de `foundry:load-fixtures`. L'env `demo` ne sert plus qu'à isoler la base (`.env.demo`).
8. **Reset de base** : décorer `OrmResetter` (documenté par Foundry), pas de tag interne. Préférence exprimée : le remplacer complètement.
9. Chaque test de repository couvre aussi `AlreadyExists`, dans la PR de son BC.
10. Une passe de nettoyage vérifie règles, extensions PHPUnit, règles PHPat/deptrac, config de services.
11. Le coût de refacto n'est pas un critère.

## 2. Fait dans le spike (non commité)

**Infra / config**
- `composer.json/lock` : foundry `^2.14`. `config/bundles.php` : `ZenstruckFoundryBundle` en `dev`, `demo`, `test`.
- `config/packages/zenstruck_foundry.php` : faker (`FoundryFaker::create`), `EventSourcingResetter` décorant `OrmResetter`, Stories (`ShopperStory`, `BuilderShopperStory`, `tests/Iam/Support/Story/*Story.php`).
- `config/packages/patchlevel_event_sourcing.php` (bloc test) : plus de `store: in_memory` ; `subscription.store: static_in_memory` conservé. `config/services/shared.php` : `InMemoryCipherKeyStore` retiré.
- `phpunit.dist.xml` : `FoundryExtension` avec `enabled-auto-reset=true`. `tools/PHPUnit/EventSourcingExtension.php` vidée (les 3 subscribers de reset et `ResetState` ne sont plus enregistrés ; leurs fichiers existent encore).
- `tests/bootstrap.php` : `UnitTestConfig::configure(faker: FoundryFaker::create())`. `bin/console` : raccourci `-a` de `--app-id` retiré (conflit avec `foundry:load-fixtures -a|--append`). `.castor/demo.php` : tâche `demo:fixtures` ajoutée (à supprimer avec les autres au final).

**Socle de test** (`tests/Support/Foundry/`) : `AbstractAggregateFactory` (surcharge `create()` : horloge figée et monotone via `ClockSequence` ; `inputs()` par `WeakMap` ; `sample()` pont temporaire), `FoundryFaker`, `EventSourcingResetter` (séquence de `castor db:reset` sans `messenger` ; coupe les connexions statiques DAMA), `Story/AbstractAggregateStory` (persiste via `RepositoryManager`), `CustomerFactory`, `CartFactory` (spike).

**Iam**
- `tests/Iam/Identity/Support/Factory/` : `IdentityFactory` (16 attributs, 10 transitions, `with*()`), `IdentityIdFactory`, `FullNameFactory`, `EmailFactory`, `ReasonFactory` (`Instantiator::namedConstructor('fromString')`).
- `tests/Iam/Authentication/Support/Factory/` : `PasswordCredentialFactory`, `TotpCredentialFactory`, `BackupCodeCredentialFactory` (services fournis par la Story ; ids dérivés via `beforeInstantiate` et `??=`). Leurs VO ne passent pas encore par des factories de VO.
- `tests/Iam/Support/Story/` : `Account` (DTO), `AbstractAccountStory`, `UnconfirmedRegistrationStory`, `ConfirmedAccountStory`, `PasswordResetRequestedAccountStory`, `TwoFactorAccountStory`, `TwoFactorWithBackupCodesAccountStory`.
- `src/Iam/Identity/Domain/Identity.php` : 20 propriétés `public private(set)`.
- 48 fichiers portés d'`IdentityBuilder` vers `IdentityFactory` ; lectures d'Identity redirigées vers l'aggregate ; `PatchlevelIdentityRepositoryTest` : aller-retour comparant les 20 propriétés + `itThrowsWhenAlreadyExists` (vert).

**Storefront**
- `disableReboot()` retiré (`AbstractStorefrontTestCase::activeBrowser()`, `ShowTest`). `ChangeEmailTest::itRefusesResendWhenEmailAlreadyInUse` utilise un second navigateur. 4 tests TOTP : `interceptRedirects()` déplacé après la connexion. 5 tests utilisent une Story (`itChanges`, `itResets`, `itConfirms`, `itRefusesRevokeWithInvalidCsrfToken`, `itUnenrolls`). `AccountBuilder` porté vers `IdentityFactory` mais **toujours utilisé** (57 appels sur 62).

**Résultats au dernier run complet** : Iam 415 (+1 test AlreadyExists depuis), Crm 49, Sales 103, Finance 127, Fulfilment 136, Shopping 186, Catalog 48, Compliance 43, Shared 291, storefront 84 : tout vert.

## 3. Mesures et faits appris

- Store de subscriptions en base : `DbalIdentityFinderTest` passe de **1,8 s (en mémoire) à 6,3 s** (11 tests). Le premier run (27 s, et 2 min 47 s sur `tests/Iam`) n'est pas représentatif (démarrage à froid). Avec subscriptions en mémoire : `tests/Iam` 6,4 s.
- **castor** : `FOUNDRY_FAKER_SEED=<n> castor qa:test <chemin>` atteint le conteneur (contexte `test`).
- **Faker Foundry** : seed par test = `crc32(seed::testId)` ; un test isolé (`--filter`) rejoue exactement ses valeurs (vérifié en unitaire et en kernel). En unitaire, `UnitTestConfig::configure(faker: …)` ; en kernel, `zenstruck_foundry.faker.service`. `UnitTestConfig::build()` appelle `unique(true)` à chaque boot unitaire (le store d'unicité repart de zéro).
- **Foundry** : `defaults()` est évaluée à chaque `create()` (valeurs directes, pas de closures par clé) ; `with()` remplace ; une `Factory` imbriquée dans un attribut est créée automatiquement ; `afterInstantiate` reçoit les paramètres résolus ; `beforeInstantiate` sert aux clés dérivées (`$p['id'] ??= …`). `create()` est surchargeable (horloge figée).
- **Bundle** : il importe `orm.php` (resetter ORM sur la connexion `default`) dès que `DoctrineBundle` est présent, même sans ORM ; d'où la décoration d'`OrmResetter`.
- **Stories** : cache vidé à chaque test (`Configuration::shutdown()` → `StoryRegistry::reset()`) ; `#[WithStory]` exige une `KernelTestCase` ; une Story avec dépendances échoue hors kernel ; pas de paramètre à l'appel (une classe = un scénario) ; `Story::__callStatic` rend un état nommé (`Story::email()`).
- **FoundryExtension `enabled-auto-reset`** : un reset par process, sur le kernel de la première `KernelTestCase` ; `exit-on-reset-database-failure` vaut `true` par défaut. La base `messenger` n'a pas de suffixe par worker (`doctrine.php`) et n'est pas utilisée (transport `sync://`) : exclue du resetter.
- **WebTestCase** : chaque `browser()` fait `ensureKernelShutdown()` puis `createClient()` (noyau et session neufs). Avec event store réel, l'état persiste d'un noyau à l'autre (DAMA).
- **Dev** : les groupes Policy et Processor tournent ; une inscription envoie un mail via `mailer:1025`. Le service `mailer` doit être lancé avec `compose.override.yaml` pour publier `8025`.
- **PR #254 avant le spike** : 5 tests storefront échouaient déjà (4 TOTP : `interceptRedirects()` placé avant `signInAs()` ; `ChangeEmail` : parcours à deux acteurs dans une seule session). À corriger dans la PR #254 elle-même.
- Le hook du repo bloque `rm -rf`, `git worktree remove --force`, `git branch -D`.

## 4. Plan de PRs (dans l'ordre, une marche à la fois)

**PR socle (une seule, contre `main`)** : PR #254 (`feat/storefront-signin-tests`) + le spike nettoyé, poussé depuis `ai/foundry-spike-storefront`. On attend la CI verte avant la suite. Contenu (étapes 0 à 3, déjà faites dans le spike) :
- 0. 4 tests TOTP corrigés (`interceptRedirects()` après la connexion et le défi), dans le spike et non dans l'ancêtre.
- 1. Stores réels en test : event store et clés en base, subscriptions statiques en mémoire, `disableReboot()` retiré, test `ChangeEmail` à deux navigateurs.
- 2. Fondations Foundry : composer, bundle, `FoundryExtension`, `EventSourcingResetter`, faker, `bin/console` sans `-a`, `AbstractAggregateFactory`, `AbstractAggregateStory`.
- 3. Tranche Identity : factories de VO + `IdentityFactory`, état complet, aller-retour + `AlreadyExists`, `IdentityBuilder` supprimé.
- Avant de pousser : retirer les restes du spike (§6), `castor qa` vert (lancé par l'utilisateur), commits recomposés pour passer chacun.

**Avancement des PRs par BC** (une par ligne, mise à jour à chaque étape) :
- Iam.Authentication : fait sur `ai/foundry-iam-authentication` (état complet, factories de VO + `ApiKeyCredential`/`TrustedDevice`, aller-retour + `AlreadyExists`, `*PiiErasureTest` via le store réel, 5 Builders supprimés, `AccountBuilder` porté sur les factories de credentials).
- Crm : fait sur `ai/foundry-crm` (état complet de `Customer`, `AddressFactory`/`PostalAddressFactory` dans `tests/Shared/Support/Factory/`, `CustomerFactory`, nouveau `PatchlevelCustomerRepositoryTest` avec `AlreadyExists`, `CustomerPiiErasureTest` via `storedEventOf()`, `CustomerBuilder` supprimé). Les usages de `CustomerBuilder` dans Shopping sont portés dans cette PR.
- Reste : Shopping, Sales, Finance, Fulfilment, Catalog, Compliance.

**Ensuite : une PR par BC, empilées chacune sur la précédente** (une fois la PR socle mergée, la première se rebase sur `main`) :
4. Iam.Authentication (credentials : factories de VO), Crm, Shopping, Sales, Finance, Fulfilment, Catalog, Compliance. Chaque PR : factories de VO et d'aggregate, état complet, test `AlreadyExists` du repository, suppression des Builders du BC. En dernier : `AbstractAggregateBuilder`, `SeededFaker`, `FakerSeedExtension`, les 3 subscribers de reset + `ResetState` + `EventSourcingExtension` + `ThrowawayKernelHelper`.
5. Stories de compte + storefront : porter les usages restants d'`account()` (11 combinaisons : 5 Stories couvrent 48 appels sur 62 ; le reste en factories inline), supprimer `AccountBuilder`.
6. Démo : Stories de démo dans `/demo` (`#[AsFixture]`), suppression des tâches castor `demo:*`, de `demo/SeedCommand.php`, `demo/console`, `demo/seeds.php` ; `config/services.php` charge les Stories en env `demo`.
7. Nettoyage final : `castor qa:static` d'abord, puis règles (`tests.md`, `dm.md`, `domain.md:35`, `infrastructure.md`, `demo.md`, `CLAUDE.md`), règles PHPat/deptrac (calque `demo/.*`), config de services, extensions PHPUnit restantes.

À chaque PR (socle comprise), autorisation de l'utilisateur : mettre à jour les règles concernées dans la même passe ; **un seul `castor qa` avant de pousser** ; pousser et ouvrir la PR (empilée sur la précédente) ; **attendre la CI verte** avant d'ouvrir la suivante et de passer au BC suivant. Jamais deux `castor qa` en parallèle.

## 5. Où vit quoi

- Factories (aggregate et VO) : `tests/<Subdomain>/<Bc>/Support/Factory/`.
- Socle commun (`AbstractAggregateFactory`, `AbstractAggregateStory`, `FoundryFaker`, resetter) : `tests/Support/Foundry/`.
- Stories de sous-domaine : `tests/<Subdomain>/Support/Story/` (ex. `tests/Iam/Support/Story/`).
- Stories propres à un DM : `apps/<dm>/tests/Support/Story/`, promues au sous-domaine dès qu'un second DM les utilise.
- Stories de démo : `/demo` (`#[AsFixture]`), qui composent les factories de `tests/`.

## 6. Points ouverts / non vérifié

- **castor et l'environnement** : `qa:test` tourne sous le contexte `test` (`castor.php`) ; `APP_ENV`, `FAKER_SEED` et `FOUNDRY_FAKER_SEED` posés en ligne atteignent le conteneur (voir §3).
- **Playwright** : `castor debug:test` s'est arrêté sur un `TimeoutException` (le clic sur « submit » ne faisait rien) ; cause non élucidée.
- `castor qa:static` (PHPStan, deptrac, PHPat, CS) et `qa:mutation` (Infection, min-msi 100) **jamais lancés** sur le nouveau code ; le typage `@phpstan-type Inputs` n'est pas vérifié.
- Calque deptrac `demo/.*` (`deptrac_bc.yaml:60`) : autorise-t-il une dépendance vers `tests/` ?
- DTO `Account` des Stories : remplacer par des états scalaires lus par `__callStatic` (`TwoFactorAccountStory::email()`) avec `@method static` ? Proposé, non tranché.
- `EventSourcingResetter` : décoration sans appel de l'interne ; remplacement complet à décider.
- Subscriptions en mémoire : passent dans tous les runs faits, non prouvé dans tous les ordres d'exécution.
- Le test d'aller-retour du repository détecte la sérialisation, pas un `#[Apply]` oublié.
- `composer.lock` : `symfony/error-handler` est passé de v8.1.5 à v8.1.8 pendant l'installation.
- Domaines autres qu'Identity : état complet non essayé. Playwright, `cron` et `es-dashboard` non testés avec les stores réels.
- **Tests `*PiiErasureTest`** : la sérialisation manuelle (`serializedEventOf()` + `deserialize()`) existait parce que l'event store en mémoire ne chiffrait rien. Avec le store et les clés en base, le test se réduit à : sauver, `removeWithSubjectId()`, relire (repository pour l'état d'un aggregate, `storedEventOf()` de `EventSourcingTrait` pour un événement, d'intégration compris). Fait pour Identity, `ApiKeyCredential`, Customer ; à faire dans la PR de chaque BC (Shopping, Sales, Fulfilment), puis supprimer `serializedEventOf()` de `EventSourcingTrait` s'il n'a plus d'appelant.
- Restes du spike retirés (`tests/Iam/Identity/Spike/*`, `ShopperStory`, `BuilderShopperStory`, `CustomerFactory`, `CartFactory`, `demo:fixtures`). Reste : worktree `ai/foundry-spike`, stash `foundry-spike`.

## 7. Reprendre

- Branche : `ai/foundry-spike-storefront`. Tests : `castor qa:test <chemin>` (jamais de `castor sh` pour vérifier). Démo : `castor demo:fixtures` (env `demo`, base `demo`).
- Seed reproductible : `FOUNDRY_FAKER_SEED=<n> castor qa:test <chemin>`.

## 8. Raisonnements consignés (ne pas les rediscuter)

**Aggregate à état complet**
- Motif : une seule règle (tout l'état est lisible) remplace « cette prop mérite-t-elle sa place ? » (`domain.md:35`), supprime le `WeakMap` et les `$builder['x']`, et rend les tests de repository plus riches.
- Pas une transgression DDD/CQRS : DDD n'interdit pas d'exposer des attributs ; Tell, Don't Ask vise les décisions prises hors de l'objet ; CQRS sépare les modèles de lecture et d'écriture, et lire côté commande (tests, construction d'Integration Event) ne sert aucune requête ; l'état minimal en ES (le `Decider`) est une recommandation de simplicité, pas de correction. Écart reconnu : un smell faible (facilite la lecture-pour-décider). *Citations de mémoire, non vérifiées : les exemples d'IDDD (Vernon) avec accesseurs publics et setters privés ; le `expectState` de la fixture Axon.*
- Seul risque résiduel : l'Application d'un même BC lit un état pour décider (l'inter-BC est déjà bloqué par deptrac/PHPat). Garde-fou existant : `application.md`, NEVER « pré-contrôler une condition que la transition garde ». **Règle d'usage** : état lisible en `public private(set)` ; la décision reste dans l'aggregate ; l'Application ne lit un état que pour un appel de port sortant ou la construction d'un Integration Event ; l'affichage passe par un Finder. Les cas légitimes sont dans la liste suivante.
- Pas de règle PHPat interdisant à l'Application de dépendre des `*State` : `CancelErasureHandler` lit légitimement `$erasure->state` (faux positif).
- RGPD : non sujet (clé détruite, valeurs de repli gérées) — décision de l'utilisateur.
- L'aller-retour du repository détecte la sérialisation, pas un `#[Apply]` oublié (les deux côtés passent par les mêmes `#[Apply]`).

**Lectures d'état légitimes dans l'Application (références)**
- `CancelErasureHandler` : `if ($erasure->state->isCancelled()) { uniqueness->release(...) }` est une décision applicative (effet sur le registre d'unicité), pas une décision de l'aggregate. C'est un postcondition volontaire : `cancel()` est idempotente (no-op si déjà annulée), donc la libération est rejouable ; une Policy sur `ErasureCancelled` ne se déclencherait qu'à la première transition.
- `ShipmentManifester` : lit un **Result de Finder** (pas l'aggregate) et refuse un colis annulé pour ne pas appeler le transporteur. Résoudre → décider → appeler → `dispatch` est la forme prescrite par `application.md` ; l'aggregate ne peut pas protéger un appel qui le précède.

**Stories vs Givens inline**
- Fixtures partagées entre tests : écartées (Mystery Guest ; revue Q16 ; `paratest`). Admis : une Story est un scénario nommé, **reconstruite à chaque test** (`Configuration::shutdown()` → `StoryRegistry::reset()`), donc fraîche ; les lignes sont annulées par DAMA.
- Limites : une Story n'a pas de paramètre d'appel (une variation = une classe) → Stories empilables par dépendance pour les cas fréquents (5 Stories couvrent 48 des 62 appels `account()`), factories inline pour les autres ; une Story qui encode plus que son nom devient un Mystery Guest ; Object Mother vs Test Data Builder (Pryce).
- Un Given mono-aggregate reste inline : une ligne énonce déjà la condition.
- `AccountBuilder` disparaît : il avait été écrit pour remplacer des Stories sans Foundry.

**VO et faker** : une factory par VO ; un provider seulement pour une primitive. Un VO imbriqué dans `defaults()` est créé par Foundry avant l'instanciation.

**Stores réels** : event store et clés en base ; subscriptions en mémoire, parce que la version en base coûte cher (6,3 s contre 1,8 s sur un fichier) sans apporter ce qui est cherché. Gains : `disableReboot()` inutile (chaque `browser()` recrée le noyau ; l'état persiste grâce à DAMA), second navigateur, positions de subscriptions non remises à zéro (l'auto-increment InnoDB ne recule pas au rollback — non prouvé dans tous les ordres), `AggregateAlreadyExists` testable (vérifié sur Identity). Playwright, selon l'utilisateur, partage le process du `KernelBrowser` : DAMA s'applique donc, mais ce mode n'a pas été validé.

**`sample()`** : n'était qu'un raccourci vers le faker ; remplacé par une factory de VO, `faker()` direct, ou une ancre d'horloge + `modify()`. Les offsets cachés des `defaults()` (`+1 hour`, …) deviennent explicites dans les tests de cooldown. Conséquence : réviser la règle de `tests.md` qui interdit un `Clock::get()->now()` brut dans un `setUp()` de test Domain.

**Resetter** : `OrmResetter` décoré (documenté par Foundry), sans tag interne ; sans appel à l'interne, c'est de fait un remplacement. `messenger` exclu (transport `sync://`, rien n'y est lu ni écrit ; la base n'est pas suffixée par worker, donc le `drop` y fait une course entre workers).

**Démo** : l'env `demo` reste pour isoler la base ; Stories de démo dans `/demo` ; Stories de test dans `tests/` ; `foundry:load-fixtures` ne liste que les `#[AsFixture]`.

**Précision de vocabulaire** : le `create()` surchargé, le `WeakMap` et la décoration du resetter sont des extensions prévues de Foundry, pas des contournements (correction de l'utilisateur).
