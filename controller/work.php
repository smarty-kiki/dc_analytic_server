<?php

if_get('/work/receive', function ()
{
    list($msg_signature, $timestamp, $nonce, $echostr) = input_list('msg_signature', 'timestamp', 'nonce', 'echostr');

    return business_wechat_verify_url($msg_signature, $timestamp, $nonce, $echostr);
});

if_post('/work/receive', function ()
{
    list($msg_signature, $timestamp, $nonce) = input_list('msg_signature', 'timestamp', 'nonce');

    $message_info = business_wechat_receive_message($msg_signature, $timestamp, $nonce, input_post_raw());
    /**kiki*/error_log(print_r($message_info, true)."\n", 3, '/tmp/error_user.log');

    $type = $message_info['type'];
    $message = $message_info['message'];

    switch ($type) {
    case 'text':
        $reply_message = dialogue_push($message['user_id'], $message['content'], true);

        $res =  business_wechat_reply_message($reply_message['user_id'], $reply_message['content']);
        /**kiki*/error_log(print_r($res, true)."\n", 3, '/tmp/error_user.log');
        return $res;
    }
});
