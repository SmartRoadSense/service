#! /bin/bash

osm2pgsql --number-processes 8 -a -s -d ${PGDATABASE} -H ${PGHOST} -P ${PGPORT} -l /data/map.osm.pbf
