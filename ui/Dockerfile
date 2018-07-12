# ---
# First stage (build)
FROM debian:stretch-slim AS build
WORKDIR /tmp

# Configuration
ENV HUGO_VERSION=0.43
ENV HUGO_TYPE=_extended
ENV HUGO_ID=hugo${HUGO_TYPE}_${HUGO_VERSION}

# Get Hugo
ADD https://github.com/gohugoio/hugo/releases/download/v${HUGO_VERSION}/${HUGO_ID}_Linux-64bit.tar.gz /tmp
RUN tar -xf /tmp/${HUGO_ID}_Linux-64bit.tar.gz --directory=/tmp

# ---
# Second stage (build website)
FROM debian:stretch-slim AS runtime
WORKDIR /
COPY --from=build /tmp/hugo /usr/local/sbin/hugo
RUN apt update && apt install -y \
    git

# Copy script and allow execution
COPY ./build-static-site.sh /build-static-site.sh
RUN chmod 0777 /build-static-site.sh

VOLUME /src
VOLUME /output

CMD ["/build-static-site.sh"]
