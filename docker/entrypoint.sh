#!/bin/bash
set -e

WEBROOT=/var/www/html

# Install composer dependencies if vendor is missing
if [ ! -d "$WEBROOT/vendor" ]; then
    echo "[myaac] Running composer install..."
    composer install --no-dev --optimize-autoloader --working-dir="$WEBROOT"
fi

# Ensure writable directories exist
mkdir -p "$WEBROOT/system/cache/twig"
mkdir -p "$WEBROOT/system/logs"
mkdir -p "$WEBROOT/system/php_sessions"
mkdir -p "$WEBROOT/images/guilds"
mkdir -p "$WEBROOT/images/houses"
mkdir -p "$WEBROOT/images/gallery"

chown -R www-data:www-data \
    "$WEBROOT/system/cache" \
    "$WEBROOT/system/logs" \
    "$WEBROOT/system/php_sessions" \
    "$WEBROOT/images/guilds" \
    "$WEBROOT/images/houses" \
    "$WEBROOT/images/gallery"

chmod -R 760 "$WEBROOT/system/cache"
chmod 644 "$WEBROOT/config.local.php" 2>/dev/null || true

# Run database setup script
echo "[myaac] Running database setup..."
php /var/www/html/docker/setup.php

exec "$@"
