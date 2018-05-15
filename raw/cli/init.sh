#!/bin/bash

#psql -c 'CREATE USER srs_ro_user;'
#psql -c 'CREATE EXTENSION postgis;'
#psql -c 'CREATE EXTENSION postgis_topology;'
psql -f /code/tables.sql
psql -f /code/functions.sql
