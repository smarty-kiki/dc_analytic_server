<?php

if_get('/yunbi/*/k/*', function ($dc_name, $period)
{
    $limit = input('limit', 1000);

    $table = crawl_yunbi_k_table($dc_name, $period);

    return array_reverse(storage_query($table, [], [], ['at' => -1], 0, $limit));
});
