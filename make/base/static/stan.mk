## PHPStan
##---------------------------------------------------------------------------

APP_ENV_UCFIRST = $(shell echo "$(APP_ENV)" | awk '{print toupper(substr($$0,1,1)) substr($$0,2)}')

stan: stan.src $(addprefix stan.,$(APPS)) ## Run PHPStan on src/ and all DMs
.PHONY: stan

stan.src: ## Run static analysis on shared src/
	@$(EXEC) env APP_ENV=$(APP_ENV) APP_ENV_UCFIRST=$(APP_ENV_UCFIRST) \
		vendor/bin/phpstan analyse -c phpstan.dist.neon
.PHONY: stan.src

stan.%: ## Run static analysis on a specific DM (ex: make stan.api)
	@$(EXEC) env APP_ID=$* APP_ENV=$(APP_ENV) APP_ENV_UCFIRST=$(APP_ENV_UCFIRST) \
		vendor/bin/phpstan analyse -c $(firstword $(wildcard apps/$*/phpstan.dist.neon) apps/phpstan.dist.neon)
.PHONY: stan.%
