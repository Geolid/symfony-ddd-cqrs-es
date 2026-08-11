## PHPStan
##---------------------------------------------------------------------------

APP_ENV_UCFIRST = $(shell echo "$(APP_ENV)" | awk '{print toupper(substr($$0,1,1)) substr($$0,2)}')

stan: stan.src stan.tests $(addprefix stan.,$(APPS)) ## Run PHPStan on src/, tests/ and all DMs
.PHONY: stan

stan.src: ## Run static analysis on shared src/
	@$(EXEC) env APP_ENV=$(APP_ENV) APP_ENV_UCFIRST=$(APP_ENV_UCFIRST) \
		vendor/bin/phpstan analyse -c phpstan.dist.neon
.PHONY: stan.src

stan.tests: ## Run static analysis on tests/ (always against the test-env container)
	@$(EXEC) env APP_ENV=test APP_ENV_UCFIRST=Test \
		vendor/bin/phpstan analyse -c tests/phpstan.neon
.PHONY: stan.tests

stan.%: ## Run static analysis on a specific DM (ex: make stan.api)
	@$(EXEC) env APP_ID=$* APP_ENV=$(APP_ENV) APP_ENV_UCFIRST=$(APP_ENV_UCFIRST) \
		vendor/bin/phpstan analyse -c $(firstword $(wildcard apps/$*/phpstan.dist.neon) apps/phpstan.dist.neon)
.PHONY: stan.%
