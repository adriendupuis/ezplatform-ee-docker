FROM debian:10
# Debian "Buster" 10 comes w/ PHP 7.3
MAINTAINER adrien.dupuis@ez.no

ENV SYMFONY_ENV=dev

EXPOSE 80

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
        php-gd

# PHP-FPM: UDS Directory & Log Creation
RUN mkdir /run/php && touch /var/log/php7.3-fpm.log
# PHP-FPM: Redis as session handler
RUN echo "\n; Redis as session handler\nphp_value[session.save_handler] = redis\nphp_value[session.save_path] = \"tcp://redis:6379\"" >> /etc/php/7.3/fpm/pool.d/www.conf
# Apache: FastCGI Module & PHP-FPM Configuration
RUN a2enmod proxy_fcgi setenvif rewrite \
    && a2enconf php7.3-fpm

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

# Cron, Apache, PHP-FPM & Logs
#TODO: Should be an array
CMD service cron restart && /usr/sbin/apache2ctl restart && /etc/init.d/php7.3-fpm restart \
    # Send several logs following to output for `docker(-compose) logs` command
    && tail \
        -F /var/log/apache2/error.log \
        -F /var/log/apache2/access.log \
        -F /var/log/php7.3-fpm.log \
        -F /var/www/var/logs/dev.log \
    ;
