ENV ?= prod
DC := docker-compose -f docker-compose.yml -f docker-compose.${ENV}.yml
DC_RUN := ${DC} run --rm


.PHONY: test
test:
	${DC_RUN} tiles eslint ./
	@echo 'All tests passed!'

.PHONY: fmt
fmt:
	${DC_RUN} tiles eslint --fix ./

data:
	mkdir data

test/payload.json:
	gzip -kd test/payload.json.gz

.PHONY: up
up: data build
	${DC} up -d
	${DC} ps
	@echo
	@echo 'Now open http://127.0.0.1:8080 with your web browser'
	@echo 'See `raw` database web UI at http://127.0.0.1:9000/phppgadmin/'
	@echo 'See `agg` database web UI at http://127.0.0.1:9001/phppgadmin/'
	@echo

down:
	${DC} down --rmi all -v
	@echo 'All images and volumes removed'
	@echo

.PHONY: ps
ps:
	${DC} ps

.PHONY: init init_raw init_agg
init: init_raw init_agg

init_raw:
	${DC_RUN} raw-cli /code/init.sh

init_agg:
	${DC_RUN} agg-cli /code/init.sh

.PHONY: enable_jobs enable_6h_jobs enable_weekly_jobs
enable_jobs: enable_6h_jobs enable_weekly_jobs

enable_6h_jobs:
	rsync $${PWD}/jobs/6h.service /lib/systemd/system/
	rsync $${PWD}/jobs/6h.timer /lib/systemd/system/
	systemctl daemon-reload
	systemctl start 6h.timer
	systemctl enable 6h.timer
	systemctl status 6h.timer

enable_weekly_jobs:
	rsync $${PWD}/jobs/weekly.service /lib/systemd/system/
	rsync $${PWD}/jobs/weekly.timer /lib/systemd/system/
	systemctl daemon-reload
	systemctl start weekly.timer
	systemctl enable weekly.timer
	systemctl status weekly.timer

.PHONY: fetch_example fetch_italy
fetch_example: data clean_pbf
	wget -O data/map.osm https://api.openstreetmap.org/api/0.6/map?bbox=12.898,43.9115,12.921,43.928

fetch_eu_%: data clean_pbf
	wget http://download.geofabrik.de/europe/$*-latest.osm.pbf
	wget http://download.geofabrik.de/europe/$*-latest.osm.pbf.md5
	md5sum -c $*-latest.osm.pbf.md5
	rm $*-latest.osm.pbf.md5
	mv $*-latest.osm.pbf data/map.osm

fetch_region_%: data clean_pbf
	wget http://download.geofabrik.de/$*-latest.osm.pbf
	wget http://download.geofabrik.de/$*-latest.osm.pbf.md5
	md5sum -c $*-latest.osm.pbf.md5
	rm $*-latest.osm.pbf.md5
	mv $*-latest.osm.pbf data/map.osm

fetch_italy: fetch_eu_italy

.PHONY: import_example import_italy import import_raw import_agg import_osm
import: import_raw import_agg

import_example: fetch_example import_osm
import_italy: fetch_italy import_osm

import_raw:
	${DC_RUN} raw-cli psql -f /code/data.sql

import_agg:
	${DC_RUN} agg-cli psql -f /code/data.sql

import_osm:
	${DC_RUN} raw-cli ./import_osm.sh
	${DC_RUN} osm-cli ./import_osm.sh

.PHONY: export_osm
export_osm:
	${DC_RUN} raw-cli pg_dump -a -t 'planet_osm*' -f /code/osm.sql
	${DC_RUN} osm-cli pg_dump -a -t 'planet_osm*' -f /code/osm.sql

.PHONY: export export_raw export_agg
export: export_raw export_agg

export_raw:
	${DC_RUN} raw-cli pg_dump -a -T 'planet_osm*' -f /code/data.sql

export_agg:
	${DC_RUN} agg-cli pg_dump -a -f /code/data.sql

.PHONY: rs
rs:
	${DC} restart

.PHONY: stop
stop: clean_ui
	${DC} stop

.PHONY: rm rmc
rm rmc: stop
	${DC} rm -fa

.PHONY: rmi
rmi: rmc
	docker rmi $$(docker images | grep 'srs' | awk '{print $$1}')

.PHONY: rmv
rmv: stop
	${DC} rm -fv
	docker volume rm $$(docker volume ls | grep srs | awk '{print $$2}')

sh_%:
	${DC_RUN} $* /bin/bash

logs_%:
	${DC} logs $*

.PHONY: raw-cli
raw-cli:
	${DC_RUN} raw-cli pgcli

.PHONY:	raw-projections-reset
raw-projections-reset:
	${DC_RUN} map-reduce php /code/reset_projections.php

.PHONY: agg-cli
agg-cli:
	${DC_RUN} agg-cli pgcli

.PHONY: osm-cli
osm-cli:
	${DC_RUN} osm-cli pgcli

.PHONY: update_meta
update_meta: jobs/meta-updater/meta-updater
	${DC_RUN} meta-updater /code/meta-updater

.PHONY: map-reduce
map-reduce:
	${DC_RUN} map-reduce php semi_parallel_updater.php

.PHONY: history
history:
	${DC_RUN} map-reduce php /code/history_stepper.php

.PHONY: open_data
open_data:
	${DC_RUN} agg-cli ./update_open_data.sh
	mv agg/cli/open_data.zip web/open_data.zip

.PHONY:	reset_projs
reset_projs:
	${DC_RUN} map-reduce php /code/reset_projections.php

load_test_sh: test/boom test/payload.json
	docker run --net=srs_default -v /var/run/docker.sock:/var/run/docker.sock \
		-v $$PWD/test:/code -w /code -it michelesr/docker-cli bash

.PHONY: build build_go
build: build_go

build_go: jobs/meta-updater/meta-updater test/boom

jobs/meta-updater/meta-updater: jobs/meta-updater/meta-updater.go
	docker run --rm -v $${PWD}/jobs/meta-updater:/code michelesr/gopg:latest env CGO_ENABLED=0 go build /code/meta-updater.go

test/boom:
	docker run --rm -it -v $${PWD}/test:/code michelesr/gopg:latest env CGO_ENABLED=0 \
	  bash -c 'go get github.com/michelesr/boom && cp `which boom` .'

.PHONY: clean clean_go clean_raw clean_ui clean_data
clean: clean_go clean_raw clean_ui clean_data clean_pbf

clean_go:
	rm -f jobs/meta-updater/meta-updater
	rm -f test/boom

clean_raw:
	rm -f raw/*.pbf
	rm -f raw/*.md5

clean_data:
	rm -rf data/

clean_pbf:
	rm -rf *.pbf*
	rm -rf data/map.osm

