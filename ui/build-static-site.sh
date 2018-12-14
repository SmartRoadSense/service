#!/bin/sh

if [ -d "/repo/website" ]; then
    echo "Pulling website updates…"
    cd /repo/website
    git pull --ff-only
else
    echo "Retrieving website source…"
    git clone -b master --single-branch --no-tags https://github.com/SmartRoadSense/website.git /repo/website
fi

# Update source directory
cp -r /repo/website/hugo/* /src

echo "Building website…"
hugo --source="/src" --destination="/target" --baseURL="$HUGO_BASEURL" "$@" || exit 1

echo "Cleaning up…"
rm -r /src/*
