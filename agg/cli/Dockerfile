FROM smartroadsense/postgres-cli

ENV PGHOST=agg-db \
    PGUSER=crowd4roads_sw \
    PGDATABASE=srs_agg_db \
    PGPASSWORD=password \
    PGPASS=password

COPY ./ /code
WORKDIR /code
