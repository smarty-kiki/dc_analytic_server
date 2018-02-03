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
        $market_infos = remote_get_json('https://api.coinmarketcap.com/v1/ticker/?limit=150', 10);
        $market_infos_indexed_by_symbol = [];
        $ranks = [];

        otherwise($market_infos, '连接 coinmarketcap 不畅通');

        foreach ($market_infos as $rank => $info) {
            $symbol = $info['symbol'];

            $ranks[$symbol] = $rank;

            $market_infos_indexed_by_symbol[$symbol] = $info;
        }

        foreach ($market_infos_indexed_by_symbol as $symbol => $info) {
            queue_push('crawler_bittrex_abnormal_volume_single', [
                'symbol' => $symbol,
                'rank' => $ranks[$symbol],
            ]);
            queue_push('crawler_binance_abnormal_volume_single', [
                'symbol' => $symbol,
                'rank' => $ranks[$symbol],
            ]);
            queue_push('crawler_okex_abnormal_volume_single', [
                'symbol' => $symbol,
                'rank' => $ranks[$symbol],
            ]);
            queue_push('crawler_bitfinex_abnormal_volume_single', [
                'symbol' => $symbol,
                'rank' => $ranks[$symbol],
            ]);
        }
    } catch (Exception $ex) {
        if ($ex instanceof PDOException) {
            throw $ex;
        }

        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawler_bittrex_abnormal_volume_single', function ($data)
{/*{{{*/
    try {
        $step = 5;

        $symbol = 'BTC-'.$data['symbol'];
        $rank = $data['rank'];

        // 拉币网数据
        $res = remote_get_json('https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName='.$symbol.'&tickInterval=fiveMin', 3, 1);

        if ($res['success']) {

            $result = $res['result'];

            $total_count = count($result);

            foreach ($result as $index => $tick) {

                if ($index > $total_count - 12) { // 一小时内的

                    $btc_volume = (float) $tick['BV'];

                    $tmp_result = array_slice($result, $index - $step, $step);

                    $bv_result = array_fetch($tmp_result, 'BV');

                    $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                    if ($btc_volume > $btc_avg_volume * 6 && $btc_volume > 10) {

                        $high_price = (float) $tick['H'];

                        $percent_change_1h = round((($high_price - $result[$index - 12]['H']) /$result[$index - 12]['H'] ) * 100, 1);
                        $percent_change_24h = round((($high_price - $result[$index - 288]['H']) /$result[$index - 288]['H'] ) * 100, 1);

                        $tmp_result = array_slice($result, $index - 288, 288);
                        $h_result = array_fetch($tmp_result, 'H');

                        $max_h_result = max($h_result);
                        $highest_price_percent_change_in_24h = round((($high_price - $max_h_result) /$max_h_result) * 100, 1);

                        crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now($tick['T'].' +8 hours'), $btc_avg_volume,
                            '*#'.$rank.' '.$symbol.' 币网 '.now($tick['T'].' +8 hours', 'H:i').'*'
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
        } else {
            echo $data['symbol']." 请求币网超时\n";
        }
    } catch (Exception $ex) {
        if ($ex instanceof PDOException) {
            throw $ex;
        }
        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawler_binance_abnormal_volume_single', function ($data)
{/*{{{*/
    try {
        $step = 5;

        $symbol = $data['symbol'].'BTC';
        $rank = $data['rank'];

        // 拉币安数据
        $res = remote_get_json('https://api.binance.com/api/v1/klines?symbol='.$symbol.'&interval=5m&limit=300', 3, 1);

        if ((! empty($res)) && ! array_key_exists('code', $res)) {

            array_pop($res);

            $result = $res;

            $total_count = count($result);

            foreach ($result as $index => $tick) {

                if ($index > $total_count - 12) { // 一小时内的

                    $btc_volume = (float) ($tick[5] * $tick[3]);

                    $tmp_result = array_slice($result, $index - $step, $step);

                    $bv_result = array_map(function ($v) {
                        return (float) ($v[5] * $v[3]);
                    }, $tmp_result);

                    $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                    if ($btc_volume > $btc_avg_volume * 6 && $btc_volume > 10) {

                        $high_price = (float) $tick[3];

                        $percent_change_1h = round((($high_price - $result[$index - 12][3]) /$result[$index - 12][3] ) * 100, 1);
                        $percent_change_24h = round((($high_price - $result[$index - 288][3]) /$result[$index - 288][3] ) * 100, 1);

                        $tmp_result = array_slice($result, $index - 288, 288);
                        $h_result = array_fetch($tmp_result, 3);

                        $max_h_result = max($h_result);
                        $highest_price_percent_change_in_24h = round((($high_price - $max_h_result) /$max_h_result) * 100, 1);

                        crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now(($tick[6] / 1000)), $btc_avg_volume,
                            '*#'.$rank.' BTC-'.$data['symbol'].' 币安 '.now(($tick[6] / 1000), 'H:i').'*'
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
        } else {
            echo $data['symbol']." 请求币安超时\n";
        }
    } catch (Exception $ex) {
        if ($ex instanceof PDOException) {
            throw $ex;
        }
        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawler_okex_abnormal_volume_single', function ($data)
{/*{{{*/
    try {
        $step = 5;

        $symbol = $data['symbol'].'_BTC';
        $rank = $data['rank'];

        // 拉币安数据
        $res = remote_get_json('https://www.okex.com/api/v1/kline.do?symbol='.$symbol.'&type=5min&size=300', 3, 1);

        if ((! empty($res)) && ! array_key_exists('error_code', $res)) {

            $result = $res;

            $total_count = count($result);

            foreach ($result as $index => $tick) {

                if ($index > $total_count - 12) { // 一小时内的

                    $btc_volume = (float) ($tick[5] * $tick[2]);

                    $tmp_result = array_slice($result, $index - $step, $step);

                    $bv_result = array_map(function ($v) {
                        return (float) ($v[5] * $v[2]);
                    }, $tmp_result);

                    $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                    if ($btc_volume > $btc_avg_volume * 6 && $btc_volume > 10) {

                        $high_price = (float) $tick[2];

                        $percent_change_1h = round((($high_price - $result[$index - 12][2]) /$result[$index - 12][2] ) * 100, 1);
                        $percent_change_24h = round((($high_price - $result[$index - 288][2]) /$result[$index - 288][2] ) * 100, 1);

                        $tmp_result = array_slice($result, $index - 288, 288);
                        $h_result = array_fetch($tmp_result, 2);

                        $max_h_result = max($h_result);
                        $highest_price_percent_change_in_24h = round((($high_price - $max_h_result) /$max_h_result) * 100, 1);

                        crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now(($tick[0] / 1000)), $btc_avg_volume,
                            '*#'.$rank.' BTC-'.$data['symbol'].' OKEX '.now(($tick[0] / 1000), 'H:i').'*'
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
        } else {
            echo $data['symbol']." 请求 okex 超时\n";
        }
    } catch (Exception $ex) {
        if ($ex instanceof PDOException) {
            throw $ex;
        }
        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawler_bitfinex_abnormal_volume_single', function ($data)
{/*{{{*/
    try {
        $step = 5;

        $symbol = 'tBTC'.$data['symbol'];
        $rank = $data['rank'];

        // 拉币安数据
        $res = remote_get_json('https://api.bitfinex.com/v2/candles/trade:5m:'.$symbol.'/hist?limit=300', 3, 1);

        if ($res && 'error' !== $res[0]) {

            $result = $res;

            $total_count = count($result);

            foreach ($result as $index => $tick) {

                if ($index > $total_count - 12) { // 一小时内的

                    $btc_volume = (float) ($tick[5] * $tick[3]);

                    $tmp_result = array_slice($result, $index - $step, $step);

                    $bv_result = array_map(function ($v) {
                        return (float) ($v[5] * $v[3]);
                    }, $tmp_result);

                    $btc_avg_volume = array_sum($bv_result) / count($bv_result);

                    if ($btc_volume > $btc_avg_volume * 6 && $btc_volume > 10) {

                        $high_price = (float) $tick[3];

                        $percent_change_1h = round((($high_price - $result[$index - 12][3]) /$result[$index - 12][3] ) * 100, 1);
                        $percent_change_24h = round((($high_price - $result[$index - 288][3]) /$result[$index - 288][3] ) * 100, 1);

                        $tmp_result = array_slice($result, $index - 288, 288);
                        $h_result = array_fetch($tmp_result, 3);

                        $max_h_result = max($h_result);
                        $highest_price_percent_change_in_24h = round((($high_price - $max_h_result) /$max_h_result) * 100, 1);

                        crawler_bittrex_abnormal_volume_slack_save_and_send_slack($symbol, $rank, $btc_volume, now(($tick[0] / 1000)), $btc_avg_volume,
                            '*#'.$rank.' BTC-'.$data['symbol'].' bitfinex '.now(($tick[0] / 1000), 'H:i').'*'
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
        } else {
            echo $data['symbol']." 请求 bitfinex 超时\n";
        }
    } catch (Exception $ex) {
        if ($ex instanceof PDOException) {
            throw $ex;
        }
        slack_say_to_smarty_ds($ex->getMessage());
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
