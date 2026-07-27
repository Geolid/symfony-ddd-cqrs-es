## Coding Standards
##---------------------------------------------------------------------------

cs: cs-twig cs-php ## Check all coding standards
.PHONY: cs

cs-twig: ## Check Twig coding standards
	@$(EXEC) vendor/bin/twig-cs-fixer lint
.PHONY: cs-twig

cs-php: ## Check PHP coding standards
	@$(EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff
.PHONY: cs-php

cs-fix: cs-twig-fix cs-php-fix ## Fix all coding standards
.PHONY: cs-fix

cs-twig-fix: ## Fix Twig coding standards (optional: make cs-twig-fix file=<path>)
	@$(EXEC) vendor/bin/twig-cs-fixer lint --fix $(file)
.PHONY: cs-twig-fix

cs-php-fix: ## Fix PHP coding standards (optional: make cs-php-fix file=<path>)
	@$(EXEC) vendor/bin/php-cs-fixer fix $(file)
.PHONY: cs-php-fix
