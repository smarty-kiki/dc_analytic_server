<?php

if_get('/yunbi/*', function ($table)
{
    $limit = input('limit', 1000);

    return array_reverse(storage_query($table, [], [], ['at' => -1], 0, $limit));
});
