#!/bin/bash

# Note this needs VPN

if [ "$1" == "" ] || [ $# -gt 1 ]; then
  echo "Host parameter is missing"
  echo "e.g. datastore.hammer.ucla.edu"
  exit
fi

# Connect to remote server
ssh hammer.datastore << EOF
  sudo su deploy
  cd "/var/www/${1}/current"
  php artisan responsecache:clear
  exit
EOF
