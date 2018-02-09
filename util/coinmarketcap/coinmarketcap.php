<?php

function coinmarketcap_get_tickers()
{/*{{{*/
    static $tickers = [];

    if (empty($tickers)) {
        $tickers = cache_get('coinmarketcap_tickers');
    }

    if (empty($tickers)) {
        $tmp_tickers = remote_get_json(' https://api.coinmarketcap.com/v1/ticker/?convert=CNY', 10);

        foreach ($tmp_tickers as $ticker) {
            $tickers[$ticker['symbol']] = [
                'name' => $ticker['name'],
                'symbol' => $ticker['symbol'],
            ];
        }

        cache_set('coinmarketcap_tickers', $tickers, 3600);
    }

    return $tickers;
}/*}}}*/
