FROM php:8.2-apache

# Install MySQL PDO driver (needed for login/register with a real database)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite (clean URLs, .htaccess rules) and mod_headers /
# mod_expires (Cache-Control rules in .htaccess).
RUN a2enmod rewrite headers expires

# Force prefork as the only MPM. mod_php requires prefork; making it explicit
# keeps Apache from refusing to start with "More than one MPM loaded".
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Allow the project ErrorDocument rule in .htaccess to handle missing routes.
COPY docker/najminet.conf /etc/apache2/conf-available/najminet.conf
RUN a2enconf najminet

# Log errors instead of displaying them to visitors.
COPY docker/php.ini /usr/local/etc/php/conf.d/najminet.ini

# PHP dependencies. vendor/ is not in git: composer.lock pins the exact
# versions and Composer installs them here. unzip lets it extract the
# dist archives.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

# Ship the application source in the image. docker-compose bind-mounts the
# project over it (`.:/var/www/html/`) so local edits apply without a rebuild.
# .dockerignore keeps vendor/ out, so the copy above is not overwritten.
COPY . /var/www/html/

# Uploaded documents are stored outside the web root, in the "documents"
# volume (see docker-compose.yml). Apache's user must own the directory.
RUN mkdir -p /var/www/storage/documents \
    && chown -R www-data:www-data /var/www/storage

# No CMD: the base image's apache2-foreground serves on port 80.
