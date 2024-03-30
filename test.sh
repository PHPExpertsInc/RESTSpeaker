#!/bin/bash

clear
rm -rf composer.lock vendor
PHP_VERSION=7.2 composer install

time for PHPV in 7.2 7.3 7.4 8.0 8.1 8.2 8.3; do
    echo "PHP Version: $PHPV"
    PHP_VERSION=$PHPV composer --version
    PHP_VERSION=$PHPV composer update
    PHP_VERSION=$PHPV phpunit
    read
done

