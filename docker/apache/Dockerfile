FROM debian:10

LABEL maintainer="adrien.dupuis@ez.no"
LABEL description="Debian “Buster” 10 with Apache 2.4, Cron, PHP-FPM (PHP 7.3, Redis as session handler, Xdebug), Composer and Yarn for eZ Platform usage"

ARG symfony_env=dev
ENV SYMFONY_ENV=$symfony_env

# Debian: Packages
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get -qq update \
    && apt-get -qq install -y \
        cron \
        curl \
        git \
        zip unzip \
        # Apache
        apache2 \
        # PHP
        php \
        php-cli \
        php-fpm \
        php-mysql \
        php-xml \
        php-intl \
        php-curl \
        php-mbstring \
        php-zip \
        php-redis \
        # GD
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        php-gd \
        # PECL
        php-dev \
        php-pear

# PHP: Xdebug
RUN pecl install xdebug \
    && echo "zend_extension=$(find /usr/lib/php/ -name xdebug.so)" > /etc/php/7.3/mods-available/xdebug.ini \
    && ln -s /etc/php/7.3/mods-available/xdebug.ini /etc/php/7.3/cli/conf.d/00-xdebug.ini \
    && ln -s /etc/php/7.3/mods-available/xdebug.ini /etc/php/7.3/fpm/conf.d/00-xdebug.ini

# PHP-FPM: Configuration
RUN sed -i "s/max_execution_time = 30/max_execution_time = 120/" /etc/php/7.3/fpm/php.ini
RUN sed -i "s/pm.max_children = 5/pm.max_children = 20/" /etc/php/7.3/fpm/pool.d/www.conf \
    && sed -i "s/;php_admin_value[error_log] = .*/php_admin_value[error_log] = \/var\/log\/php7.3-fpm.\$pool.error.log/" /etc/php/7.3/fpm/pool.d/www.conf \
    && sed -i "s/;access\.log = .*/access.log = \/var\/log\/php7.3-fpm.\$pool.access.log/" /etc/php/7.3/fpm/pool.d/www.conf \
    && sed -i "s/;access\./access./" /etc/php/7.3/fpm/pool.d/www.conf
# PHP-FPM: UDS Directory & Log Creation
RUN mkdir /run/php && touch /var/log/php7.3-fpm.log
# PHP-FPM: Redis as session handler
RUN echo "\n; Redis as session handler\nphp_value[session.save_handler] = redis\nphp_value[session.save_path] = \"tcp://redis:6379\"" >> /etc/php/7.3/fpm/pool.d/www.conf
# Apache: FastCGI Module & PHP-FPM Configuration
RUN a2enmod proxy_fcgi && a2enconf php7.3-fpm

# Apache: Logs Conf
COPY logs.conf /etc/apache2/conf-available/
RUN a2enconf logs

# Apache: Virtual Host Modules
RUN a2enmod rewrite setenvif
# Apache: Global ServerName (avoid AH00558)
RUN sed -i -e '1 i\ServerName apache' /etc/apache2/apache2.conf
# Apache: Virtual Host Setting
COPY vhost.conf /etc/apache2/sites-available/
RUN a2ensite vhost && a2dissite 000-default.conf

# Apache: Cron
COPY crontab /tmp/crontab
RUN crontab -u www-data /tmp/crontab

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# NodeJS
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt-get install -y nodejs
# Yarn
RUN npm install -g yarn

RUN chown www-data:www-data /var/www/

WORKDIR /var/www/ez
EXPOSE 80

# Cron, Apache, PHP-FPM & Logs
#TODO: Should be an array
CMD service cron restart && /usr/sbin/apache2ctl restart && /etc/init.d/php7.3-fpm restart \
    # Send several log followings to output for `docker(-compose) logs` command
    && tail \
        -F /var/log/apache2/error.log \
        -F /var/log/apache2/other_vhosts_access.log \
        -F /var/log/php7.3-fpm.log \
        -F /var/www/ez/var/logs/dev.log \
    ;
