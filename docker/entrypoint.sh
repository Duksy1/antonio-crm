#!/bin/sh
set -e

php artisan migrate --force

if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

exec "$@"
