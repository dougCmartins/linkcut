.PHONY: up down logs install dev test typecheck build

FRONTEND = docker compose run --rm frontend

up: ## Start the stack in the background
	docker compose up -d

down: ## Stop the stack
	docker compose down

logs:
	docker compose logs -f frontend

install:
	$(FRONTEND) npm install

dev:
	docker compose up frontend

test:
	$(FRONTEND) npm run test

typecheck:
	$(FRONTEND) npm run typecheck

build:
	$(FRONTEND) npm run build
