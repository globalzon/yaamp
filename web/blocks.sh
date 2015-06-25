#!/bin/bash

cd /var/web
while true; do
        php5 run.php cronjob/runblocks
        sleep 20
done
exec bash


