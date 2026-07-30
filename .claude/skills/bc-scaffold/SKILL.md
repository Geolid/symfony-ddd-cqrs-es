---
name: bc-scaffold
description: Procedure to create a new Bounded Context.
when_to_use: Before creating a new BC.
paths: src/**, tests/**
allowed-tools: Read, Grep, Glob, Edit, Write, Bash(make:*)
effort: low
---
## Procedure

### 1. Choose the Subdomain

List `src/`; pick an existing one. A new Subdomain only if none fits:

1. psr-4 entry (`autoload` + `autoload-dev`) in `composer.json`, then `composer dump-autoload`
2. Create `config/services/<lowercase-subdomain>.php` (`SubdomainServiceLoader::load($services, '<Subdomain>')`)
3. Add `<testsuite name="<lowercase-subdomain>"><directory>tests/<Subdomain></directory></testsuite>` to `phpunit.dist.xml`

### 2. Create the structure

`src/<Subdomain>/<BC>/{Domain,Application,Infrastructure}/` mirrored at `tests/<Subdomain>/<BC>/`

### 3. Wire the isolation

`deptrac_bc.yaml`: a layer (collector `src/<Subdomain>/<BC>/.*`) + a ruleset row listing the BCs actually granted (real imports only, minimum `[ Shared, Vendors ]`)

### 4. Write the skeleton

- A minimal root Aggregate (look at 2-3 existing Aggregates, extract the shared pattern) + its first Domain Event
- `Domain/Repository/XxxRepositoryInterface` + implementation under `Infrastructure/Persistence/**/Repository/`
- An `AggregateRootTestCase`-based test alongside the Aggregate, from the start (see `tests/Sales/Order/Domain/OrderTest.php`)

### 5. Validate

`make deptrac-bc` and `make stan` before any business logic
