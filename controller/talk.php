<?php

if_get('/talk', function ()
{
    return dialogue_push((string) input('user'), (string) input('msg'), true);
});
