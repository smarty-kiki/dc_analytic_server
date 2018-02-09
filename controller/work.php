<?php

if_get('/work/receive', function ()
{
    list($msg_signature, $timestamp, $nonce, $echostr) = input_list('msg_signature', 'timestamp', 'nonce', 'echostr');

    return business_wechat_verify_url($msg_signature, $timestamp, $nonce, $echostr);
});

if_post('/work/receive', function ()
{
    list($msg_signature, $timestamp, $nonce) = input_list('msg_signature', 'timestamp', 'nonce');

    $message_xml = business_wechat_decrypt_message($msg_signature, $timestamp, $nonce, input_post_raw());

    /**kiki*/error_log(print_r($message_xml, true)."\n", 3, '/tmp/error_user.log');exit;

    //dialogue_push(input('uid'), input('msg'));
});
