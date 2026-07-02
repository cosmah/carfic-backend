#!/bin/sh
set -e

if [ -f /var/www/html/artisan ]; then
    echo "Running safe migration sync..."
    php /var/www/html/artisan migrate:safe --force
fi

return 0
