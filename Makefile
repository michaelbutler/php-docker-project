
build:
	docker build -t php-project-prod:latest .

build-dev:
	docker build -t php-project-dev:latest --build-arg IS_DEV=true .

composer:
	php ci-cd/composer.phar install

# Note: must run make docker-up first for redis support
server-dev: build-dev
	docker run --rm -it -p 8080:8080 \
	-e XDEBUG_SESSION=phpproject \
	-e PHP_IDE_CONFIG=serverName=dockerinternal \
	-e IS_DEV=1 \
	-v "${PWD}:/app:ro" php-project-dev:latest

server-prod: build
	docker run --rm -it -p 8080:8080 php-project-prod:latest

docker-up:
	docker-compose up -d

docker-down:
	docker-compose stop

client-setup:
	npm install

# Auto watch and compile scss to css in a terminal
# Ctrl-c to exit
watch:
	npm run watch

fixer:
	echo "Running php-cs-fixer in real mode..."
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

psalm:
	echo "Running psalm..."
	vendor/bin/psalm

# Formats all code and runs static analysis checking
fix-all: fixer psalm

# Build assets for production.
# You should run this right before going live, and commit all the changes it produces!
assets:
	./ci-cd/build-prod-assets.sh
