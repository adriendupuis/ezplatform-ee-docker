Docker Container Cluster
========================

Introduction
------------

Use a Docker containers cluster to have a [typical architecture for eZ Platform](https://doc.ezplatform.com/en/2.5/guide/clustering/) including the following elements.
* HTTP Server:
  - Debian “Buster” 10
  - Apache 2.4
  - PHP 7.3 ([by default](https://packages.debian.org/buster/php/php))
  - [PHP FastCGI Process Manager](https://www.php.net/manual/install.fpm.php) (PHP-FPM) with [Unix domain socket](https://en.wikipedia.org/wiki/Unix_domain_socket) (UDS)
  - [eZ Platform Enterprise Edition](https://ez.no/Products/eZ-Platform-Enterprise-Edition) 2.5
* Reverse Proxy Cache Server:
  - Varnish 6.0
  - Varnish Modules 0.15
* [Persistence Cache](https://doc.ezplatform.com/en/2.5/guide/persistence_cache/) and [Session Handling](https://doc.ezplatform.com/en/2.5/guide/sessions/) Servers:
  - Redis 3.2
* DataBase Server:
  - MariaDB 10.1
* Search Engine:
  - Solr 6.6

Quick Start
-----------

* Run `./docker-compose.bash;` (you may need your eZ Platform Enterprise Edition credentials)
* Access to http://localhost:8080/

About
-----

* Follow [eZ Platform 2.5 Requirements](https://doc.ezplatform.com/en/2.5/getting_started/requirements/) as much as possible.
* Add as less configuration as possible to [original distribution](https://github.com/ezsystems/ezplatform-ee/tree/v2.5.9).

URLs and Command Lines
----------------------

### Usefull URLs
* eZ Home page through Varnish: http://localhost:8080/
* eZ Admin through Varnish: http://localhost:8080/admin
  - Username: *admin*
  - Password: *publish*
  * [Admin / System Info](http://localhost:8080/admin/systeminfo)
* Change port from 8080 to 8000 to access directly to Apache avoiding Varnish
* Solr Admin: http://localhost:8983/solr/#/collection1

### Usefull Commands
* Docker
  - Get Docker version: `docker --version;`
  - Get Docker Compose version: `docker-compose --version;`
  - Remove everything about containers: `docker rm --force --volumes $(docker ps --all --quiet) && docker system prune --force --all;`
* Docker Containers Cluster
  - Get containers status: `docker-compose ps --all;`
  - Follow several logs from the cluster: `docker-compose logs -f;`
  - Follow containers stats: `docker stats;`
  - Restart every containers: `docker-compose restart;`
  - Stop every containers: `docker-compose stop;`
* Apache & Cron
  - Get OS release: `docker-compose exec apache cat /etc/os-release;`
  - Get Apache version: `docker-compose exec apache apache2ctl -v;`
  - Get Apache modules: `docker-compose exec apache apache2ctl -M;`
  - Check Apache status: `docker-compose exec apache service apache2 status;`
  - Follow Apache error.log: `docker-compose exec apache tail -f /var/log/apache2/error.log;`
  - Check Cron status: `docker-compose exec apache service cron status;`
  - Get Crontab content: `docker-compose exec apache crontab -u www-data -l;`
* PHP & PHP-FPM
  - Get PHP version: `docker-compose exec apache php -v;`
  - Get PHP modules: `docker-compose exec apache php -m;`
  - Check PHP-FPM status: `docker-compose exec apache service php7.3-fpm status;`
  - Follow PHP-FPM log: `docker-compose exec apache tail -f /var/log/php7.3-fpm.log;`
  - Follow eZ Platform log: `docker-compose exec apache tail -f var/logs/dev.log;`
* Symfony & eZ Platform
  - Get Git version: `docker-compose exec apache git --version;`
  - Get Composer version: `docker-compose exec apache composer --version;`
  - Get Yarn version: `docker-compose exec apache yarn --version;`
  - Get Symfony version: `docker-compose exec apache php bin/console --version;`
  - See a bundle info: `docker-compose exec apache composer show vendor-name/bundle-name;`
    - See eZ Kernel bundle info: `docker-compose exec apache composer show ezsystems/ezpublish-kernel;`
    - See eZ Symfony Tools info: `docker-compose exec apache composer show ezsystems/symfony-tools;`
    - See eZ HTTP Cache bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-http-cache;`
    - See eZ Solr SE bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-solr-search-engine;`
  - Clear eZ caches: `docker-compose exec --user www-data apache sh -c "php bin/console cache:clear; php bin/console cache:pool:clear cache.redis;";` 
  - Open a shell into container as root: `docker-compose exec apache bash;`
  - Open a shell into container as www-data: `docker-compose exec --user www-data apache bash;`
* Varnish
  - Get OS release: `docker-compose exec varnish cat /etc/os-release;`
  - Get Varnish version: `docker-compose exec varnish varnishd -V;`
  - Follow [Varnish logs](https://varnish-cache.org/docs/6.0/reference/varnishlog.html): `docker-compose exec varnish varnishlog;`
    - Follow requests for an URL: `docker-compose exec varnish varnishlog -q 'ReqURL eq "/the/url/to/follow"';`
    - Follow PURGE requests: `docker-compose exec varnish varnishlog -q 'ReqMethod eq PURGE';`
  - Follow [Varnish cache statistics](https://varnish-cache.org/docs/6.0/reference/varnishstat.html): `docker-compose exec varnish varnishstat;`
  - Restart Varnish (remove all cache): `docker-compose restart varnish;`
  - [Bans](https://varnish-cache.org/docs/trunk/users-guide/purging.html#bans)
    - Ban an URL: `docker-compose exec varnish varnishadm ban req.url '==' '/the/url/to/ban';`
    - Ban built CSS and JS: `docker-compose exec varnish varnishadm ban req.url '~' '^/assets/.*\\.(cs|j)s$';`
    - Get the ban list: `docker-compose exec varnish varnishadm ban.list;`
  - Open a shell into container: `docker-compose exec varnish bash;`
* Apache → Varnish
  - See [`render_esi` `esi:include` tags](https://symfony.com/doc/3.4/http_cache/esi.html): `curl --silent --header "Surrogate-Capability: abc=ESI/1.0" http://localhost:8000/the/url/to/test | grep esi:include;`
  - Purge an URL: `docker-compose exec --user www-data apache curl --request PURGE --header 'Host: localhost:8080' http://varnish/the/url/to/purge;`
  - Soft purge content(s) by ID: `docker-compose exec --user www-data apache curl -X PURGE -H 'Host: localhost:8080' -H 'key: <TYPE><ID>' http://varnish;`
    - [(x)key types](https://github.com/ezsystems/ezplatform-http-cache/blob/v1.0.0/docs/using_tags.md#tags-in-use-in-this-bundle):
      - `c`: ***c***ontent id
      - `l`: ***l***ocation id
      - `p`: (***p***ath) ancestor location id
      - `pl`: ***p***arent ***l***ocation id
      - `ct`: ***c***ontent ***t***ype id 
* Redis
  - Get OS release: `docker-compose exec redis cat /etc/os-release;`
  - Get server info: `docker-compose exec redis redis-cli INFO Server;`
  - Get all info: `docker-compose exec redis redis-cli INFO;`
  - Follow stats: `docker-compose exec redis redis-cli --stat;`
  - Monitor request: `docker-compose exec redis redis-cli MONITOR;`
  - Delete all keys: `docker-compose exec redis redis-cli FLUSHALL;`
  - Open Redis CLI: `docker-compose exec redis redis-cli;`
  - Open a shell into container: `docker-compose exec redis bash;`
* MariaDB
  - Get OS release: `docker-compose exec mariadb cat /etc/os-release;`
  - Get MariaDB version: `docker-compose exec mariadb mysql --password=root --batch --skip-column-names --execute="SELECT VERSION();";`
    - Get detailed version: `docker-compose exec mariadb mysqladmin --password=root version;`
  - Open command-line client: `docker-compose exec mariadb mysql -proot ezplatform;`
  - Ping MariaDB server: `docker-compose exec mariadb mysqladmin -proot ping;`
  - Get MariaDB status: `docker-compose exec mariadb mysqladmin -proot status;`
    - Get extended status: `docker-compose exec mariadb mysqladmin -proot extended-status;`
  - Show process list: `docker-compose exec mariadb mysqladmin --password=root processlist --verbose;`
  - Get last content modification date: `docker-compose exec mariadb mysql -proot ezplatform -e "SELECT FROM_UNIXTIME(modified) AS modified FROM ezcontentobject ORDER BY modified DESC LIMIT 1;";`
* Solr
  - Get OS release: `docker-compose exec solr cat /etc/os-release;`
  - Get Solr version: `docker-compose exec solr bin/solr version;`
  - Get Solr status: `docker-compose exec solr bin/solr status;`
  - Follow Solr logs: `docker-compose logs --follow solr;`
* Apache/eZ → Solr
  - (Re)Index: `docker-compose exec --user www-data apache php bin/console ezplatform:reindex;`

TODO
----

* Add [DFS](https://doc.ezplatform.com/en/master/guide/clustering/#dfs-io-handler)
* Ensure compatibility with other unixoides than Mac OS X. For example, `sed -i ''` is specific to Mac OS X and a solution could be https://formulae.brew.sh/formula/gnu-sed
* Maybe:
  - Build Solr at the same time than other containers and uncomment that apache depends on solr
  - Use more docker-compose.yml `volumes` and less Dockerfile `COPY`
  - The builder could have some options like: `./docker-compose.bash --cache-pool=memcached --sessions-in-cache-pool --search-engine=legacy --en=dev --xdebug;`
  - Just use `docker-compose up --build` and remove `docker-compose.bash`
