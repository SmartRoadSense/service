FROM nginx:1.14.0-alpine

RUN chmod 777 /var/cache/nginx

# Copy and overwrite configuration files
RUN rm -rf /etc/nginx/conf.d/*
COPY proxy_params nginx.conf /etc/nginx/

USER nginx
