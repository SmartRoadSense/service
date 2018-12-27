FROM smartroadsense/php-composer:latest

COPY composer.* /code/ws/
WORKDIR /code/ws
RUN composer install --no-interaction

# aggregate database
ENV AGG_DB_HOST=agg-db \
    AGG_DB_NAME=srs_agg_db \
    AGG_DB_USER=crowd4roads_sw \
    AGG_DB_PASS=password \

# meta database
ENV META_DB_HOST=agg-db \
    META_DB_NAME=srs_agg_db \
    META_DB_USER=crowd4roads_sw \
    META_DB_PASS=password

COPY . /code/ws
