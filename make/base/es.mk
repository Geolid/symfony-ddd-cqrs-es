## Event Sourcing
##---------------------------------------------------------------------------

replay: ## Rebuild a read model by replaying its events (ex: make replay id=your_id)
	@if [ -z "$(id)" ]; then echo "Error: id is required (ex: make replay id=your_id)"; exit 1; fi
	@$(SF) event-sourcing:subscription:remove --id=$(id) --no-interaction
	@$(SF) event-sourcing:subscription:setup --id=$(id) --no-interaction
	@$(SF) event-sourcing:subscription:boot --id=$(id) --no-interaction
.PHONY: replay
