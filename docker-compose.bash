#!/usr/bin/env bash

# Debug: Stop on Error
#set -e;
# Debug: Display Command Line
#set -x;

# Git: Untracked Files Removal
#git clean -df; # Help to switch between eZ Platform v2 and v3

# In-Place sed Command
function sedi() {
  ## Regular
  #sed -i -e $*;
  ## MacOS
  sed -i '' -e "$1" $2;
}

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

# Symfony: Logs Removal
rm -rf var/log/*.log;
touch var/log/dev.log;

# Symfony: Cache Removal
rm -rf var/cache/dev/;
mkdir -p var/cache/dev;

# Symfony: Remove bin/ symlinks
find bin/ -type l -exec unlink {} \; ; # Remove bin/ symlinks

# Encore: Remove generated config files
rm -f var/encore/*config*.js;

# Docker: Parse Containers Options

## Default Options Values
cache='redis';
search='solr';
session='redis';
dynamic_session=0;

args=`getopt --options=hc:s:u:d $*`;
set -- $args
for i
do
  case "$i"
  in
    -h)
      echo "Usage:";
      echo "$0 [-c <filesystem|redis|memcached>] [-s <legacy|solr|elasticsearch|fallback>] [-u <filesystem|redis|memcached>] [-d]";
      echo "-c: Cache Pool (default: redis)";
      echo "-s: Search Engine (default: solr)";
      echo "-s: User Sessions (default: redis); Not yet implemented";
      echo "-d: Dynamic Session Handler (default: false); Not yet implemented";
      ;;
    -c)
      cache="$2";
      shift; shift;
      ;;
    -s)
      search="$2";
      shift; shift;
      ;;
    -u)
      echo "Session Handler: Not yet implemented";
      #session="$2";
      shift; shift;
      ;;
    -d)
      echo "Dynamic Session Handler: Not yet implemented";
      #dynamic_session=1;
      shift;
      ;;
  esac
done

case "$cache"
in
  'filesystem')
    CACHE_POOL=cache.tagaware.filesystem;
    CACHE_DSN=localhost;
    cache_container='';
    ;;
  'redis')
    CACHE_POOL=cache.redis;
    CACHE_DSN=redis;
    cache_container='redis';
    ;;
  'memcached')
    CACHE_POOL=cache.memcached;
    CACHE_DSN=memcached;
    cache_container='memcached';
    ;;
esac

case "$search"
in
  'legacy')
    SEARCH_ENGINE=legacy;
    search_container='';
    ;;
  'solr')
    SEARCH_ENGINE=solr;
    search_container='solr';
    ;;
  'elasticsearch')
    SEARCH_ENGINE=elasticsearch;
    search_container='elasticsearch';
    ;;
  'fallback')
    SEARCH_ENGINE=fallback;
    search_container='solr elasticsearch';
    ;;
esac

case "$session"
in
  'filesystem')
    SESSION_HANDLER_ID=session.handler.native_file
    SESSION_SAVE_PATH=%kernel.project_dir%/var/sessions/%kernel.environment%
    session_container='';
    ;;
  'redis')
    SESSION_HANDLER_ID=ezplatform.core.session.handler.native_redis
    SESSION_SAVE_PATH=tcp://redis:6379
    session_container='redis';
    ;;
  'memcached')
    SESSION_HANDLER_ID=ad.session.handler.native_memcached
    SESSION_SAVE_PATH=memcached:11211
    session_container='memcached';
    ;;
esac

if [[ ! -f .env.local ]]; then
  cp -v .env.pattern .env.local;
fi

# Docker/eZ: Set Environment

sedi "s/CACHE_POOL=.*/CACHE_POOL=$CACHE_POOL/" .env.local;
sedi "s/CACHE_DSN=.*/CACHE_DSN=$CACHE_DSN/" .env.local;

sedi "s/SEARCH_ENGINE=.*/SEARCH_ENGINE=$SEARCH_ENGINE/" .env.local;

if [[ 0 == $dynamic_session ]]; then
  echo "Session Handler set in PHP";
  sedi "s/^SESSION_HANDLER_ID=.*/#SESSION_HANDLER_ID=/" .env;
  sedi "s/^SESSION_HANDLER_ID=.*/#SESSION_HANDLER_ID=/" .env.local;
  sedi "s/ezplatform.session.handler_id: .*/ezplatform.session.handler_id: ~/" config/packages/ezplatform.yaml;
  # TODO: PHP-FPM
elif [[ 1 == $dynamic_session ]]; then
  echo "Session Handler set dynamically";
  sedi "s/^#SESSION_HANDLER_ID=/SESSION_HANDLER_ID=/" .env.local;
  sedi "s/^SESSION_HANDLER_ID=.*/SESSION_HANDLER_ID=$SESSION_HANDLER_ID/" .env.local;
  sedi "s/^SESSION_SAVE_PATH=.*/SESSION_SAVE_PATH=$SESSION_SAVE_PATH/" .env.local;
  sedi "s/ezplatform.session.save_path: .*/ezplatform.session.save_path: '$SESSION_SAVE_PATH'/" config/packages/ezplatform.yaml;
fi

# Docker: docker-compose settings
sedi "s/^COMPOSE_FILE=/#COMPOSE_FILE=/" .env;
sedi "s/^COMPOSE_PROJECT_NAME=/#COMPOSE_FILE=/" .env;

# Docker: Containers Cluster Build
container_list="varnish apache mariadb $cache_container $search_container $session_container";
container_list=`echo "$container_list" | awk '{for (i=1;i<=NF;i++) if (!a[$i]++) printf("%s%s",$i,FS)}{printf("\n")}'`;
#docker-compose stop;
docker-compose up --build --detach $container_list;

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
sedi "s/DATABASE_VERSION=.*/DATABASE_VERSION=mariadb-$MARIADB_VERSION/" .env;
sedi "s/DATABASE_VERSION=.*/DATABASE_VERSION=mariadb-$MARIADB_VERSION/" .env.local;

# Apache: Composer Scripts' Timeout
docker-compose exec --user www-data apache composer config --global process-timeout 0;

# Apache: eZ Platform Install
docker-compose exec mariadb mysql -proot -e "DROP DATABASE IF EXISTS ezplatform;";
docker-compose exec --user www-data apache rm -rf public/var/*; # Clean public/var/*/storage/ as the DB is reset.
docker-compose exec redis redis-cli FLUSHALL;
docker-compose exec --user www-data apache composer ezplatform-install;

# Logs Follow-up
#docker-compose logs --follow;
