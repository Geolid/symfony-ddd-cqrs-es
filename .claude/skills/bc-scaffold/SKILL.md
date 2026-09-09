---
name: bc-scaffold
description: Procedure to create a new Bounded Context.
when_to_use: Before creating a new BC.
paths: src/**, tests/**
allowed-tools: Read, Grep, Glob, Edit, Write, Bash(make:*)
effort: low
---
## Procedure

### 0. Decide whether a new BC is warranted

Judge on DDD/CQRS/ES theory, never on existing code shape or a written convention. Apply all four; none suffices alone; a failure on #2 disqualifies alone.

1. **Distinct vocabulary** — a term meaningful on one side, meaningless on the other.
2. **Acyclic integration, at the business-process level** — a bidirectional dependency graph is fine on its own; disqualifies only when one operation needs both sides to conclude, or both replicate the same sub-state of the other. Check the whole ruleset before disqualifying, never just the one edge in question.
3. **Independent cadence** — different change driver, different stakeholder; mirroring the other side's own sub-cycle doesn't count.
4. **Real invariant/multiplicity** — a consistency rule, or a 1-N relationship, that exists nowhere else.

### 1. Choose the Subdomain and name the BC

List `src/`; pick an existing Subdomain. A new Subdomain only if none fits:

1. psr-4 entry (`autoload` + `autoload-dev`) in `composer.json`, then `composer dump-autoload`
2. Create `config/services/<lowercase-subdomain>.php` (`SubdomainServiceLoader::load($services, '<Subdomain>')`)
3. Add `<testsuite name="<lowercase-subdomain>"><directory>tests/<Subdomain></directory></testsuite>` to `phpunit.dist.xml`

If the BC will host 2+ aggregates, name it after the capability/process it serves — never after one of its own aggregates, and never a word that only stutters against an aggregate name it will contain.

### 2. Create the structure

`src/<Subdomain>/<BC>/{Domain,Application,Infrastructure}/` mirrored at `tests/<Subdomain>/<BC>/`

### 3. Wire the isolation

`deptrac_bc.yaml`: a layer (collector `src/<Subdomain>/<BC>/.*`) + a ruleset row listing the BCs actually granted (real imports only, minimum `[ Shared, Vendors ]`)

### 4. Write the skeleton

- A minimal root Aggregate (look at 2-3 existing Aggregates, extract the shared pattern) + its first Domain Event
- `Domain/Repository/<Aggregate>RepositoryInterface` + implementation under `Infrastructure/EventStore/<Vendor><Aggregate>Repository`
- An `AggregateRootTestCase`-based test alongside the Aggregate, from the start

### 5. Validate

`castor qa:deptrac --scope=bc` and `castor qa:stan` before any business logic
