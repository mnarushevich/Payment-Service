install_and_start:
	herd isolate 8.4
	herd composer install
	cp .env.example .env
	docker compose up -d
	herd php artisan key:generate

up:
	docker compose up -d

rebuild:
	docker compose up -d --no-deps --build app

setup-hooks:
	docker exec -it payment_service_app git config core.hooksPath .githooks

exec:
	docker exec -it payment_service_app bash

db-seed:
	herd php artisan db:seed

stop:
	docker compose down

setup-tests:
	touch database/database.sqlite
	cp .env.testing.example .env.testing
	herd php artisan key:generate --env=testing
	herd php artisan migrate:fresh --env=testing
	herd php artisan config:clear --env=testing

run-tests:
	herd php artisan test --colors=always --env=testing
