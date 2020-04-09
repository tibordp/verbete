#!/bin/bash
set -e 

pushd /opt
until php ping.php; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

# Import the data into the DB
php import.php
popd

# Serve the requests
apache2-foreground