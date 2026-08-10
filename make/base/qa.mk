include make/base/static/static.mk
include make/base/test.mk

## Quality Assurance
##---------------------------------------------------------------------------

qa: composer-validate static coverage ## Run full QA pipeline
	@$(MAKE) mutation coverage=1
.PHONY: qa
