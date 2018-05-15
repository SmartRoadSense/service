FROM smartroadsense/postgres-cli

ENV PGHOST=osm-db \
    PGUSER=crowd4roads_sw \
    PGDATABASE=srs_osm_db \
    PGPASSWORD=password \
    PGPASS=password

COPY ./ /code
WORKDIR /code
