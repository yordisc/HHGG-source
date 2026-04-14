#!/usr/bin/env bash
set -euo pipefail

export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

php artisan migrate --force
php-fpm -D
exec nginx -g 'daemon off;'
