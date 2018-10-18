FROM php:7.2-alpine3.8

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/* && \
    curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer && \
    composer global require "hirak/prestissimo:0.3.8"
