#!/bin/sh

# Get website source
git clone https://github.com/SmartRoadSense/website.git /tmp/repository
mv /tmp/repository/hugo/* /src

# Go on with default Hugo build
/run.sh
