#!/usr/bin/env bash

# Debug: Stop on Error
#set -e;
# Debug: Display Command Line
#set -x;

# Git: Untracked Files Removal
git clean -df; # Help to switch between eZ Platform v2 and v3

# eZ Platform: Cache and Logs Removal
rm -rf var/cache/dev/ var/log/*.log;
touch var/log/dev.log;

# Docker: Containers Cluster Build (except Solr which needs vendor/ezsystems/ezplatform-solr-search-engine/)
docker-compose --env-file=.env.local up --build --detach varnish apache redis mariadb;

# MariaDB: Server Wait & Version Fetch
GET_MARIADB_VERSION_CMD="docker-compose --env-file=.env.local exec mariadb mysql -proot -BNe 'SELECT VERSION();' | cut -d '-' -f 1 | head -n 1;";
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
  docker-compose --env-file=.env.local exec --user www-data apache composer config http-basic.updates.ez.no ${INSTALLATION_KEY} ${TOKEN_PASSWORD};
fi;

# Apache: Composer Scripts' Timeout
docker-compose --env-file=.env.local exec --user www-data apache composer config --global process-timeout 0;

# Apache: Composer Install
find bin/ -type l -exec unlink {} \; ; # Remove bin/ symlinks
rm -f var/encore/*config*.js; # Remove Webpack Encore generated config files
docker-compose --env-file=.env.local exec --user www-data apache composer install --no-interaction;

# Solr: Docker Container Build (needs vendor/ezsystems/ezplatform-solr-search-engine/)
cp -r ./vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr ./docker/solr/conf;
docker-compose --env-file=.env.local up --build --detach solr;
rm -rf ./docker/solr/conf;

# Apache: eZ Platform Install (needs Solr)
docker-compose --env-file=.env.local exec mariadb mysql -proot -e "DROP DATABASE IF EXISTS ezplatform;";
docker-compose --env-file=.env.local exec --user www-data apache rm -rf public/var/*; # Clean public/var/*/storage/ as the DB is reset.
docker-compose --env-file=.env.local exec redis redis-cli FLUSHALL;
docker-compose --env-file=.env.local exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose --env-file=.env.local logs --follow;
