#!/bin/bash

#psql -c 'CREATE USER srs_ro_user;'
psql -f /code/tables.sql
psql -f /code/functions.sql
