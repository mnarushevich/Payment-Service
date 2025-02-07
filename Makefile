install_and_start:
	composer install
	cp .env.example .env
	php artisan sail:install
	./vendor/bin/sail up -d
	docker exec payment_service_app php artisan key:generate
	docker exec payment_service_app php artisan jwt:secret --force

up:
	./vendor/bin/sail up -d

rebuild:
	docker compose up -d --no-deps --build app

setup-hooks:
	docker exec -it payment_service_app git config core.hooksPath .githooks

exec:
	docker exec -it payment_service_app bash

db-seed:
	docker exec payment_service_app php artisan db:seed

stop:
	./vendor/bin/sail down
