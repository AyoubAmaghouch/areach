# syntax=docker/dockerfile:1
# -----------------------------------------------------------------------------
# Railway-compatible Dockerfile for the AREACH boutique (native PHP + MySQL).
#
# Fixes: AH00534: apache2: Configuration error: More than one MPM loaded.
#
# The previous Dockerfile pulled in multiple Apache MPM modules. The official
# php:8.2-apache image ships with ONLY mpm_prefork enabled (Apache 2.4 with the
# prefork MPM is the default and what mod_php-style php:8.2-apache expects).
# We keep it that way by never touching MPMs and, as a guard, explicitly
# disabling the other two (worker & event) so they cannot be loaded by any
# inherited conf even if a future base image change enables them.
# -----------------------------------------------------------------------------

FROM php:8.2-apache

# -----------------------------------------------------------------------------
# PHP extensions required by the application:
#   - pdo          : PDO core
#   - pdo_mysql    : PDO driver for MySQL (the project uses PDO throughout)
#   - mysqli       : MySQLi driver (kept for any legacy code path)
#
# Note: the php:8.2-apache image already ships mpm_prefork + mod_php via
# libapache2-mod-mod-php (the SAPI), so pdo_mysql works out of the box once
# the extension is compiled in. No MPM swap is ever performed here.
# -----------------------------------------------------------------------------
RUN docker-php-ext-install pdo pdo_mysql mysqli

# -----------------------------------------------------------------------------
# Apache: enable mod_rewrite (pretty URLs used by the app).
# This command only enables a regular module; it does NOT touch any MPM.
# -----------------------------------------------------------------------------
RUN a2enmod rewrite

# -----------------------------------------------------------------------------
# Guard against the AH00534 regression.
# Disable the non-prefork MPMs if their load symlinks happen to exist in the
# image (they normally don't with prefork default, but defensive). This is the
# only safe, idempotent way to ensure "More than one MPM loaded" cannot happen.
# -----------------------------------------------------------------------------
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true

# -----------------------------------------------------------------------------
# Copy the application into the Apache document root.
# .dockerignore keeps secrets and build artifacts (.git, .env, vendor/, etc.)
# out of the image.
# -----------------------------------------------------------------------------
COPY . /var/www/html/

EXPOSE 80
