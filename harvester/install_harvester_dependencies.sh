#!/bin/bash
cd /var/www/datastore.hammer.cogapp.com/current/harvester/
sudo python3 setup.py install
sudo chown -R deploy:deploy /var/www/datastore.hammer.cogapp.com/current/harvester/
