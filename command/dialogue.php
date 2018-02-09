<?php

command('dialogue:operator', '启动接线员', function ()
{/*{{{*/
    $config_key = command_paramater('config_key', 'default');
    $memory_limit = command_paramater('memory_limit', 1048576 * 128);

    ini_set('memory_limit', $memory_limit.'b');

    dialogue_send_action(function ($user_id, $message) {
        business_wechat_send_message($user_id, [],[],$message);
    });

    dialogue_topic_miss(function ($user_id, $message) {
        business_wechat_send_message($user_id, [],[],$message);
    });

    dialogue_watch($config_key, $memory_limit);
});/*}}}*/
