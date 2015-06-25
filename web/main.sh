#!/bin/bash

cd /var/web
while true; do
        php5 run.php cronjob/run
        sleep 90
done
exec bash

