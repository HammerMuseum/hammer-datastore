#! /bin/bash

ELASTICSEARCH_DOMAIN="https://search-hammermuseum-7ugp6zl6uxoivh2ihpx56t7wxu.us-west-1.es.amazonaws.com"
SITE=$1
HOST=$2
ALIAS=$3
LIMIT=$4
SINCE=$5

COMMAND="PYTHONPATH=../. python3 run_harvester.py \
  --submit \
  --host=$HOST \
  --alias=$ALIAS \
  --asset-type=1 \
  --search-domain=$ELASTICSEARCH_DOMAIN \
  --storage=/var/www/$SITE/shared/storage/app"


if test -n "${LIMIT-}"; then
  COMMAND="$COMMAND --limit=$LIMIT"
fi

if test -n "${SINCE-}"; then
  COMMAND="$COMMAND --since=$SINCE"
fi

echo $COMMAND
cd "/var/www/$SITE/current/harvester/scripts" && $COMMAND
