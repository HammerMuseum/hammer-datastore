#!/bin/bash
cd /var/www/datastore.hammer.ucla.edu/current/harvester/
sudo python3 setup.py install
sudo chown -R deploy:deploy /var/www/datastore.hammer.ucla.edu/current/harvester/
