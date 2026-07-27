IN_CONTAINER := $(shell test -f /.dockerenv -o -n "$$CI" && echo yes)

## Demo
##---------------------------------------------------------------------------

APP_ENV = demo
export APP_ENV # Only needed when running from inside the container (EXEC is empty)

FIG     = docker compose
USERID  = $(shell id -u)
GROUPID = $(shell id -g)
EXEC    = $(if $(IN_CONTAINER),,$(FIG) exec -e APP_ENV=$(APP_ENV) -u $(USERID):$(GROUPID) app)

demo: db-reset ## Seed the demo
	@$(EXEC) php demo/console demo:seed
.PHONY: demo

demo-list: ## List available demo commands
	@$(EXEC) php demo/console list demo
.PHONY: demo-list
