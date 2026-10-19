FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends gosu git unzip libzip-dev libsqlite3-dev libicu-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite zip intl pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid 1000 app \
    && useradd --uid 1000 --gid app --create-home --shell /usr/sbin/nologin app

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8085

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8085"]
