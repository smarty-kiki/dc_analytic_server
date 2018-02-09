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

    $message = simplexml_load_string($message_xml);

    dialogue_push((string) $message->FromUserName, (string) $message->Content);
});
