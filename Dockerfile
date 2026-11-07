FROM php:8.4-cli

# Herramientas del sistema necesarias para Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensiones de PHP requeridas por Laravel y MySQL
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip bcmath intl pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Usuario con el mismo UID/GID que el del host, para que los archivos
# creados desde el contenedor no queden como root en tu máquina
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN groupadd -g ${HOST_GID} app \
    && useradd -u ${HOST_UID} -g ${HOST_GID} -m app

WORKDIR /var/www/html
USER app

EXPOSE 8000
