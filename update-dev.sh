#!/bin/bash

echo "========================================="
echo "  ZicBoard - Update Dev Branch"
echo "========================================="

# Stash any uncommitted changes
echo ">> Stashing local changes..."
git stash

# Fetch and pull latest from dev branch
echo ">> Switching to dev branch..."
git checkout dev

echo ">> Pulling latest code..."
git pull origin dev

# Install/update composer dependencies
echo ">> Updating composer dependencies..."
composer install --no-dev --optimize-autoloader

# Clear application cache
echo ">> Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:clear

echo "========================================="
echo "  ✅ Update completed!"
echo "========================================="
