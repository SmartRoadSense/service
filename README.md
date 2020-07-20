# SmartRoadSense back-end service

![SmartRoadSense logo](docs/media/logo-small.png)

## SETUP with external DBs
1. Clone the repository
2. Setup external databases as needed
3. Edit `srs.env-template` file and rename it as `srs.env`
4. Run `make config` to generate the system configuration file
5. Run `make create_volumes` to create docker external volumes
6. Run `make up`
7. Run `make enable_jobs` to enable automatic open-data computation and historifying


## SETUP with dockerized DBs
1. Clone the repository
2. Edit `srs.env-template` file and rename it as `srs.env`
3. Run `make config` to generate the system configuration file
4. Run `make create_db_volumes` to create docker external volumes
5. Run `make create_volumes` to create docker external volumes
6. Run `make init_db` to complete the setup of needed dbs
7. Run `make up`
8. Run `make enable_jobs` to enable automatic open-data computation and historifying
