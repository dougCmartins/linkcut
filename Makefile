COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo docker compose || echo docker-compose)
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
# Node version of the project (.nvmrc). The containers already use it; this is for running npm on the host.
NODE_VERSION := $(shell cat .nvmrc 2>/dev/null || echo 22)

export HOST_UID
export HOST_GID

.DEFAULT_GOAL := help
.PHONY: help up start stop down restart setup check-docker check-node permissions reset-web logs migrate seed fresh \
	test pint shell-api test-web typecheck build shell-web

help: ## Lista os comandos
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "} {printf "  make %-12s %s\n", $$1, $$2}'

up: setup ## Sobe tudo (API :8087, SPA :5173, Mailpit :8025). Rode depois de cada git pull
	$(COMPOSE) up --build

start: up ## O mesmo que make up

stop: ## Para tudo (os dados do banco ficam guardados)
	$(COMPOSE) down

down: stop

restart: stop up ## Para e sobe de novo

# Runs before "up": checks Docker, warns about the Node version and fixes file permissions.
# New dependencies after a git pull are installed by the containers themselves
# (composer install in the API; npm install in the SPA when package-lock.json changed).
setup: check-docker check-node permissions

check-docker:
	@docker info >/dev/null 2>&1 || { echo "Docker não está rodando. Abra o Docker Desktop (ou: sudo systemctl start docker) e rode make up de novo."; exit 1; }

check-node:
	@if command -v node >/dev/null 2>&1; then \
		current=$$(node -v | sed 's/^v//; s/\..*//'); \
		if [ "$$current" != "$(NODE_VERSION)" ]; then \
			echo "Aviso: seu Node local é v$$current; o projeto usa Node $(NODE_VERSION) (.nvmrc)."; \
			echo "       Os containers já usam Node $(NODE_VERSION). Para rodar npm fora do Docker: nvm install && nvm use"; \
		fi; \
	fi

permissions: ## Devolve ao seu usuário os arquivos criados pelos containers
	$(COMPOSE) run --rm --no-deps --user root --entrypoint sh backend -c 'sh docker/fix-permissions.sh'
	@if [ "$(HOST_UID)" != "0" ]; then \
		$(COMPOSE) run --rm --no-deps --user root --entrypoint sh frontend -c \
			'find /app -path /app/node_modules -prune -o ! -user $(HOST_UID) -exec chown $(HOST_UID):$(HOST_GID) {} +'; \
	fi

reset-web: ## Reinstala do zero as dependências do SPA (se algo ainda quebrar)
	$(COMPOSE) run --rm --no-deps --entrypoint sh frontend -c 'rm -rf node_modules/* node_modules/.[!.]* && entrypoint.sh true'

logs:
	$(COMPOSE) logs -f

migrate:
	$(COMPOSE) exec backend php artisan migrate --force

seed: ## Barbearias de demonstração e o login rafael@linkcut.app / linkcut123
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
