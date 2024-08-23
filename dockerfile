FROM library/php:fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pcntl redis fileinfo pdo_mysql \ 
&& apk --no-cache add shadow supervisor nginx nginx-mod-http-brotli mysql-client git patch redis vim lsof mtr\
&& addgroup -S -g 1000 www && adduser -S -G www -u 1000 www

#复制项目文件以及配置文件
WORKDIR /www
COPY .docker /
COPY . /www

#安装composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
&& php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
&& php composer-setup.php \
&& php -r "unlink('composer-setup.php');" \
&& mv composer.phar /usr/local/bin/composer

RUN composer install --optimize-autoloader --no-cache --no-dev \
&& php artisan storage:link \
&& chown -R www:www /www \
&& chmod -R 775 /www \
$$ chmod -R 777 /www/storage

CMD ["supervisord", "--nodaemon", "-c", "/etc/supervisor/supervisord.conf"]
