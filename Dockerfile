FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-zip \
    php8.1-mbstring \
    php8.1-xml \
    libapache2-mod-php8.1 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork php8.1 rewrite

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf \
    && echo "Listen 80" > /etc/apache2/ports.conf

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["/usr/sbin/apache2", "-D", "FOREGROUND"]
