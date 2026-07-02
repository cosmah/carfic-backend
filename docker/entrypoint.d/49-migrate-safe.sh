#!/bin/sh
set -e

app_base_dir=${APP_BASE_DIR:-/var/www/html}

if [ -f "$app_base_dir/artisan" ]; then
    echo "=========================================="
    echo "Running safe migration sync (migrate:safe)"
    echo "=========================================="
    php "$app_base_dir/artisan" migrate:safe --force
    echo "Safe migration sync completed."
else
    echo "Artisan file not found at $app_base_dir/artisan"
    exit 1
fi

exit 0
