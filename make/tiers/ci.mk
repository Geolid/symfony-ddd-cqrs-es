IN_CONTAINER := $(shell test -f /.dockerenv -o -n "$$CI" && echo yes)

## CI Pipeline
##---------------------------------------------------------------------------

FIG     = docker compose
USERID  = $(shell id -u)
GROUPID = $(shell id -g)
EXEC    = $(if $(IN_CONTAINER),,$(FIG) exec -u $(USERID):$(GROUPID) app)

ci.build: composer-validate vendor assets ## CI — validate and install dependencies
.PHONY: ci.build

ci.static: warmup security static ## CI — warmup then run static analysis
.PHONY: ci.static

ci.test: test ## CI — run test suite
.PHONY: ci.test

dist: ## Build a production-optimized artifact — the GitHub Release workflow attaches it
	@echo "Building production artifact with APP_ENV=$(APP_ENV)..."
	@rm -rf vendor/ var/cache/prod/ dist/
	@env APP_ENV=$(APP_ENV) composer install --optimize-autoloader --classmap-authoritative --prefer-dist --no-progress --no-dev
	@mkdir -p dist
	@tar czf dist/symfony-ddd-cqrs-es.tar.gz \
		--exclude=dist --exclude=.git --exclude='var/cache/dev' --exclude='var/cache/test' .
	@echo "Artifact built at dist/symfony-ddd-cqrs-es.tar.gz"
.PHONY: dist
