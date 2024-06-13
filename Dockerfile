FROM php:8.1

RUN apt-get update -y && apt-get install -y zip unzip git cron libzip-dev zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev clamav clamav-daemon libpq-dev supervisor
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.3.3
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
COPY . /var/www

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip
RUN composer install --optimize-autoloader --ignore-platform-reqs
RUN php artisan key:generate
RUN php artisan config:clear

RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
COPY --chown=www:www . /var/www

# Copy Supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start Supervisor
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

EXPOSE 9000
