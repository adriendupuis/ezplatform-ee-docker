#!/usr/bin/env bash

# Stop on error
set -e;

# Persistence Cache and Session Handler
CACHE_AND_SESSION_HANDLER=memcached

while (( "$#" )); do
  case "$1" in
    -h|--help)
      echo "Usage: $0 [--cache-and-session-handler=(redis|memcached)]";
      echo "";
      echo "\t-c,\t--cache-and-session-handler\tCache and session handler; redis, memcached or tagaware.filesystem (default)"
      exit 0
      ;;
    -c=*|--cache-and-session-handler=*)
      CACHE_AND_SESSION_HANDLER="${1#*=}"
      shift 1
      ;;
    -c|--cache-and-session-handler)
      CACHE_AND_SESSION_HANDLER=$2
      shift 2
      ;;
    *)
      echo "Error: Unsupported argument '$1'" >&2
      exit 1
      ;;
  esac
done

# eZ Platform logs removal
rm -f var/logs/*.log;

# Docker Containers Cluster Build (except Solr which needs vendor/ezsystems/ezplatform-solr-search-engine/)
docker-compose up --build --detach varnish mariadb $CACHE_AND_SESSION_HANDLER;
docker-compose build --build-arg session_save_handler=$CACHE_AND_SESSION_HANDLER apache;

# MariaDB: Server Wait & Version Fetch
GET_MARIADB_VERSION_CMD="docker-compose exec mariadb mysql -proot -BNe 'SELECT VERSION();' | cut -d '-' -f 1 | head -n 1;";
MARIADB_VERSION=`eval $GET_MARIADB_VERSION_CMD`;
while [ -n "`echo $MARIADB_VERSION | grep 'ERROR';`" ]; do
  echo 'Waiting for server inside mariadb container...';
  sleep 3;
  MARIADB_VERSION=`eval $GET_MARIADB_VERSION_CMD`;
done;
echo "MariaDB version: $MARIADB_VERSION";

UNUSED_CONTAINER_LIST="memcached redis";

# Apache: Symfony parameters.yml
cp docker/apache/parameters.yml app/config/parameters.yml;
sed -i '' -e "s/MARIADB_VERSION/$MARIADB_VERSION/" app/config/parameters.yml;
if [[ 'redis' == "$CACHE_AND_SESSION_HANDLER" ]]; then
  sed -i '' -e "s/CACHE_AND_SESSION_HOST/redis/" app/config/parameters.yml;
  sed -i '' -e "s/CACHE_AND_SESSION_PORT/6379/" app/config/parameters.yml;
  UNUSED_CONTAINER_LIST="memcached";
elif [[ 'memcached' == "$CACHE_AND_SESSION_HANDLER" ]]; then
#  sed -i '' -e "s/CACHE_AND_SESSION_HOST/memcached/" app/config/parameters.yml;
#  sed -i '' -e "s/CACHE_AND_SESSION_PORT/11211/" app/config/parameters.yml;
  UNUSED_CONTAINER_LIST="redis";
else # tagaware.filesystem
    sed -i '' -e "s/imports://" app/config/parameters.yml;
    sed -i '' -e "s/.*resource: cache_pool/.*//" app/config/parameters.yml;
    sed -i '' -e "s/env(CACHE_.*): .*//g" app/config/parameters.yml;
    sed -i '' -e "s/env(SESSION_.*): .*//g" app/config/parameters.yml;
    sed -i '' -e "s/ezplatform\.session\..*: .*//g" app/config/parameters.yml;
fi

# May remove unused Docker containers
docker-compose rm -sv $UNUSED_CONTAINER_LIST;

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
docker-compose exec --user www-data apache rm -rf web/var/*;
docker-compose exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose logs --follow;
