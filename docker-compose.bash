#!/usr/bin/env bash

# Debug: Stop on Error
#set -e;
# Debug: Display Command Line
#set -x;

# Git: Untracked Files Removal
#git clean -df; # Help to switch between eZ Platform v2 and v3

# Docker: Containers Cluster Build (except Solr which needs vendor/ezsystems/ezplatform-solr-search-engine/)
docker-compose up --build --detach varnish apache redis mariadb;

# eZ Platform: Cache and Logs Removal
rm -rf var/cache/dev/ var/logs/*.log;
mkdir -p var/cache/dev; touch var/logs/dev.log;
docker-compose exec apache chown www-data -R var/cache/;
docker-compose exec apache chmod g+w -R var/cache/;

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
sed -i '' -e "s/DATABASE_VERSION=mariadb-.*/DATABASE_VERSION=mariadb-$MARIADB_VERSION/" .env;

# Apache: Composer Authentication
if [ ! -f auth.json ]; then
  echo -n "eZ Platform Enterprise Edition Installation Key: "; read INSTALLATION_KEY;
  echo -n "eZ Platform Enterprise Edition Token Password: "; read TOKEN_PASSWORD;
  docker-compose exec --user www-data apache composer config http-basic.updates.ez.no ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
fi;

# Apache: Composer Scripts' Timeout
docker-compose exec --user www-data apache composer config --global process-timeout 0;

# Apache: Composer Install
find bin/ -type l -exec unlink {} \; ; # Remove bin/ symlinks
rm -f var/encore/*config*.js; # Remove Webpack Encore generated config files
docker-compose exec --user www-data apache composer install --no-interaction;

# Solr for eZ Commerce
docker-compose exec --user www-data apache bash install-solr.sh 8983;
rm solr-7.7.3.tgz;

# Apache: eZ Platform Install (needs Solr)
docker-compose exec mariadb mysql -proot -e "DROP DATABASE IF EXISTS ezplatform;";
docker-compose exec --user www-data apache rm -rf public/var/*; # Clean public/var/*/storage/ as the DB is reset.
docker-compose exec redis redis-cli FLUSHALL;
docker-compose exec --user www-data apache composer ezcommerce-install;

# Logs Follow-up
#docker-compose logs --follow;
