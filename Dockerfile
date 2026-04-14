# syntax=docker/dockerfile:1

FROM node:20-bullseye-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./

RUN npm run build

FROM php:8.4-fpm-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    nginx \
    gettext-base \
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

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container
RUN rm -f /etc/nginx/conf.d/default.conf /etc/nginx/sites-enabled/default

COPY --from=assets /app/public/build ./public/build

EXPOSE 10000

ENV PORT=10000

CMD ["/usr/local/bin/start-container"]
