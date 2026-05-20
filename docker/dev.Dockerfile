FROM php:8.2-cli

RUN apt-get update -q && apt-get install -y rsync zip unzip git > /dev/null 2>&1 \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*

# Pre-warm Composer package cache so release runs don't download anything
COPY composer.json composer.lock /tmp/composer-cache/
RUN cd /tmp/composer-cache \
 && composer install --no-interaction --prefer-dist --quiet \
 && composer install --no-interaction --prefer-dist --no-dev --quiet \
 && rm -rf /tmp/composer-cache
