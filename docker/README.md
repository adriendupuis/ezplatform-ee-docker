Docker Container Cluster
========================

Introduction
------------

Use a Docker containers cluster to have a [typical architecture for eZ Platform](https://doc.ezplatform.com/en/3.1/guide/clustering/) including the following elements.
* HTTP Server:
  - Debian “Buster” 10
  - Apache 2.4
  - PHP 7.3 ([by default](https://packages.debian.org/buster/php/php))
  - [PHP FastCGI Process Manager](https://www.php.net/manual/install.fpm.php) (PHP-FPM) with [Unix domain socket](https://en.wikipedia.org/wiki/Unix_domain_socket) (UDS)
  - [eZ Platform Enterprise Edition](https://ez.no/Products/eZ-Platform-Enterprise-Edition) 3.1
* Reverse Proxy Cache Server:
  - Varnish 6.0
  - Varnish Modules 0.15
* [Persistence Cache](https://doc.ezplatform.com/en/3.1/guide/persistence_cache/) and [Session Handling](https://doc.ezplatform.com/en/3.1/guide/sessions/) Servers:
  - Redis 3.2
* DataBase Server:
  - MariaDB 10.4
* Search Engine:
  - Solr 7.7

Quick Start
-----------

* Run `./docker-compose.bash;` (you may need your eZ Platform Enterprise Edition credentials)
* Access to http://localhost:8080/

About
-----

* Follow [eZ Platform 3.1 Requirements](https://doc.ezplatform.com/en/3.1/getting_started/requirements/) as much as possible.
* Add as less configuration as possible to [original distribution](https://github.com/ezsystems/ezplatform-ee/tree/v2.5.9).

URLs and Command Lines
----------------------

### Useful URLs
* eZ Home page through Varnish: http://localhost:8080/
* eZ Admin through Varnish: http://localhost:8080/admin
  - Username: *admin*
  - Password: *publish*
  * [Admin / System Info](http://localhost:8080/admin/systeminfo)
* Change port from 8080 to 8000 to access directly to Apache avoiding Varnish
* Solr Admin: http://localhost:8983/solr/#/collection1

### Useful Commands
* Docker
  - Get Docker version: `docker --version;`
  - Get Docker Compose version: `docker-compose --version;`
  - Remove everything about containers: `docker rm --force --volumes $(docker ps --all --quiet) && docker system prune --force --all;`
* Docker Containers Cluster
  - Get containers status: `docker-compose ps --all;`
  - Follow several logs from the cluster: `docker-compose logs -f;`
  - Follow containers stats: `docker stats;`
  - Restart all containers: `docker-compose restart;`
  - List containers internal processes: `docker-compose top;`
  - Stop all containers: `docker-compose stop;`
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
  - Get Symfony version: `docker-compose exec apache bin/console --version;`
  - See a bundle info: `docker-compose exec apache composer show vendor-name/bundle-name;`
    - See eZ Kernel bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-kernel;`
    - See eZ Symfony Tools info: `docker-compose exec apache composer show ezsystems/symfony-tools;`
    - See eZ HTTP Cache bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-http-cache;`
    - See eZ Solr SE bundle info: `docker-compose exec apache composer show ezsystems/ezplatform-solr-search-engine;`
  - Clear eZ caches: `docker-compose exec --user www-data apache sh -c bin/console cache:clear;` 
  - Open a shell into container as root: `docker-compose exec apache bash;`
  - Open a shell into container as www-data: `docker-compose exec --user www-data apache bash;`
* Varnish
  - Get OS release: `docker-compose exec varnish cat /etc/os-release;`
  - Get Varnish version: `docker-compose exec varnish varnishd -V;`
  - Follow [Varnish logs](https://varnish-cache.org/docs/6.0/reference/varnishlog.html): `docker-compose exec varnish varnishlog;`
    - Follow requests for an URL: `docker-compose exec varnish varnishlog -q 'ReqURL eq "/the/url/to/follow"';`
    - Follow PURGE and PURGEKEYS requests: `docker-compose exec varnish varnishlog -q 'ReqMethod ~ PURGE.*';`
  - Follow [Varnish cache statistics](https://varnish-cache.org/docs/6.0/reference/varnishstat.html): `docker-compose exec varnish varnishstat;`
  - Restart Varnish (remove all cache): `docker-compose restart varnish;`
  - [Bans](https://varnish-cache.org/docs/trunk/users-guide/purging.html#bans)
    - Ban an URL: `docker-compose exec varnish varnishadm ban req.url '==' '/the/url/to/ban';`
    - Ban built CSS and JS: `docker-compose exec varnish varnishadm ban req.url '~' '^/assets/.*\\.(cs|j)s$';`
    - Get the ban list: `docker-compose exec varnish varnishadm ban.list;`
  - Open a shell into container: `docker-compose exec varnish bash;`
  - User Context Hash
    - Get a User Context Hash as Anonymous: `uch=$(curl -sIXGET -H "Surrogate-Capability: abc=ESI/1.0" -H "accept: application/vnd.fos.user-context-hash" -H "x-fos-original-url: /" http://localhost:8000/_fos_user_context_hash | grep User-Context-Hash | sed 's/X-User-Context-Hash: //'); echo $uch;`
    - Use this User Context Hash: `curl -IXGET -H "Surrogate-Capability: abc=ESI/1.0" -H "x-user-context-hash: ${uch//[^[:alnum:]]/}" http://localhost:8000/ez-platform;`
* Apache → Varnish
  - See [`render_esi` `esi:include` tags](https://symfony.com/doc/5.0/http_cache/esi.html): `curl --silent --header "Surrogate-Capability: abc=ESI/1.0" http://localhost:8000/the/url/to/test | grep esi:include;`
  - Purge an URL: `docker-compose exec --user www-data apache curl --request PURGE --header 'Host: localhost:8080' http://varnish/the/url/to/purge;`
  - Soft purge content object(s) by ID:
    - `docker-compose exec apache bin/console fos:httpcache:invalidate:tag <TYPE><ID>;`
    - `docker-compose exec apache curl -X PURGEKEYS -H 'Host: localhost:8080' -H 'xkey-softpurge: <TYPE><ID>' http://varnish;`
    - [xkey types](https://github.com/ezsystems/ezplatform-http-cache/blob/v2.0.0/docs/using_tags.md#tags-in-use-in-this-bundle):
      - `c`: ***c***ontent id
      - `l`: ***l***ocation id
      - `p`: (***p***ath) ancestor location id
      - `pl`: ***p***arent ***l***ocation id
      - `ct`: ***c***ontent ***t***ype id
* Symfony → Varnish
  - TODO: `bin/console fos:httpcache:invalidate:tag --siteaccess=admin l2;`
* Redis
  - Get OS release: `docker-compose exec redis cat /etc/os-release;`
  - Get server info: `docker-compose exec redis redis-cli INFO Server;`
  - Get `maxmemory` settings: `docker-compose exec redis redis-cli INFO MEMORY | grep maxmemory;`
  - Get all info: `docker-compose exec redis redis-cli INFO;`
  - Follow stats: `docker-compose exec redis redis-cli --stat;`
  - Monitor request: `docker-compose exec redis redis-cli MONITOR;`
  - Delete all keys: `docker-compose exec redis redis-cli FLUSHALL;`
  - Clear Redis caches: `docker-compose exec --user www-data apache bin/console cache:pool:clear cache.redis;` 
  - Total key count: `docker-compose exec redis redis-cli DBSIZE;`
    - PHP session key count: `docker-compose exec redis redis-cli KEYS PHPREDIS_SESSION:* | wc -l | tr -d ' ';`
    - `ezp` cache namespace key count: `docker-compose exec redis redis-cli KEYS ezp:* | grep -v empty | wc -l | tr -d ' ';`
  - Open Redis CLI: `docker-compose exec redis redis-cli;`
  - Open a shell into container: `docker-compose exec redis bash;`
* Memcached
  - Get OS release: `docker-compose exec memcached cat /etc/os-release;`
  - Get all stats: `echo 'stats' | docker-compose exec -T apache telnet memcached 11211;`
  - Delete all keys: `echo 'flush_all' | docker-compose exec -T apache telnet memcached 11211;`
  - Clear Memcached caches: `docker-compose exec --user www-data apache bin/console cache:pool:clear cache.memcached;`
  - Total key count: `echo 'stats' | docker-compose exec -T apache telnet memcached 11211 | grep curr_item;`
    - `ezp` cache namespace key count: `docker-compose exec apache php -r '$m=new Memcached(); $m->addServer("memcached", 11211); echo implode(PHP_EOL, $m->getAllKeys());' | wc -l | tr -d ' ';`
* MariaDB
  - Get OS release: `docker-compose exec mariadb cat /etc/os-release;`
  - Get MariaDB version: `docker-compose exec mariadb mysql --password=root --batch --skip-column-names --execute="SELECT VERSION();";`
    - Get detailed version: `docker-compose exec mariadb mysqladmin --password=root version;`
  - Open command-line client: `docker-compose exec mariadb mysql -proot --default-character-set=utf8mb4 ezplatform;`
  - Ping MariaDB server: `docker-compose exec mariadb mysqladmin -proot ping;`
  - Get MariaDB status: `docker-compose exec mariadb mysqladmin -proot status;`
    - Get extended status: `docker-compose exec mariadb mysqladmin -proot extended-status;`
  - Show process list: `docker-compose exec mariadb mysqladmin --password=root processlist --verbose;`
  - Get last content modification date: `docker-compose exec mariadb mysql -proot ezplatform -e "SELECT FROM_UNIXTIME(modified) AS modified FROM ezcontentobject ORDER BY modified DESC LIMIT 1;";`
  - Get language list: `docker-compose exec mariadb mysql -proot --default-character-set=utf8mb4 ezplatform -e "SELECT * FROM ezcontent_language;";`
* Solr
  - Get OS release: `docker-compose exec solr cat /etc/os-release;`
  - Get Solr version: `docker-compose exec solr bin/solr version;`
  - Get Solr status: `docker-compose exec solr bin/solr status;`
  - Follow Solr logs: `docker-compose logs --follow solr;`
* Apache/eZ → Solr
  - (Re)Index: `docker-compose exec --user www-data apache bin/console ezplatform:reindex;`

TODO
----

* v3: Avoid .env's `DATABASE_VERSION` change without commit it
* Add [DFS](https://doc.ezplatform.com/en/3.1/guide/clustering/#dfs-io-handler)
* Facilitate switch between eZ Platform EE v2.5 and eZ Platform v3.x
* Ensure compatibility with other “unixoides” (Unix-likes) than Mac OS X. For example, `sed -i ''` is specific to Mac OS X and a solution could be https://formulae.brew.sh/formula/gnu-sed
* Maybe:
  - Build Solr at the same time than other containers and uncomment that apache depends on solr
  - Facilitate keeping Varnish's VCL up-to-date  
  - Use more docker-compose.yml `volumes` and less Dockerfile `COPY`
  - The builder could have some options like: `./docker-compose.bash --cache-pool=memcached --sessions-in-cache-pool --search-engine=legacy --en=dev --xdebug;`
  - Just use `docker-compose up --build` and remove `docker-compose.bash`
  - Use 2 Redis: [“separate instances for session & cache”](https://doc.ezplatform.com/en/3.1/getting_started/requirements/#recommended-setups)
* Go back to an official Varnish image by succeeding installing xkey in it

TODO: Reset or Uninstall
* Reset:
  - `docker-compose stop;`
  - `docker system prune --all --force;`
  - `git clean -df;` or `rm -rf vendor/ var/ public/build/ public/bundles/ public/var/ public/assets/ezplatform/;`
  - `git reset --hard origin/$(git rev-parse --abbrev-ref HEAD);`
