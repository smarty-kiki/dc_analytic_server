<?php

if_get('/yunbi/*/k/*', function ($dc_name, $period)
{
    $limit = input('limit', 1000);

    $table = crawl_yunbi_k_table($dc_name, $period);

    return array_reverse(db_simple_query($table, [], 'order by at desc limit '.$limit));
});
