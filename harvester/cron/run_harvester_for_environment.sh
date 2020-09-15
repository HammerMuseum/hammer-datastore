#! /bin/bash

params=()

if [ ! -z "$4" ]; then
    params+=("--limit=$4")
fi

cd /var/www/$1/current/harvester/scripts

PYTHONPATH=../. python3 run_harvester.py \
  --submit \
  --host=$2 \
  --alias $3 \
  --asset-type=1 \
  --search-domain=https://search-hammermuseum-7ugp6zl6uxoivh2ihpx56t7wxu.us-west-1.es.amazonaws.com \
  "${params[@]}"
