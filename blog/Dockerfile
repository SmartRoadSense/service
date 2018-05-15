FROM smartroadsense/php-composer:latest

USER root
RUN apt update && apt install -y \
  python3-pip python3-dev mysql-client libmysqlclient-dev \
  && docker-php-ext-install mysqli \
  && rm -rf /var/lib/apt/lists/*
RUN pip3 install --upgrade setuptools
RUN pip3 install mycli

ENV WORDPRESS_DB_HOST=blog-db:3306 \
    WORDPRESS_DB_USER=crowd4roads_sw \
    WORDPRESS_DB_PASSWORD=password \
    WORDPRESS_DB_NAME=srs_blog_db \
    MYSQL_HOST=blog-db \
    MYSQL_PWD=password

ADD blog.tar.xz /code
RUN chmod -R o+rx /code

ADD data.sql.xz /code
RUN chown -R app /code

WORKDIR /code/blog
USER app
