<?php

if_get('/talk', function ()
{
    $message =  dialogue_push((string) input('user'), (string) input('msg'), true);

    return $message['content'];
});
