#!/bin/bash

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"/../..

ln -fs $ROOT_DIR/project/config/production/nginx/dc_analytic_server.conf /etc/nginx/sites-enabled/dc_analytic_server
/usr/sbin/service nginx reload

/usr/bin/php $ROOT_DIR/public/cli.php migrate

ln -fs $ROOT_DIR/project/config/production/supervisor/dialogue_operator.conf /etc/supervisor/conf.d/dialogue_operator.conf
/usr/bin/supervisorctl update
/usr/bin/supervisorctl restart dialogue_operator:*
