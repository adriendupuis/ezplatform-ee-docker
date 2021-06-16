```shell
cd ./docker/apache/;
wget https://raw.githubusercontent.com/ibexa/docker/main/scripts/vhost.sh;
wget https://raw.githubusercontent.com/ibexa/docker/main/templates/apache2/vhost.template;

bash ./vhost.sh --template-file=vhost.template --basedir=/var/www/ez --sf-env=dev --sf-trusted-proxies=172.27.0.1/24 --sf-http-cache=0 --body-size-limit=0 --request-timeout=363 > vhost.conf;
sed -Ei '' 's/^([^#]+)php.*-fpm/\1php7.3-fpm/' vhost.conf;

rm vhost.sh vhost.template;
cd -;
```