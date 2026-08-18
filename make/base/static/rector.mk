## Rector
##---------------------------------------------------------------------------

rector: ## Check Rector refactoring rules
	@$(EXEC) env TMPDIR=/srv/var/rector/tmp vendor/bin/rector process --dry-run
.PHONY: rector

rector-fix: ## Apply Rector refactoring rules (optional: make rector-fix file=<path>)
	@$(EXEC) env TMPDIR=/srv/var/rector/tmp vendor/bin/rector process $(file)
.PHONY: rector-fix
