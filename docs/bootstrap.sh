#!/bin/bash

# Common packages
export DEBIAN_FRONTEND=noninteractive
echo 'deb http://http.debian.net/debian jessie-backports main' >> /etc/apt/sources.list
apt update
apt full-upgrade -y
apt install -y \
  apt-transport-https \
  bash-completion \
  build-essential \
  curl \
  git \
  htop \
  iotop \
  linux-headers-$(uname -r) \
  pydf \
  ranger \
  rsync \
  rxvt-unicode-256color \
  tmux \
  tree \
  vim \
  wget
apt -t jessie-backports install -y \
  sysdig \
  sysdig-dkms

# Docker Engine
apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D
echo 'deb https://apt.dockerproject.org/repo debian-jessie main' > /etc/apt/sources.list.d/docker.list
apt update
apt install -y docker-engine
systemctl start docker
systemctl enable docker
docker --version

# Docker Compose
curl -L https://github.com/docker/compose/releases/download/1.6.0/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
docker-compose --version
