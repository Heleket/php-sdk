.PHONY: help install update test stan cs cs-fix qa example-invoice example-info example-history example-static-wallet example-webhook example-balance example-payout example-payout-info example-services example-rates webhook-inspect docker-build docker-shell docker-webhook docker-qa clean

PHP        ?= php
COMPOSER   ?= composer
PHPUNIT    ?= vendor/bin/phpunit
PHPSTAN    ?= vendor/bin/phpstan
CS_FIXER   ?= vendor/bin/php-cs-fixer

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

install: ## Install Composer dependencies
	$(COMPOSER) install

update: ## Update Composer dependencies
	$(COMPOSER) update

test: ## Run PHPUnit unit tests
	$(PHPUNIT)

stan: ## Run PHPStan (level 8)
	$(PHPSTAN) analyse

cs: ## Check coding style (dry-run)
	$(CS_FIXER) fix --dry-run --diff

cs-fix: ## Auto-fix coding style
	$(CS_FIXER) fix

qa: test stan cs ## Run all quality gates (test + stan + cs)

example-invoice: ## Create a test invoice via API
	$(PHP) examples/01_create_invoice.php

example-info: ## Look up payment info (pass UUID/order_id via UUID env var)
	$(PHP) examples/02_get_payment_info.php $(UUID)

example-history: ## List recent payments
	$(PHP) examples/03_list_payment_history.php

example-static-wallet: ## Create a static (top-up) wallet
	$(PHP) examples/04_create_static_wallet.php

example-webhook: ## Run the webhook handler on http://localhost:8000
	$(PHP) -S 0.0.0.0:8000 examples/05_handle_webhook.php

example-balance: ## Show merchant + personal balances
	$(PHP) examples/06_get_balance.php

example-payout: ## Create a payout (pass AMOUNT and ADDRESS env vars)
	$(PHP) examples/07_create_payout.php $(AMOUNT) $(ADDRESS)

example-payout-info: ## Look up a payout (pass UUID env var)
	$(PHP) examples/08_get_payout_info.php $(UUID)

example-services: ## List payment services (KIND=payment|payout)
	$(PHP) examples/09_list_services.php $(KIND)

example-rates: ## List exchange rates (CURRENCY=USD by default)
	$(PHP) examples/10_exchange_rates.php $(CURRENCY)

webhook-inspect: ## Inspect a webhook payload from stdin (pass KEY env var)
	bin/heleket-webhook-inspect --key=$(KEY)

docker-build: ## Build the dev Docker image
	docker compose build

docker-shell: ## Open a shell in the dev container
	docker compose run --rm cli sh

docker-webhook: ## Run the webhook handler in Docker on port 8000
	docker compose up webhook

docker-qa: ## Run the QA pipeline in Docker
	docker compose run --rm qa

clean: ## Remove generated caches
	rm -rf .phpunit.cache .phpunit.result.cache .php-cs-fixer.cache .phpstan.cache build coverage
