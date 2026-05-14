FROM php:8.2-fpm-alpine

# Install PHP extensions
RUN apk add --no-cache \
    nginx \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Nginx config
RUN mkdir -p /run/nginx
RUN echo 'server {
    listen 80;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}' > /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Start both php-fpm and nginx
CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
