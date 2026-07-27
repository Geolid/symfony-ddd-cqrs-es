## Architecture
##---------------------------------------------------------------------------

deptrac: deptrac-bc deptrac-layers deptrac-dm ## Run all architectural checks
.PHONY: deptrac

deptrac-bc: ## Check Bounded Context isolation
	@$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_bc.yaml --fail-on-uncovered --report-uncovered
.PHONY: deptrac-bc

deptrac-layers: ## Check architecture layers
	@$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_layers.yaml --fail-on-uncovered --report-uncovered
.PHONY: deptrac-layers

deptrac-dm: ## Check Delivery Mechanism isolation
	@$(EXEC) vendor/bin/deptrac analyse --config-file=deptrac_dm.yaml --fail-on-uncovered --report-uncovered
.PHONY: deptrac-dm
