## Database Management
##---------------------------------------------------------------------------

db: ## Create database and run full setup
	@$(SF) event-sourcing:database:create --if-not-exists --no-interaction
	@$(SF) doctrine:database:create --connection=read_model --if-not-exists --no-interaction
	@$(MAKE) db-update
.PHONY: db

db-update: ## Update schema, register subscriptions and boot catch-up
	@$(SF) event-sourcing:schema:update --force --no-interaction
	@$(SF) event-sourcing:subscription:setup --no-interaction
	@$(SF) event-sourcing:subscription:boot --no-interaction
	@$(SF) messenger:setup-transports --no-interaction
.PHONY: db-update

db-reset: ## Drop database and run fresh setup
	@case "$(TIER)" in \
		dev|demo) ;; \
		*) echo "Refused: db-reset is destructive and only allowed on TIER=dev or demo (got $(TIER))."; exit 1 ;; \
	esac
	@$(SF) event-sourcing:database:drop --force --if-exists --no-interaction
	@$(SF) doctrine:database:drop --connection=read_model --force --if-exists --no-interaction
	@$(MAKE) db
.PHONY: db-reset
