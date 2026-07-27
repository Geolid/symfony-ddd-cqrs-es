include make/base/static/lint.mk
include make/base/static/cs.mk
include make/base/static/deptrac.mk
include make/base/static/stan.mk

## Static Checks
##---------------------------------------------------------------------------

composer-validate: ## Validate composer configuration
	@$(EXEC) composer validate --no-check-publish --strict
.PHONY: composer-validate

security: ## Check for vulnerable dependencies
	@$(EXEC) composer audit
.PHONY: security

static: lint cs deptrac stan ## Run linters, coding standards and architecture checks
.PHONY: static
