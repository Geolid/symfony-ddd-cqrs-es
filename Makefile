COMPOSE ?= docker compose
EXEC    ?= $(COMPOSE) exec app

.DEFAULT_GOAL := help

help: ## Display this help message
	@printf "\033[33mUsage:\033[0m\n  make [target]\n\n"
	@grep -hE '^[a-zA-Z0-9_.-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-24s\033[0m %s\n", $$1, $$2}'
.PHONY: help

up: ## Start the stack (nginx, php-fpm, mariadb, mailpit)
	$(COMPOSE) up -d
.PHONY: up

down: ## Stop the stack
	$(COMPOSE) down
.PHONY: down

sh: ## Shell into the app container
	$(EXEC) sh
.PHONY: sh

install: ## composer install
	$(EXEC) composer install
.PHONY: install

cc: ## Cache clear + warmup, every Delivery Mechanism
	@for dm in web api cli webhook; do $(EXEC) bin/console --appId=$$dm cache:clear; done
.PHONY: cc

test: ## Run the test suite (filter=<x> to scope it)
	$(EXEC) vendor/bin/phpunit $(if $(filter),--filter=$(filter))
.PHONY: test

stan: ## PHPStan (includes the phpat architecture suite)
	$(EXEC) vendor/bin/phpstan analyse
.PHONY: stan

deptrac-layers: ## Onion layering: Domain / Application / Infrastructure
	$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_layers.yaml
.PHONY: deptrac-layers

deptrac-bc: ## Bounded Context isolation
	$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_bc.yaml
.PHONY: deptrac-bc

deptrac-dm: ## Delivery Mechanism -> Bounded Context reach
	$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_dm.yaml
.PHONY: deptrac-dm

cs-fix: ## Auto-fix code style (file=<path> to scope it, repo-wide otherwise)
	$(EXEC) vendor/bin/php-cs-fixer fix $(file)
.PHONY: cs-fix

static: cs-fix stan deptrac-layers deptrac-bc deptrac-dm ## Lint + CS + PHPStan/phpat + Deptrac
.PHONY: static

qa: static test ## Everything static + the test suite
.PHONY: qa
