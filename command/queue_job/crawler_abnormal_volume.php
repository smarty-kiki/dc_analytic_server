<?php

function crawler_bittrex_abnormal_volume_table()
{/*{{{*/
    return 'crawler_bittrex_abnormal_volume';
}/*}}}*/

function crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $volume, $volume_at, $before_avg_volume, $description)
{/*{{{*/
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
            queue_push('crawler_bittrex_abnormal_volume_single', [
                'symbol' => $symbol,
                'rank' => $ranks[$symbol],
            ]);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_ds($ex->getMessage());
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

            $total_count = count($result);

            foreach ($result as $index => $tick) {

                if ($index > $total_count - 12) { // 一小时内的

                    $btc_volume = (float) $tick['BV'];

                    $tmp_result = array_slice($result, $index - $step, $step);

                    $bv_result = array_fetch($tmp_result, 'BV');

                    $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                    if ($btc_volume > $btc_avg_volume * 6 && $btc_volume > 5) {

                        $high_price = (float) $tick['H'];

                        $percent_change_1h = round((($high_price - $result[$index - 12]['H']) /$result[$index - 12]['H'] ) * 100, 1);
                        $percent_change_24h = round((($high_price - $result[$index - 288]['H']) /$result[$index - 288]['H'] ) * 100, 1);

                        $tmp_result = array_slice($result, $index - 288, 288);
                        $h_result = array_fetch($tmp_result, 'H');

                        $max_h_result = max($h_result);
                        $highest_price_percent_change_in_24h = round((($max_h_result - $high_price) /$max_h_result) * 100, 1);

                        crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now($tick['T'].' +8 hours'), $btc_avg_volume, 
                            '*#'.$rank.' '.$symbol.' '.now($tick['T'].' +8 hours', 'm/d H:i').'*'
                            ."\n*5 分钟交易量 ".$btc_volume.'*'
                            ."\n1 小时涨幅 ".$percent_change_1h.'%'
                            ."\n24 小时涨幅 ".$percent_change_24h.'%'
                            ."\n相比 24 小时内最高面值 ".$highest_price_percent_change_in_24h.'%'
                            ."\n前 ".$step.' 柱平均交易量 '.$btc_avg_volume
                            ."\n前 ".$step." 柱明细:\n  ".implode("\n  ", $bv_result)
                        );

                    }
                }
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
