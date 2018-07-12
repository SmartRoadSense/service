#!/bin/sh

# Get website source
echo "Retrieving website source…"
git clone https://github.com/SmartRoadSense/website.git /tmp/repository
mv /tmp/repository/hugo/* /src

echo "Building website…"
hugo --source="/src" --destination="/output" --baseURL="$HUGO_BASEURL" "$@" || exit 1
