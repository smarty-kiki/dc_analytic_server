<?php

if_get('/yunbi/*', function ($table)
{
    return array_reverse(storage_query($table, [], [], ['at' => -1]));
});
