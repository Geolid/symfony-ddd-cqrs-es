## Linting
##---------------------------------------------------------------------------

lint: lint-container lint-twig lint-translations ## Run all linters
.PHONY: lint

lint-container: ## Validate Symfony container for all DMs
	@$(foreach app,$(APPS),$(SF) lint:container --no-debug --appId=$(app) &&) true
.PHONY: lint-container

# Not every DM loads TwigBundle in every env (e.g. an API-only DM) — `lint:twig` wouldn't
# even exist there, so check the command is registered before calling it.
lint-twig: ## Check Twig syntax for DMs where TwigBundle is loaded in the current env
	@$(foreach app,$(APPS),$(if $(wildcard apps/$(app)/config/packages/twig.php),if $(SF) list --raw --appId=$(app) 2>/dev/null | grep -q '^lint:twig'; then $(SF) lint:twig $(shell grep -oE "ui/templates[^'\"]*" apps/$(app)/config/packages/twig.php) $(wildcard apps/$(app)/templates) --appId=$(app) || exit 1; fi &&)) true
.PHONY: lint-twig

lint-translations: ## Check YAML syntax of translation files (shared + per DM)
	@$(foreach app,$(APPS),$(if $(wildcard apps/$(app)/config/packages/translation.php),$(SF) lint:yaml ui/translations $(wildcard apps/$(app)/translations) --appId=$(app) &&)) true
.PHONY: lint-translations
