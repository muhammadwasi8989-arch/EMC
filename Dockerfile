FROM php:8.2-fpm-alpine

# Install nginx and PHP extensions
RUN apk add --no-cache nginx \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Create nginx run directory
RUN mkdir -p /run/nginx

# Write nginx config using a heredoc-safe method
COPY nginx.conf /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
