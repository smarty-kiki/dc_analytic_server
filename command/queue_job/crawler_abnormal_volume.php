<?php

function crawler_bittrex_abnormal_volume_table()
{/*{{{*/
    return 'crawler_bittrex_abnormal_volume';
}/*}}}*/

function crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $volume, $volume_at, $before_avg_volume, $description)
{/*{{{*/
    if (strtotime($volume_at) > strtotime(now('-1 hours'))) {
        if (! $ann = db_simple_query_first(crawler_bittrex_abnormal_volume_table(), ['symbol' => $symbol, 'volume_at' => $volume_at])) {
            db_simple_insert(crawler_bittrex_abnormal_volume_table(), [
                'symbol' => $symbol,
                'volume' => $volume,
                'volume_at' => $volume_at,
                'before_avg_volume' => $before_avg_volume,
                'description' => $description,
                'rank' => $rank,
                'at' => now(),
            ]);

            slack_say_to_smarty_ds($description);
        }
    }
}/*}}}*/

queue_job('crawler_bittrex_abnormal_volume', function ()
{/*{{{*/
    try {
        // 拉 coinmarketcap 前 200 数据
        $market_infos = remote_get_json('https://api.coinmarketcap.com/v1/ticker/?limit=200', 10);
        $market_infos_indexed_by_symbol = [];
        $ranks = [];

        otherwise($market_infos, '连接 coinmarketcap 不畅通');

        foreach ($market_infos as $rank => $info) {
            $symbol = 'BTC-'.$info['symbol'];

            $ranks[$symbol] = $rank;

            $market_infos_indexed_by_symbol[$symbol] = $info;
        }

        foreach ($market_infos_indexed_by_symbol as $symbol => $info) {
            queue_push('crawler_bittrex_abnormal_volume_single', ['symbol' => $symbol, 'rank' => $ranks[$symbol]]);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_ds($ex->getMessage());
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawler_bittrex_abnormal_volume_single', function ($data)
{/*{{{*/
    try {
        $step = 5;

        $symbol = $data['symbol'];
        $rank = $data['rank'];

        // 拉币网数据
        $res = remote_get_json('https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName='.$symbol.'&tickInterval=fiveMin', 10);

        if ($res['success']) {

            $result = $res['result'];

            foreach ($result as $index => $tick) {
                if ($index < 6) {
                    continue;
                }

                $btc_volume = (float) $tick['BV'];

                $tmp_result = array_slice($result, $index - $step, $step);

                $bv_result = array_fetch($tmp_result, 'BV');

                $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                if ($btc_volume > $btc_avg_volume * 4) {
                    crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now($tick['T'].' +8 hours'), $btc_avg_volume, 
                        '#'.$rank.' '.$symbol.' '.now($tick['T'].' +8 hours').' 5分钟交易量 '.$btc_volume
                        ."\n前".$step.'柱平均交易量: '.$btc_avg_volume
                        ."\n前".$step.'柱明细: '.implode(' ', $bv_result)
                    );
                }
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_ds($ex->getMessage());
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
