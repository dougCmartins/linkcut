COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo docker compose || echo docker-compose)
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)

export HOST_UID
export HOST_GID

.PHONY: up down logs migrate seed fresh test pint shell-api test-web typecheck build shell-web permissions

up: permissions ## Build and start the whole stack (API :8087, SPA :5173, Mailpit :8025, Pulse :8087/pulse)
	$(COMPOSE) up --build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

migrate:
	$(COMPOSE) exec backend php artisan migrate --force

seed: ## Demo tenants and the demo login rafael@linkcut.app / linkcut123
	$(COMPOSE) exec backend php artisan db:seed --force

fresh:
	$(COMPOSE) exec backend php artisan migrate:fresh --seed --force

test:
	$(COMPOSE) exec backend php artisan test

pint:
	$(COMPOSE) exec backend vendor/bin/pint

shell-api:
	$(COMPOSE) exec backend bash

test-web:
	$(COMPOSE) exec frontend npm run test

typecheck:
	$(COMPOSE) exec frontend npm run typecheck

build:
	$(COMPOSE) exec frontend npm run build

shell-web:
	$(COMPOSE) exec frontend sh

permissions:
	$(COMPOSE) run --rm --no-deps --user root --entrypoint sh backend -c 'sh docker/fix-permissions.sh'
