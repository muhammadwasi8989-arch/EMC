#!/bin/sh
php-fpm -D
sleep 1
exec nginx -g 'daemon off;'
