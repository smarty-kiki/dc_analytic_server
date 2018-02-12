<?php

if_get('/', function ()
{
    return dialogue_push((string) input('user'), (string) input('msg'), true);
});
