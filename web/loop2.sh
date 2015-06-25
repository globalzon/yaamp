#!/bin/bash

cd /var/web
while true; do
        php5 run.php cronjob/runloop2
        sleep 60
done
exec bash

