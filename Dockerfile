# syntax=docker/dockerfile:1

FROM node:20-bullseye-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./

RUN npm run build

FROM php:8.4-cli-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath curl gd intl mbstring pdo_mysql pdo_pgsql xml zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts \
    && php artisan package:discover --ansi

COPY --from=assets /app/public/build ./public/build

EXPOSE 10000

ENV PORT=10000

CMD sh -lc 'php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}'
