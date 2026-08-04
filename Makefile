TIER     ?= dev
APP_ENV  ?= dev
APPS     := $(shell find apps -mindepth 1 -maxdepth 1 -type d -exec basename {} \; | sort)

IN_CONTAINER := $(shell test -f /.dockerenv -o -n "$$CI" && echo yes)
FIG          = docker compose
USERID       = $(shell id -u)
GROUPID      = $(shell id -g)
EXEC         = $(if $(IN_CONTAINER),,$(FIG) exec $(EXEC_ENV) -u $(USERID):$(GROUPID) app)

-include make/tiers/$(TIER).mk

include make/base/infra.mk
include make/base/db.mk
include make/base/es.mk
include make/base/qa.mk

.DEFAULT_GOAL := help

help: ## Display this help message
	@printf "\033[33mUsage:\033[0m\n  make [target]\n"
	@grep -hE '(^[a-zA-Z0-9_.%-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {if ($$2!="") printf "  \033[32m%-30s\033[0m %s\n", $$1, $$2; else if ($$1 != "") printf "\n\033[34m%s\033[0m\n", substr($$1, 3)}'
.PHONY: help
