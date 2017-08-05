<?php

ini_set('display_errors', 'on');
date_default_timezone_set('Asia/Shanghai');

define('ROOT_DIR', __DIR__);
define('FRAME_DIR', ROOT_DIR.'/frame');
define('COMMAND_DIR', ROOT_DIR.'/command');
define('CONTROLLER_DIR', ROOT_DIR.'/controller');
define('QUEUE_JOB_DIR', COMMAND_DIR.'/queue_job');

include FRAME_DIR.'/function.php';
include FRAME_DIR.'/otherwise.php';
include FRAME_DIR.'/database/mysql.php';
include FRAME_DIR.'/queue/beanstalk.php';

config_dir(ROOT_DIR.'/config');

include ROOT_DIR.'/util/load.php';
include QUEUE_JOB_DIR.'/load.php';
