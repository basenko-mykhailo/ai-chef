FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        git \
        unzip \
        bash \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        autoconf \
        g++ \
        make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        bcmath \
        mbstring \
        gd \
        opcache \
    && apk del autoconf g++ make

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} -S app \
    && adduser  -u ${UID} -S app -G app \
    && mkdir -p /var/www/html \
    && chown -R app:app /var/www/html /var/log

WORKDIR /var/www/html
USER app

EXPOSE 9000
CMD ["php-fpm", "-F"]
