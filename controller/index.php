<?php

if_get('/', function ()
{
    dialogue_push(input('uid'), input('msg'));
});
