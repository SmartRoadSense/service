#! /bin/bash

osm2pgsql --number-processes 8 -s -d ${PGDATABASE} -H ${PGHOST} -l /data/map.osm.pbf
