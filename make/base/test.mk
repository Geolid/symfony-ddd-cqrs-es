## Tests
##---------------------------------------------------------------------------

test: ## Run test suite (optional: make test filter=name or suite=name)
	@$(EXEC) vendor/bin/paratest --processes 8 --no-coverage \
		$(if $(filter),--filter '$(filter)',) \
		$(if $(suite),--testsuite $(suite),)
.PHONY: test

coverage: ## Run test suite with coverage
	@$(EXEC) env XDEBUG_MODE=coverage vendor/bin/paratest --processes 8
.PHONY: coverage

base ?= origin/main

mutation: ## Run mutation testing scoped to the diff (optional: make mutation base=origin/<branch>)
	@$(EXEC) env XDEBUG_MODE=coverage vendor/bin/infection \
		--threads=max \
		--git-diff-lines \
		--git-diff-base=$(base) \
		--min-msi=100
.PHONY: mutation
