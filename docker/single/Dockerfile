FROM debian:10

LABEL maintainer="adrien.dupuis@ibexa.co"
LABEL description="Ibexa DXP 3.3"

# Usage:
# docker build . -f docker/single/Dockerfile -t ibexa-single && docker run --name ibexa-single --detach --publish 8055:8080 ibexa-single;
# docker exec -ti ibexa-single bash;
# docker rm -f ibexa-single;

#ARG installation-key
#ARG token-password

RUN apt-get -qq update \
  && apt-get -qq install -y \
    curl \
    git \
    zip unzip \
    mariadb-server \
    php-cli \
    #php-fpm \
    php-mysql \
    php-xml \
    php-mbstring \
    php-json \
    php-intl \
    php-curl \
    #php-pear \
    php-gd \
    #php-imagick \
;

RUN service mysql start

# Composer 2
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
# NodeJS 12
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash - \
  && apt-get install -y nodejs
# Yarn
RUN npm install -g yarn

#RUN composer config --global http-basic.updates.ibexa.co $installation-key $token-password
COPY auth.json /root/.composer/auth.json

RUN mkdir /var/www
WORKDIR /var/www

RUN composer create-project ibexa/content-skeleton:^3.3 .

RUN echo "DATABASE_URL=mysql://root@localhost:3306/ibexa" > .env.local

RUN service mysql start \
  && php bin/console ibexa:install \
  && php bin/console ibexa:graphql:generate-schema \
  && composer run post-install-cmd

EXPOSE 8080
CMD service mysql start && php -S 0.0.0.0:8080 -t public
