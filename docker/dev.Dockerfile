FROM php:8.2-cli

RUN apt-get update -q && apt-get install -y rsync zip unzip git > /dev/null 2>&1 \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*
