#!/usr/bin/env bash

# Debug: Stop on Error
#set -e;
# Debug: Display Command Line
#set -x;

# Git: Untracked Files Removal
#git clean -df; # Help to switch between eZ Platform v2 and v3

# Apache: Composer Authentication
if [ ! -f auth.json ]; then
  echo -n "eZ Platform Enterprise Edition Installation Key: "; read INSTALLATION_KEY;
  echo -n "eZ Platform Enterprise Edition Token Password: "; read TOKEN_PASSWORD;
  composer config http-basic.updates.ez.no ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
  composer config http-basic.updates.ibexa.co ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
fi;

# Symfony/eZ/Composer: Install dependencies
composer install --no-interaction --no-scripts;

# Solr: Copy config to build folder
cp -r ./vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr ./docker/solr/conf;

# Symfony: Cache and Logs Removal
rm -rf var/cache/dev/ var/log/*.log var/encore/*config*.js;
mkdir -p var/cache/dev; touch var/log/dev.log;

# Symfony: Remove bin/ symlinks
find bin/ -type l -exec unlink {} \; ; #
rm -f ; # Remove Webpack Encore generated config files

# Docker: Containers Cluster Build
docker-compose up --build --detach;

# Solr: Clean-up build folder
rm -rf ./docker/solr/conf;

# Apache: Add write rights to var folders
docker-compose exec apache chown www-data -R var/ public/var/;
docker-compose exec apache chmod g+w -R var/ public/var/;

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

# Apache: Composer Scripts' Timeout
docker-compose exec --user www-data apache composer config --global process-timeout 0;

# Apache: eZ Platform Install
docker-compose exec mariadb mysql -proot -e "DROP DATABASE IF EXISTS ezplatform;";
docker-compose exec --user www-data apache rm -rf public/var/*; # Clean public/var/*/storage/ as the DB is reset.
docker-compose exec redis redis-cli FLUSHALL;
docker-compose exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose logs --follow;
