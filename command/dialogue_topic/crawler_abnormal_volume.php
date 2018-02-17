<?php

dialogue_topic(['查*的价格', '*现在多少钱', '*多少钱了'], function ($user_id, $content, $time, $symbol) {/*{{{*/

    $symbol = trim($symbol);

    $res = remote_get_json("https://bittrex.com/api/v1.1/public/getticker?market=BTC-$symbol");

    if ($res['success']) {
        dialogue_say($user_id, $res['result']['Last'].' BTC');
    } else {
        dialogue_say($user_id, '币网没这个币');
    }

});/*}}}*/

dialogue_topic(['查*的资料', '*是什么币', '介绍一下*'], function ($user_id, $content, $time, $symbol) {/*{{{*/

    $upper_symbol = strtoupper(trim($symbol));

    $tickers = coinmarketcap_get_tickers();

    if (array_key_exists($upper_symbol, $tickers)) {
        dialogue_say($user_id, '进这里看 https://www.btc8.io/currency/'.$tickers[$upper_symbol]['name']);
    } else {
        dialogue_say($user_id, "额，我还不知道 $symbol 是什么");
    }
});/*}}}*/
