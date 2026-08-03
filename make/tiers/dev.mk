## Development & Lifecycle
##---------------------------------------------------------------------------

start: ## Full project installation and startup
start: compose.override.yaml up vendor db assets
.PHONY: start

up: ## Build images and start containers
	@$(FIG) pull
	@$(FIG) build --pull
	@$(FIG) up -d
.PHONY: up

stop: ## Stop and remove containers
	@$(FIG) down
.PHONY: stop

destroy: ## Remove containers, volumes, and networks
	@$(FIG) down -v
.PHONY: destroy

## Daily Use
##---------------------------------------------------------------------------

sh: ## Open shell in app container (optional: make sh cmd=<command>)
	@$(EXEC) /bin/sh $(if $(cmd),-c "$(cmd)")
.PHONY: sh

dump: ## Start Symfony VarDumper server
	@$(SF) server:dump
.PHONY: dump

log.%: ## Display logs for a service (ex: make log.app, make log.db)
	@$(FIG) logs -f $*
.PHONY: log.%

## Maintenance
##---------------------------------------------------------------------------

cc: ## Clear all caches
	@rm -rf var/cache/*
	@$(MAKE) warmup
.PHONY: cc

compose.override.yaml: compose.override.yaml.dist
	@test -f $@ || cp $< $@
