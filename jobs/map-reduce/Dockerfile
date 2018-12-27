FROM smartroadsense/php-composer:latest

COPY . /code
WORKDIR /code/semi-parallel

# raw database
ENV RAW_DB_HOST=raw-db \
    RAW_DB_NAME=srs_raw_db \
    RAW_DB_USER=crowd4roads_sw \
    RAW_DB_PASS=password \

# aggregate database
ENV AGG_DB_HOST=agg-db \
    AGG_DB_NAME=srs_agg_db \
    AGG_DB_USER=crowd4roads_sw \
    AGG_DB_PASS=password \

# exit 0
CMD [ "true" ]
