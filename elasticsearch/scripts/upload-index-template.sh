#!/bin/bash
#
# Helper script for uploading an index template to Elasticsearch.
# Author: https://gist.github.com/neilh-cogapp/99b16f2271bbaf903b518d77cac77e3d
# Originally forked from: https://gist.github.com/hfossli/4368aa5a577742c3c9f9266ed214aa58
#
CLEAR='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'

function usage() {
  if [ -n "$1" ]; then
    echo -e "${RED} $1${CLEAR}\n";
  fi
  echo "Usage: $0 [-h host] [-f file]"
  echo "  -h, --host   The Elasticsearch host"
  echo "  -n, --name   The template name"
  echo "  -f, --file   The template file"
  echo ""
  echo "Example: $0 --host http://localhost:9200 --name template_example --file path/to/my/template.json"
  exit 1
}

# parse params
while [[ "$#" > 0 ]]; do case $1 in
  -h|--host) HOST="$2"; shift;shift;;
  -n|--name) TEMPLATE_NAME="$2";shift;shift;;
  -f|--file) TEMPLATE_PATH="$2";shift;shift;;
  *) usage "Unknown parameter passed: $1"; shift; shift;;
esac; done

# verify params
if [ -z "$HOST" ]; then usage "Elasticsearch host not provided"; fi;
if [ -z "$TEMPLATE_NAME" ]; then usage "Template name not provided."; fi;
if [ -z "$TEMPLATE_PATH" ]; then usage "Template file not provided."; fi;

echo "Uploading Elasticsearch index template..."
if curl --fail -X PUT "$HOST/_template/$TEMPLATE_NAME?pretty" -H 'Content-Type: application/json' --data-binary "@$TEMPLATE_PATH"; then
    echo -e "${GREEN}\nUploaded Elasticsearch index template successfully.${CLEAR}"
else
    echo -e "${RED}\nFailed to upload \"$TEMPLATE_PATH\" as \"$TEMPLATE_NAME\" Elasticsearch index template.${CLEAR}"
fi;
