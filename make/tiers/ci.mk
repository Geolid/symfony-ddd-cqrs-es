IN_CONTAINER := $(shell test -f /.dockerenv -o -n "$$CI" && echo yes)

## CI Pipeline
##---------------------------------------------------------------------------

FIG     = docker compose
USERID  = $(shell id -u)
GROUPID = $(shell id -g)
EXEC    = $(if $(IN_CONTAINER),,$(FIG) exec -u $(USERID):$(GROUPID) app)

ci.build: composer-validate vendor assets ## CI — validate, install dependencies and assets
.PHONY: ci.build

ci.static: warmup security static ## CI — warmup then run static analysis
.PHONY: ci.static

ci.test: test ## CI — run test suite
.PHONY: ci.test

# Explicit allowlist of what a running instance actually needs — never "everything except a
# few excludes" (tests/, tools/, demo/, .claude/, .github/... have no reason to ship).
DIST_PATHS = bin/console bootstrap config apps public src make ui vendor Makefile composer.json

dist: ## Build a production-optimized artifact — the GitHub Release workflow attaches it
	@echo "Building production artifact with APP_ENV=$(APP_ENV)..."
	@rm -rf vendor/ dist/
	@env APP_ENV=$(APP_ENV) composer install --optimize-autoloader --classmap-authoritative --prefer-dist --no-progress --no-dev
	@mkdir -p dist
	@echo "Date: $$(date -u +%Y-%m-%dT%H:%M:%SZ)"                  > dist/release.txt
	@echo "Tag: $${GITHUB_REF_NAME:-$$(git describe --tags --always)}" >> dist/release.txt
	@echo "Branch: $$(git rev-parse --abbrev-ref HEAD)"            >> dist/release.txt
	@echo "Commit: $$(git rev-parse HEAD)"                         >> dist/release.txt
	@tar czf dist/symfony-ddd-cqrs-es.tar.gz $(DIST_PATHS) -C dist release.txt
	@echo "Artifact built at dist/symfony-ddd-cqrs-es.tar.gz"
.PHONY: dist
