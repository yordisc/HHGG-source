FROM mcr.microsoft.com/devcontainers/php:8.4-bullseye

RUN set -eux; \
  for source_file in /etc/apt/sources.list /etc/apt/sources.list.d/*.list; do \
    [ -e "$source_file" ] || continue; \
    sed -i '/dl\.yarnpkg\.com\/debian/d' "$source_file" || true; \
  done; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      build-essential \
      autoconf \
      pkg-config \
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      default-libmysqlclient-dev \
      default-mysql-client; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install gd pdo_mysql; \
    apt-get purge -y --auto-remove \
      build-essential \
      autoconf \
      pkg-config \
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      default-libmysqlclient-dev; \
    rm -rf /var/lib/apt/lists/*
