## Tests
##---------------------------------------------------------------------------

test: ## Run test suite (optional: make test filter=<name> or suite=<name>)
	@$(EXEC) vendor/bin/paratest --processes 8 --no-coverage \
		$(if $(filter),--filter '$(filter)',) \
		$(if $(suite),--testsuite $(suite),)
.PHONY: test

coverage: ## Run test suite with coverage
	@$(EXEC) env XDEBUG_MODE=coverage vendor/bin/paratest --processes 8
.PHONY: coverage

# Scoped to the diff — 100% is only reasonable because it's just the changed lines, not the whole repo.
mutation: ## Run mutation testing scoped to the diff
	@$(EXEC) env XDEBUG_MODE=coverage vendor/bin/infection \
		--threads=max \
		--git-diff-lines \
		--git-diff-base=origin/main \
		--min-msi=100
.PHONY: mutation
