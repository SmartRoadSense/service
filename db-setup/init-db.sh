#!/bin/bash

echo "Alter crowd4roads_sw user"
psql -c "grant all privileges on database srs_raw_db to crowd4roads_sw;"
psql -c "grant all privileges on database srs_agg_db to crowd4roads_sw;"
psql -c "alter role crowd4roads_sw superuser;"

CORES=$(nproc --all)
echo ""
echo "Install OSM data"
cd /home/app
wget $OSM_DATA_URL
wget $OSM_DATA_URL.md5
md5sum -c $OSM_DATA_NAME-latest.osm.pbf.md5
rm $OSM_DATA_NAME-latest.osm.pbf.md5
osm2pgsql  -s $OSM_DATA_NAME-latest.osm.pbf -d $RAW_DB_NAME -E 4326 -l --number-processes $CORES

echo ""
echo "setup SRS schema"
psql -d srs_raw_db -f /db-schema/raw_tables.sql
psql -d srs_raw_db -f /db-schema/raw_functions.sql
psql -d srs_agg_db -f /db-schema/agg_tables.sql
psql -d srs_agg_db -f /db-schema/agg_functions.sql

echo "DB setup complete"
echo ""
