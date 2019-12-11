#!/bin/bash

DBTABLE=current

DBCOLUMNS='"latitude","longitude","ppe","osm_id","highway", "quality", "passengers", "updated_at"'
QUERY_DBCOLUMNS="st_Y(the_geom) as latitude, st_x(the_geom) as longitude, ppe, osm_id, highway, quality, occupancy, updated_at"

FILENAME=open_data.csv
FILENAMEZIP=/opendata/open_data.zip
TMP_FILENAME="tmp_$FILENAME"

echo "Starting new db dump..."
echo ${DBCOLUMNS} > $TMP_FILENAME
psql -t -A -F ',' -c "SELECT $QUERY_DBCOLUMNS FROM \"$DBTABLE\";" >> $TMP_FILENAME
echo "Aggregate db dumped!"

echo "Substituing old file with new one..."
mv -v "$TMP_FILENAME" $FILENAME
echo "New open data file set!"

echo "Creating zip file..."
zip "$FILENAMEZIP" $FILENAME
echo "$FILENAMEZIP created"
rm -v $FILENAME

echo "All done. Bye!"
