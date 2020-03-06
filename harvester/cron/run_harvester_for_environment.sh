#!/bin/bash
cd /var/www/$1/current/harvester/scripts
PYTHONPATH=../. python3 run_harvester.py --host=$2 --asset-type=1 --submit --search-domain=https://search-hammermuseum-7ugp6zl6uxoivh2ihpx56t7wxu.us-west-1.es.amazonaws.com --alias $3
sudo chmod -R 777 /var/www/$1/current/harvester/logs
sudo chmod -R 777 /var/www/$1/current/harvester/data
