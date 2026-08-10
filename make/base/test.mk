## Tests
##---------------------------------------------------------------------------

test: ## Run test suite (optional: make test filter=<name> or suite=<name>)
	@$(EXEC) vendor/bin/paratest --processes 8 --no-coverage \
		$(if $(filter),--filter '$(filter)',) \
		$(if $(suite),--testsuite $(suite),)
.PHONY: test

coverage: ## Run test suite with coverage
	@$(EXEC) vendor/bin/paratest --processes 8
.PHONY: coverage

# Scoped to the diff — 100% is only reasonable because it's just the changed lines, not the whole repo.
mutation: ## Run mutation testing scoped to the diff (optional: make mutation coverage=1 to reuse var/coverage from `make coverage` instead of running the suite again)
	@$(EXEC) vendor/bin/infection \
		--threads=max \
		--git-diff-lines \
		--git-diff-base=origin/main \
		--min-msi=100 \
		$(if $(coverage),--coverage=var/coverage --skip-initial-tests,)
.PHONY: mutation
