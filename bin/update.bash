#!/bin/bash

# set environment variables EMAIL and PASSWORD to use this script

cleanup() {
    rm -fr "$TMPDIR"
}

set -e
DIR="$(dirname "$(realpath "$0")")"
token=$(curl -f --location \
     --request POST \
     'https://opendata.nationalrail.co.uk/authenticate' \
     --header 'Content-Type: application/x-www-form-urlencoded' \
     --data-urlencode "username=$EMAIL" \
     --data-urlencode "password=$PASSWORD" | jq -r .token)
TMPDIR="$(mktemp -d)"
trap cleanup EXIT
pushd "$TMPDIR"
curl -f --location -H "X-Auth-Token: $token" https://opendata.nationalrail.co.uk/api/staticfeeds/3.0/timetable -o timetables.zip
unzip timetables.zip
php "$DIR"/load_data.php .
popd
"$DIR"/cache_boards.bash
