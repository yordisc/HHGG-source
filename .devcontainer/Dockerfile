FROM mcr.microsoft.com/devcontainers/php:8.4-bullseye

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      build-essential \
      autoconf \
      pkg-config \
      default-libmysqlclient-dev \
      default-mysql-client; \
    docker-php-ext-install pdo_mysql; \
    apt-get purge -y --auto-remove build-essential autoconf pkg-config default-libmysqlclient-dev; \
    rm -rf /var/lib/apt/lists/*
