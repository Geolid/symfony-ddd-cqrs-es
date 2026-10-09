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
- `tests/bootstrap.php` : `UnitTestConfig::configure(faker: FoundryFaker::create())`. `bin/console` : raccourci `-a` de `--appId` retiré (conflit avec `foundry:load-fixtures -a|--append`). `.castor/demo.php` : tâche `demo:fixtures` ajoutée (à supprimer avec les autres au final).

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
- **castor** : `qa:test` s'exécute avec `new Context()` et `compose_exec()` ne transmet via `-e` que `context()->environment` (`APP_ENV`, `APP_DEBUG`). Une variable inline (`FOO=1 castor qa:test`) n'atteint **pas** le conteneur (mesuré). **L'utilisateur juge ce comportement anormal : à investiguer** (cause non identifiée ; il ne s'agit pas d'une règle à retenir). Contournement utilisé : `.env.test.local` (gitignoré) avec `FOUNDRY_FAKER_SEED=42`.
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

0. **PR #254** : corriger les 4 tests TOTP (`interceptRedirects()` après connexion et défi). Le test `ChangeEmail` à deux navigateurs attend l'étape 1.
1. **Stores réels en test** (sans Foundry si possible) : event store + clés en base, subscriptions statiques en mémoire, retrait de `disableReboot()`, test `ChangeEmail`. *À vérifier d'abord* : les 3 subscribers de reset existants suffisent-ils, ou faut-il déjà le resetter ?
2. **Fondations Foundry** : composer, bundle, `FoundryExtension` (auto-reset), `EventSourcingResetter`, faker unique, `bin/console` sans `-a`, `AbstractAggregateFactory`, `AbstractAggregateStory`. Retrait des 3 subscribers + `ResetState` + `EventSourcingExtension` + `ThrowawayKernelHelper`.
3. **Tranche Identity** : factories de VO + `IdentityFactory`, état complet, aller-retour du repository + `AlreadyExists`, suppression d'`IdentityBuilder` (48 fichiers).
4. **Un BC par PR** : credentials (Iam.Authentication), Crm, Shopping, Sales, Finance, Fulfilment, Catalog, Compliance. Chaque PR : factories de VO et d'aggregate, état complet, test `AlreadyExists` du repository, suppression des Builders du BC. En dernier : `AbstractAggregateBuilder`, `SeededFaker`, `FakerSeedExtension`.
5. **Stories de compte + storefront** : porter les usages restants d'`account()` (11 combinaisons : 5 Stories couvrent 48 appels sur 62 ; le reste en factories inline), supprimer `AccountBuilder`.
6. **Démo** : Stories de démo dans `/demo` (`#[AsFixture]`), suppression des tâches castor `demo:*`, de `demo/SeedCommand.php`, `demo/console`, `demo/seeds.php` ; `config/services.php` charge les Stories en env `demo`.
7. **Nettoyage final** : lancer `castor qa:static` d'abord, puis règles (`tests.md`, `dm.md`, `domain.md:35`, `infrastructure.md`, `demo.md`, `CLAUDE.md`), règles PHPat/deptrac (calque `demo/.*`), config de services, extensions PHPUnit restantes.

À chaque PR : mettre à jour les règles concernées dans la même passe ; ne pas lancer `castor qa` à ma place, donner la commande.

## 5. Où vit quoi

- Factories (aggregate et VO) : `tests/<Subdomain>/<Bc>/Support/Factory/`.
- Socle commun (`AbstractAggregateFactory`, `AbstractAggregateStory`, `FoundryFaker`, resetter) : `tests/Support/Foundry/`.
- Stories de sous-domaine : `tests/<Subdomain>/Support/Story/` (ex. `tests/Iam/Support/Story/`).
- Stories propres à un DM : `apps/<dm>/tests/Support/Story/`, promues au sous-domaine dès qu'un second DM les utilise.
- Stories de démo : `/demo` (`#[AsFixture]`), qui composent les factories de `tests/`.

## 6. Points ouverts / non vérifié

- **castor et l'environnement** : une variable inline n'atteint pas le conteneur depuis `qa:test` (voir §3) ; comportement attendu par l'utilisateur différent, à investiguer (`castor.php`, `compose_exec`, `.castor/qa/test.php`).
- **Playwright** : `castor debug-test` s'est arrêté sur un `TimeoutException` (le clic sur « submit » ne faisait rien) ; cause non élucidée.
- `castor qa:static` (PHPStan, deptrac, PHPat, CS) et `qa:mutation` (Infection, min-msi 100) **jamais lancés** sur le nouveau code ; le typage `@phpstan-type Inputs` n'est pas vérifié.
- Calque deptrac `demo/.*` (`deptrac_bc.yaml:60`) : autorise-t-il une dépendance vers `tests/` ?
- DTO `Account` des Stories : remplacer par des états scalaires lus par `__callStatic` (`TwoFactorAccountStory::email()`) avec `@method static` ? Proposé, non tranché.
- `EventSourcingResetter` : décoration sans appel de l'interne ; remplacement complet à décider.
- Subscriptions en mémoire : passent dans tous les runs faits, non prouvé dans tous les ordres d'exécution.
- Le test d'aller-retour du repository détecte la sérialisation, pas un `#[Apply]` oublié.
- `composer.lock` : `symfony/error-handler` est passé de v8.1.5 à v8.1.8 pendant l'installation.
- Domaines autres qu'Identity : état complet non essayé. Playwright, `cron` et `es-dashboard` non testés avec les stores réels.
- À nettoyer en fin de spike : `tests/Iam/Identity/Spike/*`, `ShopperStory`, `BuilderShopperStory`, `demo:fixtures` castor, worktree `ai/foundry-spike`, stash.

## 7. Reprendre

- Branche : `ai/foundry-spike-storefront`. Tests : `castor qa:test <chemin>` (jamais de `castor sh` pour vérifier). Démo : `castor demo:fixtures` (env `demo`, base `demo`).
- Seed reproductible : `.env.test.local` → `FOUNDRY_FAKER_SEED=<n>` (supprimer après usage).
