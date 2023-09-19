install:
	docker-compose up -d --build app
	docker-compose exec app cp /var/www/html/.env.example /var/www/html/.env
	docker compose run --rm composer install
	docker compose run --rm npm install 
    docker-compose exec app chmod 777 /var/www/html
    docker-compose exec app chmod 777 /var/www/html/.env
	docker compose run --rm artisan key:generate
	docker-compose run --rm artisan storage:link
	docker-compose run --rm artisan optimize:clear
	docker-compose run --rm artisan optimize
	@make fresh
	
create-project:
	mkdir -p src
	docker compose build
	docker compose up -d
	docker compose run --rm composer create-project --prefer-dist laravel/laravel .
	docker compose run --rm artisan key:generate
	docker compose run --rm artisan storage:link
	docker-compose run --rm chmod -R 777 storage bootstrap/cache
	@make fresh
up:
	docker compose up -d
build:
	docker compose build
remake:
	@make destroy
	@make install
stop:
	docker compose stop
down:
	docker compose down --remove-orphans
down-v:
	docker compose down --remove-orphans --volumes
restart:
	@make down
	@make up
destroy:
	docker compose down --rmi all --volumes --remove-orphans
ps:
	docker compose ps
logs:
	docker compose logs
logs-watch:
	docker compose logs --follow
log-web:
	docker compose logs web
log-web-watch:
	docker compose logs --follow web
log-app:
	docker compose logs app
log-app-watch:
	docker compose logs --follow app
log-db:
	docker compose logs db
log-db-watch:
	docker compose logs --follow db
web:
	docker compose exec web bash
app:
	docker compose exec app bash
migrate:
	docker compose run --rm artisan migrate
fresh:
	docker compose run --rm artisan migrate:fresh --seed
seed:
	docker compose run --rm artisan db:seed
dacapo:
	docker compose run --rm artisan dacapo
rollback-test:
	docker compose run --rm artisan migrate:fresh
	docker compose run --rm artisan migrate:refresh
tinker:
	docker compose run --rm artisan tinker
test:
	docker compose run --rm artisan test
optimize:
	docker compose run --rm artisan optimize
optimize-clear:
	docker compose run --rm artisan optimize:clear
cache:
	docker compose run --rm composer dump-autoload -o
	@make optimize
	docker compose run --rm artisan event:cache
	docker compose run --rm artisan view:cache
cache-clear:
	docker compose run --rm composer clear-cache
	@make optimize-clear
	docker compose run --rm artisan event:clear
db:
	docker compose exec db bash
sql:
	docker compose exec db bash -c 'mysql -u $$MYSQL_USER -p$$MYSQL_PASSWORD $$MYSQL_DATABASE'
redis:
	docker compose exec redis redis-cli
ide-helper:
	docker compose run --rm artisan clear-compiled
	docker compose run --rm artisan ide-helper:generate
	docker compose run --rm artisan ide-helper:meta
	docker compose run --rm artisan ide-helper:models --nowrite
