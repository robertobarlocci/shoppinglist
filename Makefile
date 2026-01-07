# Development Commands
# Run `make help` to see all available commands

.PHONY: help up down restart build logs shell npm-dev test lint analyse ci fresh migrate seed cache-clear

# Default target
help:
	@echo "Shopping List - Development Commands"
	@echo ""
	@echo "Docker Commands:"
	@echo "  make up         - Start all containers"
	@echo "  make down       - Stop all containers"
	@echo "  make restart    - Restart all containers"
	@echo "  make build      - Build/rebuild containers"
	@echo "  make logs       - View container logs"
	@echo "  make shell      - Open shell in app container"
	@echo ""
	@echo "Development Commands:"
	@echo "  make npm-dev    - Start Vite dev server"
	@echo "  make npm-build  - Build frontend assets"
	@echo ""
	@echo "Testing & Quality:"
	@echo "  make test       - Run PHPUnit tests"
	@echo "  make lint       - Run Pint code formatter"
	@echo "  make analyse    - Run PHPStan analysis"
	@echo "  make ci         - Run all CI checks"
	@echo ""
	@echo "Database Commands:"
	@echo "  make fresh      - Fresh migration with seeds"
	@echo "  make migrate    - Run migrations"
	@echo "  make seed       - Run seeders"
	@echo ""
	@echo "Cache Commands:"
	@echo "  make cache-clear - Clear all caches"
	@echo "  make optimize   - Optimize for production"

# Docker Commands
up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build --no-cache

logs:
	docker compose logs -f

shell:
	docker compose exec app bash

# Development Commands
npm-dev:
	npm run dev

npm-build:
	npm run build

# Testing & Quality
test:
	docker compose exec app php artisan test

test-coverage:
	docker compose exec app php artisan test --coverage

lint:
	docker compose exec app composer lint

lint-check:
	docker compose exec app composer lint:check

analyse:
	docker compose exec app composer analyse

ci:
	docker compose exec app composer ci

# Database Commands
fresh:
	docker compose exec app php artisan migrate:fresh --seed

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

# Cache Commands
cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

optimize:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

# Quick Commands
dev: up npm-dev

prod: optimize npm-build

# Artisan shortcut
artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
