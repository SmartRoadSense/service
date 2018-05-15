#! /bin/bash

ext='osm'

# set .pbf if OSM PBF is detected
if [[ $(file /data/map.osm) == *'OpenStreetMap Protocolbuffer Binary Format'* ]];
then
  ext='pbf'
  rm -f /data/map.pbf
  ln -s /data/map.{osm,${ext}}
fi

osm2pgsql --number-processes 8 -s -d ${PGDATABASE} -H ${PGHOST} -m /data/map.${ext}
