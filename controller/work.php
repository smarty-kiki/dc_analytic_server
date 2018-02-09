<?php

if_get('/work/receive', function ()
{
    list($msg_signature, $timestamp, $nonce, $echostr) = input_list('msg_signature', 'timestamp', 'nonce', 'echostr');

    return business_wechat_verify_url($msg_signature, $timestamp, $nonce, $echostr);
});

if_post('/work/receive', function ()
{
    dialogue_push(input('uid'), input('msg'));
});
