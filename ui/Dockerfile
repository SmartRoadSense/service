FROM klakegg/hugo:0.52-ext-alpine

RUN apk update \
    && apk --no-cache add openssl git git-lfs

# Copy script and allow execution
COPY ./build-static-site.sh /build-static-site.sh
RUN chmod 0777 /build-static-site.sh

VOLUME /src
VOLUME /target

ENTRYPOINT [ "/bin/sh", "-c", "/build-static-site.sh"]
