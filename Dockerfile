FROM unit:php8

ARG IS_DEV=false

WORKDIR /app

EXPOSE 8080

RUN apt-get update && apt-get install -y \
  git curl zip libcurl4 libcurl4-openssl-dev gnupg2 \
  locales locales-all \
  && rm -rf /var/lib/apt/lists/*

RUN pecl install redis && \
  docker-php-ext-install curl && \
  docker-php-ext-enable redis curl

# Dev specific stuff
RUN ($IS_DEV && pecl install xdebug) || :

RUN mkdir -p /app && \
  mkdir -p /ci-cd && \
  mkdir -p /var/run

# Entrypoints and configs
COPY ci-cd /ci-cd

RUN cp /ci-cd/php-custom.ini /usr/local/etc/php/conf.d/90-custom.ini && \
  cp /ci-cd/nginx-unit.json /docker-entrypoint.d/config.json && \
  cp /ci-cd/pre-entrypoint.sh /docker-entrypoint.d/ && \
  cp /ci-cd/composer.phar /usr/bin/composer

# Enable Xdebug and in DEV
RUN ($IS_DEV && cp /ci-cd/xdebug.ini /usr/local/etc/php/conf.d/80-xdebug.ini) || :

# Remove pre-entrypoint in dev, keep in prod
RUN ($IS_DEV && rm -f /docker-entrypoint.d/pre-entrypoint.sh) || :

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-dev --no-progress -o --ansi -n

COPY . /app
