<?php

command('crawler:yunbi-markets', '从云币抓取 markets 数据', function () {

    $infos = remote_get_json('https://yunbi.com//api/v2/markets.json');

    foreach ($infos as $info) {
        echo $info['name'].'   '.$info['id']."\n";
    }
});

command('crawler:yunbi-k-jobs', '从云币抓取 k 线数据', function () {

    $infos = remote_get_json('https://yunbi.com//api/v2/markets.json');

    $periods = crawl_yunbi_k_periods();

    foreach ($infos as $info) {

        foreach ($periods as $period) {
            queue_push('crawl_yunbi_k', [
                'market' => $info['id'],
                'period' => $period,
            ]);
        }
    }
});

command('crawler:yunbi-k-clean', '清除从云币抓取的 k 线数据', function () {

    $infos = remote_get_json('https://yunbi.com/api/v2/tickers.json');

    $periods = crawl_yunbi_k_periods();

    foreach ($infos as $market => $info) {

        foreach ($periods as $period) {
            storage_delete(crawl_yunbi_k_table($market, $period));
        }

    }

});