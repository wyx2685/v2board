# 使用PHP8.1官方镜像作为基础镜像
FROM library/php:8.1-fpm-alpine

# 安装PHP扩展和其他依赖
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath fileinfo pcntl pdo_mysql redis igbinary sockets \ 
&& apk --no-cache add git mysql-client lsof nginx nginx-mod-http-brotli patch shadow redis supervisor \
&& addgroup -S -g 1000 www && adduser -S -G www -u 1000 www

#复制项目文件以及配置文件
WORKDIR /www
COPY . /www
COPY .docker /

# 安装Composer生成vendor目录
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
RUN composer install --optimize-autoloader --no-cache --no-dev \
&& php artisan storage:link \
&& chown -R www:www /www \
&& chmod -R 777 /www

# 进程守护
CMD ["supervisord", "--nodaemon", "-c", "/etc/supervisor/supervisord.conf"]
