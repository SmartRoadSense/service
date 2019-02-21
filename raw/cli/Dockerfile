FROM smartroadsense/postgres-cli:2.0

ENV PGHOST=raw-db \
    PGUSER=crowd4roads_sw \
    PGDATABASE=srs_raw_db \
    PGPASSWORD=password \
    PGPASS=password

COPY ./ /code
WORKDIR /code
