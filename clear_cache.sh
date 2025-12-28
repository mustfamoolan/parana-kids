#!/bin/bash
# Script to clear all Laravel caches after deployment

echo "Clearing Laravel caches..."

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "Re-caching optimizations..."

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "Cache cleared and optimized successfully!"

