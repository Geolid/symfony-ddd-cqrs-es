## Infrastructure & Environment
##---------------------------------------------------------------------------

SF = $(EXEC) env APP_ENV=$(APP_ENV) php bin/console --ansi

vendor: composer.lock ## Install PHP dependencies
	@$(EXEC) composer install --prefer-dist --no-progress --no-interaction
.PHONY: vendor

warmup: ## Warmup cache for all contexts — shared and all DMs
	@$(SF) cache:warmup
	@$(foreach app,$(APPS),$(SF) cache:warmup --appId=$(app);)
.PHONY: warmup
