Docker Container Cluster
========================

Introduction
------------

Use a Docker containers cluster to have a typical architecture for eZ Platform including the following elements.
* HTTP Server:
  - Debian 10
  - Apache 2.4
  - PHP 7.3
  - [PHP FastCGI Process Manager](https://www.php.net/manual/install.fpm.php) (PHP-FPM) with [Unix domain socket](https://en.wikipedia.org/wiki/Unix_domain_socket) (UDS)
  - [eZ Platform Enterprise Edition](https://ez.no/Products/eZ-Platform-Enterprise-Edition) 2.5
* Reverse Proxy Cache Server:
  - Varnish 2.4
  - Varnish Modules 0.15
* [Persistence Cache](https://doc.ezplatform.com/en/2.5/guide/persistence_cache/#redis) Server:
  - Redis 3.2
* DataBase Server:
  - MariaDB 10.1
* Search Engine:
  - Solr 6.6

Quick Start
-----------

* Run `./docker-compose.bash;` (you may need your eZ Platform Enterprise Edition credentials)
* Access to http://127.0.0.1:8080/

[WIP] About
-----

### Philosophy
* Follow [eZ Platform 2.5 Requirements](https://doc.ezplatform.com/en/2.5/getting_started/requirements/) as much as possible.
* Add as less configuration as possible to [original distribution](https://github.com/ezsystems/ezplatform-ee/tree/v2.5.9).

### Usefull URLs
* eZ Home page through Varnish: http://127.0.0.1:8080/
* eZ Admin through Varnish: http://127.0.0.1:8080/admin
  - Username: *admin*
  - Password: *publish*
  * [Admin / System Info](http://localhost:8080/admin/systeminfo)
* Change port from 8080 to 8000 to access directly to Apache avoiding Varnish
* Solr Admin: http://127.0.0.1:8983/solr/#/collection1

### Usefull Commands
* Docker Containers Cluster
  - Get containers status: `docker-compose ps --all;`
  - Follow several logs from the cluster: `docker-compose logs -f;`
  - Follow containers stats: `docker stats;`
* Apache/PHP/eZ:
  - Get Apache version: `docker-compose exec apache apache2ctl -v;`
  - Get Apache modules: `docker-compose exec apache apache2ctl -M;`
  - Follow Apache error.log: `docker-compose exec apache tail -f /var/log/apache2/error.log;`
  - Get PHP version: `docker-compose exec apache php -v;`
  - Get PHP modules: `docker-compose exec apache php -m;`
  - Follow PHP-FPM log: `docker-compose exec apache tail -f /var/log/php7.3-fpm.log;`
  - Follow eZ Platform log: `docker-compose exec apache tail -f var/logs/dev.log;`
  - Get Composer version: `docker-compose exec apache composer --version;`
  - See a bundle info: `docker-compose exec apache composer show vendor-name/bundle-name;`
    - See eZ Kernel bundle info: `docker-compose exec apache composer show ezsystems/ezpublish-kernel;`
    - See eZ Symfony Tools info: `docker-compose exec apache composer show ezsystems/symfony-tools;`
    - See eZ HTTP Cache bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-http-cache;`
    - See eZ Solr SE bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-solr-search-engine;`
  - Clear eZ caches: `docker-compose exec --user www-data apache sh -c "php bin/console cache:clear; php bin/console cache:pool:clear cache.redis;";` 
  - Open a shell into container as root: `docker-compose exec apache bash;`
  - Open a shell into container as www-data: `docker-compose exec --user www-data apache bash;`
* Varnish
  - Get Varnish version: `docker-compose exec varnish varnishd -V;`
  - Follow [Varnish logs](https://varnish-cache.org/docs/6.0/reference/varnishlog.html): `docker-compose exec varnish varnishlog;`
    - Follow requests for an URL: `docker-compose exec varnish varnishlog -q 'ReqURL eq "/the/url/to/follow"';`
    - Follow PURGE requests: `docker-compose exec varnish varnishlog -q 'ReqMethod eq PURGE';`
  - Follow [Varnish cache statistics](https://varnish-cache.org/docs/6.0/reference/varnishstat.html): `docker-compose exec varnish varnishstat;`
  - [Bans](https://varnish-cache.org/docs/trunk/users-guide/purging.html#bans)
    - Ban an URL: `docker-compose exec varnish varnishadm ban req.url '~' '/the/url/to/ban';`
    - Get the ban list: `docker-compose exec varnish varnishadm ban.list;`
  - Open a shell into container: `docker-compose exec varnish bash;`
* Apache → Varnish
  - Purge an URL: `docker-compose exec --user www-data apache curl -X PURGE -H 'Host: 127.0.0.1:8080' http://varnish/the/url/to/purge;`
  - Soft purge a content by ID: `docker-compose exec --user www-data apache curl -X PURGE -H 'Host: 127.0.0.1:8080' -H 'key: cCONTENTID' http://varnish;`
    - (x)key prefixes:
      - `c`ontent id
      - `l`ocation id
      - `p`: (path) ancestor location id
      - `pl`: parent location id
      - `ct`: content type id 
* Redis
  - Get server info: `docker-compose exec redis redis-cli INFO Server;`
  - Get all info: `docker-compose exec redis redis-cli INFO;`
  - Follow stats: `docker-compose exec redis redis-cli --stat;`
  - Monitor request: `docker-compose exec redis redis-cli MONITOR;`
  - Delete all keys: `docker-compose exec redis redis-cli FLUSHALL;`
  - Open Redis CLI: `docker-compose exec redis redis-cli;`
  - Open a shell into container: `docker-compose exec redis bash;`
* TODO: Solr
  - Get Solr version: `docker-compose exec solr bin/solr version;`
  - Follow Solr logs: `docker-compose logs --follow solr;`
* TODO: Apache/eZ → Solr
  - (Re)Index: `docker-compose exec --user www-data apache php bin/console ezplatform:reindex;`

TODO
----

* Switch from 120.0.0.1 to localhost in README.md to be more consistent with vhost.conf `ServerName`
* Ensure compatibility with other unixoides than Mac OS X. For example, `sed -i ''` is specific to Mac OS X and a solution could be https://formulae.brew.sh/formula/gnu-sed
* Maybe:
  - Build Solr at the same time than other containers and uncomment that apache depends on solr
  - Use more docker-compose.yml `volumes` and less Dockerfile `COPY`
  - The builder could have some options like: `./docker-compose.bash --cache-pool=memcached --sessions-in-cache-pool --search-engine=legacy --en=dev --xdebug;`
  - Just use `docker-compose up --build` and remove `docker-compose.bash`
