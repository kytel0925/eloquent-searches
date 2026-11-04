# Development image running the lowest supported PHP version.
FROM php:8.2-cli-alpine

RUN apk add --no-cache git unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
