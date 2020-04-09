#!/usr/bin/env bash

# Debug: Stop on Error
#set -e;
# Debug: Display Command Line
#set -x;

# Git: Untracked Files Removal
#git clean -df;

# eZ Platform: Cache and Logs Removal
rm -rf var/cache/dev/ var/logs/*.log;
touch var/logs/dev.log;

# Docker: Containers Cluster Build (except Solr which needs vendor/ezsystems/ezplatform-solr-search-engine/)
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

# Apache: Doctrine Configuration
sed -i '' -e "s/server_version: .*$/server_version: mariadb-$MARIADB_VERSION/" config/packages/doctrine.yaml;

# Apache: Composer Authentication
if [ ! -f auth.json ]; then
  echo -n "eZ Platform Enterprise Edition Installation Key: "; read INSTALLATION_KEY;
  echo -n "eZ Platform Enterprise Edition Token Password: "; read TOKEN_PASSWORD;
  docker-compose exec --user www-data apache composer config http-basic.updates.ez.no ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
fi;

# Apache: Composer Scripts' Timeout
docker-compose exec --user www-data apache composer config --global process-timeout 0;

# Apache: Composer Install
docker-compose exec apache find bin/ -type l -exec unlink {} \; ; # Remove bin/ symlinks
rm -f var/encore/*config*.js; # Remove Webpack Encore generated config files
docker-compose exec --user www-data apache composer install --no-interaction;

# Solr: Docker Container Build (needs vendor/ezsystems/ezplatform-solr-search-engine/)
cp -r ./vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr ./docker/solr/conf;
docker-compose up --build --detach solr;
rm -rf ./docker/solr/conf;

# Apache: eZ Platform Install (needs Solr)
docker-compose exec mariadb mysql -proot -e "DROP DATABASE IF EXISTS ezplatform;";
docker-compose exec --user www-data apache rm -rf public/var/*; # Clean public/var/*/storage/ as the DB is reset.
docker-compose exec redis redis-cli FLUSHALL;
docker-compose exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose logs --follow;
