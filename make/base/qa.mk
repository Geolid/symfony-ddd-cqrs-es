include make/base/static/static.mk
include make/base/test.mk

## Quality Assurance
##---------------------------------------------------------------------------

qa: composer-validate lint cs deptrac stan test mutation ## Run full QA pipeline
.PHONY: qa
