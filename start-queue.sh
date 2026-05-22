#!/bin/bash

mkdir -p /var/www/html/v2/runtime/logs

while :
do
    echo "$(date +%F-%T) [queue] start" >> /var/www/html/v2/runtime/logs/queue.log

    php /var/www/html/v2/yii queue/listen --verbose=1 --isolate=0 --color=1

    echo "$(date +%F-%T) [queue] stopped — restarting in 3s" >> /var/www/html/v2/runtime/logs/queue.log
    sleep 3
done
