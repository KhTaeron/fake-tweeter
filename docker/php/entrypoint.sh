#!/bin/bash

if [ "$1" != "" ]; then
    exec "$@";
    exit 0;
fi

# Migration database
composer install

# Migration database
php bin/console d:m:m --no-interaction

# Clear cache
php bin/console cache:clear

# Chargement des fixtures
php bin/console doctrine:fixtures:load --no-interaction

# Start PHP-FPM
php-fpm