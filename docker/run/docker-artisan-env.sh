#!/bin/sh

# env vars
if [[ ! -z "${APP_STAGE}" ]]; then
    eval $(curl -s "env.getter/php-common?format=bash&source=s3")
    eval $(curl -s "env.getter/${APP_NAME}?format=bash&source=s3")

    if [[ "$PICPAY_LOAD_COMPLETED" = "" || "$PICPAY_PROMO_LOAD_COMPLETED" = "" ]]; then
      exit 1;
    fi
fi


touch /app/storage/logs/app.log \
    && mkdir -p /app/storage/cache/MongoDbHydrators \
    && mkdir -p /app/storage/cache/MongoDbProxies \
    && chown -R www-data: /app/storage

echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/php.ini-development
echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/php.ini-production

# Fix newrelic for php-cli
# https://discuss.newrelic.com/t/relic-solution-single-php-script-docker-containers/80386
php -i > /dev/null
sleep 1;

php /app/artisan $@ --verbose;

wait
