FROM jojomi/hugo:0.40.3

COPY ./build-static-site.sh /build-static-site.sh
RUN chmod 0777 /build-static-site.sh

CMD ["/build-static-site.sh"]
