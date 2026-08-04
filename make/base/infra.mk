## Infrastructure & Environment
##---------------------------------------------------------------------------

SF = $(EXEC) env APP_ENV=$(APP_ENV) php bin/console --ansi

vendor: composer.lock ## Install PHP dependencies
	@$(EXEC) composer install --prefer-dist --no-progress --no-interaction

assets: ## Install bundle and AssetMapper assets for all DMs
	@$(foreach app,$(APPS),$(SF) assets:install public/ --no-cleanup --appId=$(app) &&) true
	@$(foreach app,$(APPS),$(if $(wildcard apps/$(app)/importmap.php),$(SF) importmap:install --appId=$(app) &&)) true
.PHONY: assets

warmup: ## Warmup cache for all contexts — shared and all DMs
	@$(SF) cache:warmup
	@$(foreach app,$(APPS),$(SF) cache:warmup --appId=$(app);)
.PHONY: warmup
