#!/bin/sh

# env vars
if [[ ! -z "${APP_STAGE}" ]]; then
    eval $(curl -s "env.getter/php-common?format=bash&source=s3")
    eval $(curl -s "env.getter/${APP_NAME}?format=bash&source=s3")

    if [[ "$PICPAY_LOAD_COMPLETED" = "" || "$PICPAY_PROMO_LOAD_COMPLETED" = "" ]]; then
      exit 1;
    fi
fi

# newrelic daemon
if test -f "/usr/local/etc/php/conf.d/newrelic.ini"; then
  sed -i \
    -e "s/newrelic.license =.*/newrelic.license = \"$PICPAY_NEWRELIC_LICENSE_KEY\"/" \
     -e "s/newrelic.appname =.*/newrelic.appname = \"$APP_NAME:$APP_ENV\"/" \
    -e "s/;\?newrelic.framework =.*/newrelic.framework = \laravel/" \
    -e "s/newrelic.daemon.start_timeout =.*/newrelic.daemon.start_timeout= \"5s\"/" \
     /usr/local/etc/php/conf.d/newrelic.ini
  /usr/bin/newrelic-daemon -c /etc/newrelic/newrelic.cfg --pidfile /var/run/newrelic-daemon.pid
fi


touch /app/storage/logs/app.log \
    && mkdir -p /app/storage/cache/MongoDbHydrators \
    && mkdir -p /app/storage/cache/MongoDbProxies \
    && chown -R www-data: /app/storage

echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/php.ini-development 
echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/php.ini-production

# Aplica índices do mongo via Doctrine
#php artisan ensure-indexes

# supervisor
exec supervisord -n -c /etc/supervisord.conf