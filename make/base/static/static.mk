include make/base/static/lint.mk
include make/base/static/cs.mk
include make/base/static/deptrac.mk
include make/base/static/stan.mk
include make/base/static/rector.mk

## Static Checks
##---------------------------------------------------------------------------

composer-validate: ## Validate composer configuration
	@$(EXEC) composer validate --no-check-publish --strict
.PHONY: composer-validate

security: ## Check for vulnerable dependencies
	@$(EXEC) composer audit
.PHONY: security

static: lint cs deptrac stan rector ## Run linters, coding standards, architecture checks and Rector
.PHONY: static
