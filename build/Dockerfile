FROM php:8.4-fpm

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git unzip libzip-dev && \
    docker-php-ext-install zip

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN useradd -m dev
WORKDIR /srv/phel-cli-skeleton
