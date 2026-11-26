.PHONY: up down restart test test-unit test-integration lint format keys token guard ci

APP_SERVICE=app
COMPOSE=docker compose
COMPOSE_TEST=docker compose -f compose.test.yml

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart: down up

guard:
	bash scripts/guard.sh

keys:
	mkdir -p keys
	docker run --rm -v $(PWD)/keys:/keys alpine/openssl genrsa -out /keys/private.pem 2048
	docker run --rm -v $(PWD)/keys:/keys alpine/openssl rsa -in /keys/private.pem -pubout -out /keys/public.pem
	@echo "RSA keypair generated in keys/"

token:
	php scripts/generate-dev-jwt.php

lint:
	$(COMPOSE) run --rm $(APP_SERVICE) vendor/bin/phpstan analyse --configuration=phpstan.neon
	$(COMPOSE) run --rm $(APP_SERVICE) vendor/bin/php-cs-fixer fix --dry-run --diff
	$(COMPOSE) run --rm $(APP_SERVICE) vendor/bin/deptrac analyze --config-file=deptrac.yaml --no-cache

format:
	$(COMPOSE) run --rm $(APP_SERVICE) vendor/bin/php-cs-fixer fix

test: test-unit test-integration

test-unit:
	$(COMPOSE) run --rm $(APP_SERVICE) vendor/bin/phpunit --testsuite=unit

test-integration:
	$(COMPOSE_TEST) up -d mysql-test mongo-test rabbitmq-test
	$(COMPOSE_TEST) run --rm $(APP_SERVICE) bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE_TEST) run --rm $(APP_SERVICE) vendor/bin/phpunit --testsuite=integration
	$(COMPOSE_TEST) down -v

ci: guard lint test
