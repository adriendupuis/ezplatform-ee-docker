#!/usr/bin/env bash

# Stop on Error
#set -e;

# eZ Platform Logs Removal
rm -f var/logs/*.log;

# Docker Containers Cluster Build (except Solr which needs vendor/ezsystems/ezplatform-solr-search-engine/)
docker-compose up --build --detach varnish apache redis mariadb;

# MariaDB: Server Wait & Version Fetch
GET_MARIADB_VERSION_CMD="docker-compose exec mariadb mysql -proot -BNe 'SELECT VERSION();' | cut -d '-' -f 1 | head -n 1;";
MARIADB_VERSION=`eval $GET_MARIADB_VERSION_CMD`;
while [ -n "`echo $MARIADB_VERSION | grep 'ERROR';`" ]; do
  echo 'Waiting for server inside mariadb container...';
  sleep 3;
  MARIADB_VERSION=`eval $GET_MARIADB_VERSION_CMD`;
done;
echo "MariaDB version: $MARIADB_VERSION";

# Apache: Symfony parameters.yml
cp docker/apache/parameters.yml app/config/parameters.yml;
sed -i '' -e "s/MARIADB_VERSION/$MARIADB_VERSION/" app/config/parameters.yml;

# Apache: Composer Authentication
if [ ! -f auth.json ]; then
  echo -n "eZ Platform Enterprise Edition Installation Key: "; read INSTALLATION_KEY;
  echo -n "eZ Platform Enterprise Edition Token Password: "; read TOKEN_PASSWORD;
  docker-compose exec --user www-data apache composer config http-basic.updates.ez.no ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
fi;

# Apache: Composer Scripts' Timeout
docker-compose exec --user www-data apache composer config --global process-timeout 0;

# Apache: Composer Install
docker-compose exec --user www-data apache composer install --no-interaction;

# Solr: Docker Container Build (needs vendor/ezsystems/ezplatform-solr-search-engine/)
docker-compose up --build --detach solr;

# Apache: eZ Platform Install (needs Solr)
docker-compose exec --user www-data apache rm -rf web/var/*; # Clean web/var/ as the DB was reset.
docker-compose exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose logs --follow;
