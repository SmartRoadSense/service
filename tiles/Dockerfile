FROM node:6

ENV DEBIAN_FRONTEND noninteractive
RUN apt update && apt install -y \
    libcairo2-dev libjpeg62-turbo-dev libpango1.0-dev libgif-dev build-essential g++ && \
    rm -rf /var/lib/apt/lists/*

RUN npm install -g canvas express sphericalmercator eslint nodemon

ENV NODE_PATH /usr/local/lib/node_modules
EXPOSE 8000

USER 1000

COPY ./ /code
WORKDIR /code
CMD ["node", "server.js"]
