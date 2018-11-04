#!/bin/bash

ROOT_DIR="$(cd "$(dirname $0)" && pwd)"/../../

sudo docker run --rm -ti -p 80:80 -p 3306:3306 --name dc_analytic_server \
    -v $ROOT_DIR:/var/www/dc_analytic_server \
    -v $ROOT_DIR/project/config/development/nginx/dc_analytic_server.conf:/etc/nginx/sites-enabled/default \
    -v $ROOT_DIR/project/config/development/supervisor/dialogue_operator.conf:/etc/supervisor/conf.d/dialogue_operator.conf \
    -v $ROOT_DIR/project/config/development/supervisor/queue_worker.conf:/etc/supervisor/conf.d/queue_worker.conf \
kikiyao/debian_php_dev_env start
