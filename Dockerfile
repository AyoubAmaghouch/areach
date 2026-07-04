# syntax=docker/dockerfile:1
# -----------------------------------------------------------------------------
# Railway-compatible Dockerfile for the AREACH boutique (native PHP + MySQL).
# Uses the official PHP 8.2 Apache image.
# -----------------------------------------------------------------------------

FROM php:8.2-apache

# -----------------------------------------------------------------------------
# System packages + PHP extensions required by the application:
#   - pdo, pdo_mysql : MySQL access via PDO (used throughout the project)
#   - mysqli         : MySQLi access (kept for any legacy code path)
#   - opcache        : production bytecode cache
# Extra libs (libpng, libzip, icu, etc.) are included for common PHP extension
# dependencies; they are harmless if unused and keep the image reusable.
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------------------
# Apache: enable mod_rewrite (the app expects pretty URLs) and allow Apache
# to be started in the foreground ("apache2-foreground" is the image default).
# -----------------------------------------------------------------------------
RUN a2enmod rewrite

# Make the web root match the image's expected document root and allow
# .htaccess overrides (some routes rely on it).
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/>!g' \
              -e 's/AllowOverride None/AllowOverride All/g' \
              /etc/apache2/apache2.conf

# -----------------------------------------------------------------------------
# Copy the application into the Apache document root.
# The project is copied as-is; Docker automatically excludes anything in
# .dockerignore (e.g. .git, vendor/).
# -----------------------------------------------------------------------------
COPY . /var/www/html

# Ensure Apache owns the files (needed for uploads, cache, sessions).
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} +

# -----------------------------------------------------------------------------
# Railway injects a PORT variable to tell your container which port to bind.
# Apache in the official PHP image installs a config that listens on port 80,
# but Railway forwards external traffic to the value of $PORT. We patch the
# installed ports.conf at runtime (in the entrypoint below) so Apache listens
# on the same port Railway expects.
# -----------------------------------------------------------------------------
ENV PORT=80

# Entrypoint: rewrite Apache's Listen directive to match $PORT, then start.
RUN { \
        echo '#!/bin/sh'; \
        echo 'set -e'; \
        echo 'PORT="${PORT:-80}"'; \
        echo 'sed -ri -e "s/^Listen .*/Listen $PORT/" /etc/apache2/ports.conf'; \
        echo 'sed -ri -e "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf'; \
        echo 'exec apache2-foreground'; \
    } > /usr/local/bin/areach-entrypoint \
    && chmod +x /usr/local/bin/areach-entrypoint

EXPOSE 80

ENTRYPOINT ["areach-entrypoint"]
