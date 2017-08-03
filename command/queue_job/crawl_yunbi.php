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

    if ($infos) {

        $insert_datas = [];

        $ats = array_column($infos, 0);
        $inserted_datas = storage_query($table, [], ['at' => ['$in' => $ats]], ['at' => -1], 0, 1);
        $inserted_ats = array_column($inserted_datas, 'at');
        $inserted_ats = array_flip($inserted_ats);

        foreach ($infos as $info) {
            $timestamp = $info[0];

            $insert_data = [
                'at' => $info[0],
                'first' => $info[1],
                'max' => $info[2],
                'min' => $info[3],
                'last' => $info[4],
                'vol' => $info[5],
            ];

            if (isset($inserted_ats[$timestamp])) {
                storage_update($table, ['at' => $timestamp], $insert_data);
            } else {
                $insert_datas[] = $insert_data;
            }
        }

        if ($insert_datas) {
            storage_multi_insert($table, $insert_datas);
        }
    }

    return true;
}, $priority = 10, $retry = [], $tube = 'default', $config_key = 'default');/*}}}*/
