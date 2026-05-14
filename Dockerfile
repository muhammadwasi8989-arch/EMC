FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx bash \
    && docker-php-ext-install mysqli pdo pdo_mysql

RUN mkdir -p /run/nginx /var/log/nginx

COPY nginx.conf /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["/bin/sh", "-c", "php-fpm -D && sleep 2 && exec nginx -g 'daemon off;'"]
