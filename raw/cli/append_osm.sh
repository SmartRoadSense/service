#! /bin/bash

osm2pgsql --number-processes 8 -a -d ${PGDATABASE} -H ${PGHOST} -P ${PGPORT} -l /data/map.osm.pbf
