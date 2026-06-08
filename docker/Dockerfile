# Base image is pinned to a supported PHP release. The SDK itself targets PHP 7.4+
# (see composer.json) — merchants on older hosts can still install via Composer,
# but this dev/CI image uses a security-supported runtime.
FROM php:8.3-cli-alpine

RUN apk add --no-cache \
        git \
        unzip \
        libzip-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Cache composer deps if composer.json is present at build time.
COPY composer.json composer.lock* /app/
RUN if [ -f composer.json ]; then composer install --no-interaction --no-progress --prefer-dist; fi

COPY . /app

ENV PATH="/app/vendor/bin:${PATH}"

CMD ["php", "-v"]
