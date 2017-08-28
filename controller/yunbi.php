<?php

if_get('/yunbi/*/k/*', function ($dc_name, $period)
{
    $limit = input('limit', 1000);

    $table = crawl_yunbi_k_table($dc_name, $period);

    list($start_time, $end_time) = input_list('start_time', 'end_time');

    if ($start_time) {
        return db_simple_query($table, [
            'at >=' => strtotime($start_time),
            'at <=' => strtotime($end_time)
        ], 'order by at asc');
    }

    return array_reverse(db_simple_query($table, [], 'order by at desc limit '.$limit));
});

if_get('/yunbi/markets', function () {

    $infos = remote_get_json('https://yunbi.com//api/v2/markets.json');

    return $infos;
});
