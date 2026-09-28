#!/bin/bash
docker-entrypoint.sh "$@" &
pid=$!
trap 'kill -TERM $pid 2>/dev/null' TERM INT
/setup/setup.sh &
wait $pid
