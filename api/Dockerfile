FROM smartroadsense/php-composer:latest

USER root
RUN docker-php-ext-install bcmath
USER app

COPY . /code/api
ADD vendor.tar.xz /code
ADD generated-classes.tar.xz /code

ENV RAW_DB_PORT 5432

WORKDIR /code/api
