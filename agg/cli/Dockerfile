FROM smartroadsense/postgres-cli:3.0

ENV PGHOST=agg-db \
    PGUSER=crowd4roads_sw \
    PGDATABASE=srs_agg_db \
    PGPASSWORD=password \
    PGPASS=password

USER root

# Prepare volume with correct permissions
RUN mkdir /opendata
RUN chown -R 1000:1000 /opendata
VOLUME /opendata

# Prepare home dir with correct permissions
COPY ./ /code
RUN chown -R 1000:1000 /code

USER 1000

WORKDIR /code
