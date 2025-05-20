#!/bin/bash

if [ ! -d ".git" ]; then
  echo "Please deploy using Git."
  exit 1
fi

if ! command -v git &> /dev/null; then
    echo "Git is not installed! Please install git and try again."
    exit 1
fi

git config --global --add safe.directory $(pwd)
git fetch --all && git reset --hard origin/master && git pull origin master
rm -rf composer.lock composer.phar
wget https://github.com/composer/composer/releases/latest/download/composer.phar -O composer.phar
php composer.phar update -vvv

php_main_version=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1)
if [ $php_main_version -ge 8 ]; then
    php composer.phar require joanhey/adapterman
    if ! php -m | grep -q "pcntl"; then
        echo "Adding pcntl extension to cli-php.ini"
        sed -i '/extension=redis.so/a extension=pcntl.so' cli-php.ini
    fi
    php -c cli-php.ini webman.php stop
    echo "Webman stopped.Please restart it by yourself."
fi

php artisan v2board:update

if [ -f "/etc/init.d/bt" ]; then
  chown -R www $(pwd);
fi
