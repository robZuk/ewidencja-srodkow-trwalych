# Development image: PHP 8.4 CLI + required extensions + Node 22 + Composer.
# Used by docker-compose for a one-command local setup.
FROM php:8.4-cli-bookworm

# System libraries needed to build the PHP extensions below.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        ca-certificates \
        curl \
        gnupg \
        default-mysql-client \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath exif \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (Vite dev server + asset build).
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer binary from the official image.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Writable caches for the non-root host user that runs the container.
ENV COMPOSER_HOME=/tmp/composer \
    npm_config_cache=/tmp/npm

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000 5173

ENTRYPOINT ["entrypoint.sh"]
CMD ["serve"]
