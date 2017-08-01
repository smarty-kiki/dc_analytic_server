<?php

function crawl_yunbi_k_periods()
{
    return [1, 5, 15, 30, 60, 120, 240];
}

function crawl_yunbi_k_table($market, $period)
{
    return "crawl_yunbi_k_{$market}_{$period}";
}

queue_job('crawl_yunbi_k', function ($data)
{/*{{{*/
    $market = $data['market'];
    $period = $data['period'];

    $table = crawl_yunbi_k_table($market, $period);

    $last_infos = storage_query($table, [], [], ['at' => -1], 0, 1);
    $last_info = reset($last_infos);

    $last_timestamp = 1262275200; // 2010-01-01 00:00:00
    if ($last_info) {
        $last_timestamp = $last_info['at'];
    }

    $infos = remote_get_json('https://yunbi.com//api/v2/k.json?'.http_build_query([
        'market' => $market,
        'limit' => 500,
        'period' => $period,
        'timestamp' => $last_timestamp,
    ]));

    foreach ($infos as $info) {
        $timestamp = $info[0];

        if (storage_query($table, [], ['at' => $timestamp], ['at' => -1], 0, 1)) {
            echo $table.' '.$timestamp."continue \n";
            continue;
        }

        storage_insert($table, [
            'at' => $info[0],
            'first' => $info[1],
            'max' => $info[2],
            'min' => $info[3],
            'last' => $info[4],
            'vol' => $info[5],
        ]);
        echo $table.' '.$timestamp."insert \n";
    }

    return true;
}, $priority = 10, $retry = [], $tube = 'default', $config_key = 'default');/*}}}*/
