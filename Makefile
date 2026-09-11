.PHONY: setup up down migrate seed fresh frontend-install dev test logs

# First-time setup: builds the backend containers, prepares the database,
# and installs the frontend dependencies. Safe to re-run.
setup:
	test -f backend/.env || cp backend/.env.example backend/.env
	test -f frontend/.env.local || cp frontend/.env.example frontend/.env.local
	docker compose up -d --build
	docker compose exec app composer install
	docker compose exec app php artisan key:generate --ansi
	docker compose exec app php artisan migrate --seed
	docker compose exec app php artisan storage:link
	cd frontend && npm install

up:
	docker compose up -d

down:
	docker compose down

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

# Wipes and rebuilds the database with fresh demo data.
fresh:
	docker compose exec app php artisan migrate:fresh --seed

frontend-install:
	cd frontend && npm install

# Starts the backend containers (API on :8000, admin on :8000/admin) and
# the frontend dev server (:3000) in the foreground.
dev: up
	cd frontend && npm run dev

test:
	docker compose exec app php artisan test

logs:
	docker compose logs -f
