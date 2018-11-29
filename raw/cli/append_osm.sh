#! /bin/bash

osm2pgsql --number-processes 8 -a -d ${PGDATABASE} -H ${PGHOST} -l /data/map.osm.pbf
